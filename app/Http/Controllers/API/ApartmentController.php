<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Image;
use App\Services\FirebaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ApartmentController extends Controller
{
    protected $firebaseStorage;

    public function __construct(FirebaseStorageService $firebaseStorage)
    {
        $this->firebaseStorage = $firebaseStorage;
    }

    public function index(): JsonResponse
    {
        $apartments = Apartment::with(['user', 'images'])->latest()->paginate(10);
        return response()->json($apartments);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'bedrooms' => 'required|integer|min:0',
            'bathrooms' => 'required|integer|min:0',
            'square_feet' => 'required|integer|min:0',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zip_code' => 'required|string|max:20',
            'status' => 'required|in:available,rented,sold',
            'type' => 'required|in:apartment,condo,studio',
            'amenities' => 'nullable|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $apartment = Apartment::create(array_merge(
            $request->except('images'),
            ['user_id' => Auth::id()]
        ));

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $imageUrl = $this->firebaseStorage->uploadImage(
                    $image,
                    'apartments/' . $apartment->id
                );

                $apartment->images()->create([
                    'path' => $imageUrl,
                    'is_primary' => $index === 0
                ]);
            }
        }

        return response()->json($apartment->load('images'), 201);
    }

    public function show(Apartment $apartment): JsonResponse
    {
        return response()->json($apartment->load(['user', 'images']));
    }

    public function update(Request $request, Apartment $apartment): JsonResponse
    {
        if ($apartment->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'bedrooms' => 'sometimes|integer|min:0',
            'bathrooms' => 'sometimes|integer|min:0',
            'square_feet' => 'sometimes|integer|min:0',
            'address' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:255',
            'state' => 'sometimes|string|max:255',
            'zip_code' => 'sometimes|string|max:20',
            'status' => 'sometimes|in:available,rented,sold',
            'type' => 'sometimes|in:apartment,condo,studio',
            'amenities' => 'sometimes|array',
            'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'delete_images' => 'sometimes|array',
            'delete_images.*' => 'exists:images,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $apartment->update($request->except(['images', 'delete_images']));

        // Handle image deletions
        if ($request->has('delete_images')) {
            $images = Image::whereIn('id', $request->delete_images)
                ->where('apartment_id', $apartment->id)
                ->get();

            foreach ($images as $image) {
                $this->firebaseStorage->deleteImage($image->path);
                $image->delete();
            }
        }

        // Handle new images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imageUrl = $this->firebaseStorage->uploadImage(
                    $image,
                    'apartments/' . $apartment->id
                );

                $apartment->images()->create([
                    'path' => $imageUrl,
                    'is_primary' => false
                ]);
            }
        }

        return response()->json($apartment->load('images'));
    }

    public function destroy(Apartment $apartment): JsonResponse
    {
        if ($apartment->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete images from Firebase Storage
        foreach ($apartment->images as $image) {
            $this->firebaseStorage->deleteImage($image->path);
        }

        $apartment->delete();
        return response()->json(null, 204);
    }

    public function featured(): JsonResponse
    {
        $featured = Apartment::where('is_featured', true)
            ->with(['user', 'images'])
            ->latest()
            ->take(6)
            ->get();
        
        return response()->json($featured);
    }

    public function search(Request $request): JsonResponse
    {
        $query = Apartment::query();

        if ($request->has('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }

        if ($request->has('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        if ($request->has('bedrooms')) {
            $query->where('bedrooms', '>=', $request->bedrooms);
        }

        if ($request->has('bathrooms')) {
            $query->where('bathrooms', '>=', $request->bathrooms);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        $apartments = $query->with(['user', 'images'])->latest()->paginate(10);
        return response()->json($apartments);
    }

    public function userApartments(): JsonResponse
    {
        $apartments = Apartment::where('user_id', Auth::id())
            ->with(['images'])
            ->latest()
            ->paginate(10);
        
        return response()->json($apartments);
    }
} 