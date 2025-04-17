<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function index(): JsonResponse
    {
        $favorites = Favorite::where('user_id', Auth::id())
            ->with(['apartment.user', 'apartment.images'])
            ->latest()
            ->get();
        
        return response()->json($favorites);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'apartment_id' => 'required|exists:apartments,id'
        ]);

        $favorite = Favorite::firstOrCreate([
            'user_id' => Auth::id(),
            'apartment_id' => $request->apartment_id
        ]);

        return response()->json($favorite, 201);
    }

    public function destroy(Apartment $apartment): JsonResponse
    {
        $favorite = Favorite::where('user_id', Auth::id())
            ->where('apartment_id', $apartment->id)
            ->firstOrFail();

        $favorite->delete();
        return response()->json(null, 204);
    }

    public function check(Apartment $apartment): JsonResponse
    {
        $isFavorite = Favorite::where('user_id', Auth::id())
            ->where('apartment_id', $apartment->id)
            ->exists();

        return response()->json(['is_favorite' => $isFavorite]);
    }
} 