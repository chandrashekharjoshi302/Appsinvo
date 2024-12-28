<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'status' => 'nullable|in:active,inactive', // Include status validation
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'address' => $validated['address'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status' => $validated['status'] ?? 'active', // Default to 'active'
        ]);

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'status_code' => 200,
            'message' => 'User registered successfully',
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'address' => $user->address,
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
                'status' => $user->status, // Include status in the response
                'register_at' => $user->created_at->toDateTimeString(),
                'token' => $token,
            ]
        ]);
    }


    public function toggleStatuses()
    {
        // Toggle all users' statuses in a single query
        User::query()->update([
            'status' => DB::raw("CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END")
        ]);

        return response()->json([
            'status_code' => 200,
            'message' => 'All user statuses toggled successfully',
        ]);
    }



    public function getDistance(Request $request)
    {
        // Validate the request parameters for destination coordinates
        $request->validate([
            'destination_latitude' => 'required|numeric',
            'destination_longitude' => 'required|numeric',
        ]);

        // Retrieve the authenticated user
        $authenticatedUser = Auth::user();

        // If the user is not authenticated, return an error response
        if (!$authenticatedUser) {
            return response()->json([
                'status_code' => 401,
                'message' => 'Unauthorized - Please log in',
            ], 401);
        }

        // Calculate the distance from the user's location to the provided destination
        $calculatedDistance = $this->calculateDistance(
            $authenticatedUser->latitude,
            $authenticatedUser->longitude,
            $request->destination_latitude,
            $request->destination_longitude
        );

        // Return the calculated distance in the response
        return response()->json([
            'status_code' => 200,
            'message' => 'Distance calculated successfully',
            'distance' => $calculatedDistance . ' km',
        ]);
    }

    private function calculateDistance($userLatitude, $userLongitude, $destinationLatitude, $destinationLongitude)
    {
        // Earth's radius in kilometers
        $earthRadiusKm = 6371;

        // Convert latitude and longitude from degrees to radians
        $userLatitudeRad = deg2rad($userLatitude);
        $destinationLatitudeRad = deg2rad($destinationLatitude);
        $latitudeDifference = $destinationLatitudeRad - $userLatitudeRad;
        $longitudeDifference = deg2rad($destinationLongitude - $userLongitude);

        // Apply the Haversine formula to calculate the distance
        $a = sin($latitudeDifference / 2) * sin($latitudeDifference / 2) +
            cos($userLatitudeRad) * cos($destinationLatitudeRad) *
            sin($longitudeDifference / 2) * sin($longitudeDifference / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // Calculate the distance and round it to two decimal places
        return round($earthRadiusKm * $c, 2);
    }


    public function listUsersByDays(Request $request)
    {
        $request->validate([
            'week_number' => 'required|array',
            'week_number.*' => 'integer|min:0|max:6',
        ]);

        $users = User::all()->groupBy(function ($user) {
            return $user->created_at->dayOfWeek;
        });



        $response = [];
        foreach ($request->week_number as $day) {
            if (isset($users[$day])) {
                $response[config('app.days')[$day]] = $users[$day]->map(function ($user) {
                    return ['name' => $user->name, 'email' => $user->email];
                })->toArray();
            } else {
                $response[config('app.days')[$day]] = [];
            }
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'Users listed successfully',
            'data' => $response,
        ]);
    }
}
