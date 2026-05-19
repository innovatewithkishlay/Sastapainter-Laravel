<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    private function sendResponse($status, $success, $message, $data = null)
    {
        $response = [
            'success' => $success,
            'message' => $message,
        ];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }

    private function formatUser($user)
    {
        return [
            '_id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'isAdmin' => $user->isAdmin,
            'profilePicture' => $user->profilePicture
        ];
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            // Check if it's already exists error to match Node.js exact message
            $errors = $validator->errors();
            if ($errors->has('username') || $errors->has('email')) {
                return $this->sendResponse(400, false, 'Username or Email already exists');
            }
            return $this->sendResponse(400, false, $validator->errors()->first());
        }

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->sendResponse(200, true, 'Registration successful', [
            'token' => $token,
            'user' => $this->formatUser($user)
        ]);
    }

    public function login(Request $request)
    {
        $emailOrUsername = $request->input('email');
        $password = $request->input('password');

        $user = User::where('email', $emailOrUsername)
                    ->orWhere('username', $emailOrUsername)
                    ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return $this->sendResponse(400, false, 'Invalid Credentials');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->sendResponse(200, true, 'Login successful', [
            'token' => $token,
            'user' => $this->formatUser($user)
        ]);
    }

    public function adminLogin(Request $request)
    {
        $usernameOrEmail = $request->input('username');
        $password = $request->input('password');

        // Hardcoded fallback admin matching original Node.js logic
        if ($usernameOrEmail === 'raj_maurya_7878' && $password === '208001@@Raj') {
            $admin = User::where('username', 'raj_maurya_7878')->first();
            if (!$admin) {
                $admin = User::create([
                    'username' => 'raj_maurya_7878',
                    'email' => 'admin@aapkapainter.clone',
                    'password' => Hash::make($password),
                    'isAdmin' => true
                ]);
            } else if (!$admin->isAdmin) {
                $admin->isAdmin = true;
                $admin->save();
            }

            $token = $admin->createToken('admin_token')->plainTextToken;
            return $this->sendResponse(200, true, 'Admin Login successful', [
                'token' => $token,
                'user' => $this->formatUser($admin)
            ]);
        }

        $user = User::where('username', $usernameOrEmail)
                    ->orWhere('email', $usernameOrEmail)
                    ->first();

        if ($user && $user->isAdmin && Hash::check($password, $user->password)) {
            $token = $user->createToken('admin_token')->plainTextToken;
            return $this->sendResponse(200, true, 'Admin Login successful', [
                'token' => $token,
                'user' => $this->formatUser($user)
            ]);
        }

        return $this->sendResponse(400, false, 'Invalid Admin Credentials');
    }

    public function googleLogin(Request $request)
    {
        $token = $request->input('token');
        $type = $request->input('type');

        if (!$token) {
            return $this->sendResponse(400, false, 'Token is required');
        }

        try {
            $email = null;
            $name = null;
            $picture = null;

            if ($type === 'access_token') {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->get('https://www.googleapis.com/oauth2/v3/userinfo');

                if ($response->failed()) {
                    return $this->sendResponse(400, false, 'Failed to fetch user info from Google');
                }

                $payload = $response->json();
                $email = $payload['email'] ?? null;
                $name = $payload['name'] ?? null;
                $picture = $payload['picture'] ?? null;
            } else {
                $response = \Illuminate\Support\Facades\Http::get('https://oauth2.googleapis.com/tokeninfo', [
                    'id_token' => $token,
                ]);

                if ($response->failed()) {
                    return $this->sendResponse(400, false, 'Google ID Token validation failed');
                }

                $payload = $response->json();
                $email = $payload['email'] ?? null;
                $name = $payload['name'] ?? null;
                $picture = $payload['picture'] ?? null;
            }

            if (!$email) {
                return $this->sendResponse(400, false, 'Google login failed: email not retrieved');
            }

            $user = User::where('email', $email)->first();

            if ($user) {
                if (empty($user->profilePicture)) {
                    $user->profilePicture = $picture;
                    $user->save();
                }

                $jwtToken = $user->createToken('auth_token')->plainTextToken;

                return $this->sendResponse(200, true, 'Login successful', [
                    'token' => $jwtToken,
                    'user' => $this->formatUser($user)
                ]);
            } else {
                $username = preg_replace('/\s+/', '', $name);
                $userExists = User::where('username', $username)->first();
                if ($userExists) {
                    $username = $username . rand(1000, 9999);
                }

                $user = User::create([
                    'username' => $username,
                    'email' => $email,
                    'authProvider' => 'google',
                    'profilePicture' => $picture,
                ]);

                $jwtToken = $user->createToken('auth_token')->plainTextToken;

                return $this->sendResponse(200, true, 'Registration successful', [
                    'token' => $jwtToken,
                    'user' => $this->formatUser($user)
                ]);
            }

        } catch (\Exception $e) {
            return $this->sendResponse(401, false, 'Google Authentication Failed: ' . $e->getMessage());
        }
    }

    public function checkAuth(Request $request)
    {
        if ($request->user()) {
            return response()->json([
                'success' => true,
                'message' => 'Authenticated',
                'data' => [
                    'isAuthenticated' => true,
                    'user' => $this->formatUser($request->user())
                ]
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Not Authenticated',
            'data' => [
                'isAuthenticated' => false,
                'user' => null
            ]
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
        return $this->sendResponse(200, true, 'Logged out successfully');
    }
}
