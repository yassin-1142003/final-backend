<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    use ApiResponses;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except([
            'login',
            'register',
            'forgotPassword',
            'resetPassword'
        ]);
    }

    /**
     * Register a new user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'role_id' => 'required|exists:roles,id',
                'phone' => 'required|string|max:20',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'id_card_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,
                'phone' => $request->phone,
                'is_active' => true
            ];

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                try {
                    $profileImage = $request->file('profile_image');
                    $profileImageName = time() . '_' . uniqid() . '_profile.' . $profileImage->getClientOriginalExtension();
                    
                    // Ensure the storage directory exists
                    Storage::disk('public')->makeDirectory('profiles');
                    
                    // Store the file
                    $path = $profileImage->storeAs('profiles', $profileImageName, 'public');
                    if (!$path) {
                        throw new \Exception('Failed to store profile image');
                    }
                    $userData['profile_image'] = $path;
                    Log::info('Profile image stored at: ' . $path);
                } catch (\Exception $e) {
                    Log::error('Profile image upload failed: ' . $e->getMessage());
                    return response()->json([
                        'message' => 'Failed to upload profile image',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            // Handle ID card image upload
            if ($request->hasFile('id_card_image')) {
                try {
                    $idCardImage = $request->file('id_card_image');
                    $idCardImageName = time() . '_' . uniqid() . '_id_card.' . $idCardImage->getClientOriginalExtension();
                    
                    // Ensure the storage directory exists
                    Storage::disk('public')->makeDirectory('id_cards');
                    
                    // Store the file
                    $path = $idCardImage->storeAs('id_cards', $idCardImageName, 'public');
                    if (!$path) {
                        throw new \Exception('Failed to store ID card image');
                    }
                    $userData['id_card_image'] = $path;
                    Log::info('ID card image stored at: ' . $path);
                } catch (\Exception $e) {
                    Log::error('ID card image upload failed: ' . $e->getMessage());
                    return response()->json([
                        'message' => 'Failed to upload ID card image',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            $user = User::create($userData);
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user and create token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            if (!Auth::attempt($request->only('email', 'password'))) {
                return $this->unauthorizedResponse('Invalid credentials');
            }

            $user = User::where('email', $request->email)->firstOrFail();
            
            // Revoke all existing tokens
            $user->tokens()->delete();
            
            // Create new token
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ], 'User logged in successfully');

        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Login failed', $e->getMessage(), 500);
        }
    }

    /**
     * Get authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            return $this->successResponse([
                'user' => $request->user()
            ], 'User profile retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get user information', $e->getMessage(), 500);
        }
    }

    /**
     * Logout user (Revoke the token)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        PersonalAccessToken::findToken($request->bearerToken())->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        try {
            $user = $request->user();
            
            // Delete all existing tokens
            $user->tokens()->delete();
            
            // Create new token
            $newToken = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Token refreshed successfully',
                'access_token' => $newToken,
                'token_type' => 'Bearer',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token refresh failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 