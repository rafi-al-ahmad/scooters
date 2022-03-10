<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class UserController extends Controller
{

    protected $validationRules = [
        'name' => ['required', 'max:60'],
        'surname' => ['required', 'max:60'],
        'username' => ['required', 'unique:users', 'max:30'],
        'email' => ['required', 'email:rfc,dns', 'unique:users', 'max:60',],
        'phone' => ['required', 'unique:users', 'max:18'],
    ];

    protected $providers = [
        'google',
        'facebook',
    ];

    public function socialAuth(Request $request, $provider)
    {
        $validated = $this->validateProvider($provider);
        if (!is_null($validated)) {
            return $validated;
        }

        $request->validate([
            'social_token' => 'required|string',
        ]);

        try {
            $providerUser = Socialite::driver($provider)->stateless()->userFromToken($request->social_token);
        } catch (\Throwable $th) {
            return response()->json(['error' => __('Invaled credentails provided')], 422);
        }


        $user = User::withTrashed()->firstOrCreate(
            [
                'email' => $providerUser->getEmail(),
            ],
            [
                'email_verified_at' => now(),
                'name' => $providerUser->getName(),
            ]
        );

        
        if ($user->deleted_at) {
            return response()->json(['message' => __('Banned account')], 403);
        }

        if ($user->isAdmin()) {
            return response()->json([
                'message' => __("Login using social accounts not allowed"),
            ], 403);
        }

        $user->providers()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => $providerUser->getId()
            ],
            [
                'avatar' => $providerUser->getAvatar(),
                'token' => $providerUser->token,
                'user_id' => $user->id,
            ]
        );


        $accessToken = $this->createUserAccessTooken($user);

        return response()->json([
            'user' => $user,
            'access-token' => $accessToken
        ]);
    }


    public function validateProvider($provider)
    {
        if (!in_array($provider, $this->providers)) {
            return response()->json(['error' => __('unauthorized provider')], 422);
        }
    }



    public function users(Request $request)
    {
        return UserResource::collection(User::paginate($request->limit));
    }


    public function admins(Request $request)
    {
        return UserResource::collection(User::where('is_admin', 1)->paginate($request->limit));
    }

    public function store(Request $request)
    {
        $this->wantJson();

        $data = $request->all();

        if (Route::is('admin.create')) {
            $data['isAdmin'] = true;
        }

        Validator::make($data, array_merge($this->validationRules, [
            'password' => ['required', 'min:6']
        ]))->validate();

        $user = User::create([
            'name' => $data['name'],
            'surname' => $data['surname'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'],
            'is_admin' => isset($data['isAdmin']) ? 1 : 0,
        ]);

        $token = $this->createUserAccessTooken($user);

        return response()->json([
            'user' => $user,
            'access-token' => $token
        ]);
    }

    public function show($user_id)
    {
        $user = User::findOrFail($user_id);

        return  response()->json([
            'user' => $user
        ]);
    }

    public function update(Request $request, $user_id = null)
    {
        $this->wantJson();

        $data = $request->all();
        if ($user_id) {
            $user = User::findOrFail($user_id);
        } else {
            $user = $request->user();
        }

        Validator::make($data, array_merge($this->validationRules, [
            'username' => ['required', Rule::unique('users', 'username')->ignore($user->id), 'max:30'],
            'phone' => ['required', Rule::unique('users', 'phone')->ignore($user->id), 'max:18'],
            'email' => ['required', 'email:rfc,dns', Rule::unique('users', 'email')->ignore($user->id), 'max:60',],
        ]))->validate();


        $user->name = $data['name'];
        $user->surname = $data['surname'];
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->phone = $data['phone'];

        $user->save();

        return response()->json([
            'user' => $user,
        ]);
    }

    public function login(Request $request)
    {
        $this->wantJson();

        $request->validate([
            $this->username() => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::guard()->attempt($request->only($this->username(), 'password'))) {
            throw ValidationException::withMessages([
                $this->username() => [trans('auth.failed')],
            ]);
        }

        $user = Auth::guard()->user();

        $token = $this->createUserAccessTooken($user);

        return response()->json([
            'user' => $user,
            'access-token' => $token
        ]);
    }

    public function logout()
    {
        return $this->invalidateCurrentAccessToken();
    }

    public function logoutAll(Request $request)
    {
        return $this->invalidateAllUserAccessTokens($request->user());
    }

    public function destroy(Request $request, $user_id)
    {
        $user = User::findOrFail($user_id);
        $user->delete();

        return response()->json([
            'message' => 'success'
        ]);
    }


    public function username()
    {
        return 'email';
    }

    public function createUserAccessTooken(User $user)
    {
        return $user->createToken('auth-token')->plainTextToken;
    }

    public function invalidateAllUserAccessTokens(User $user)
    {
        return $user->tokens()->delete();
    }

    public function invalidateCurrentAccessToken()
    {
        return request()->user()?->currentAccessToken()->delete();
    }
}