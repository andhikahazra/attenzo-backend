<?php

namespace App\Http\Controllers;

use App\Models\WorkLocation;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $location = $user->workLocation;
        
        if (!$location) {
            return response()->json(['message' => 'Work location not assigned'], 404);
        }
        
        return response()->json($location);
    }
}
