<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Address;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
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

    protected $userValidationRules = [
        'name' => ['required', 'max:60'],
        'surname' => ['required', 'max:60'],
        'username' => ['required', 'unique:users', 'max:30'],
        'email' => ['required', 'email:rfc,dns', 'unique:users', 'max:60',],
        'phone' => ['required', 'unique:users', 'max:18'],
    ];

    protected $addressValidationRules = [
        'state' => ['required', 'string', 'max:60'],
        'city' => ['required', 'string', 'max:60'],
        'street_1' => ['required', 'string', 'max:190'],
        'street_2' => ['nullable', 'string', 'max:190'],
        'postal_code' => ['required', 'string', 'max:60'],
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

        Validator::make($data, array_merge($this->userValidationRules, [
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

        event(new Registered($user));

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
            Gate::authorize('admin');
            $user = User::findOrFail($user_id);
        } else {
            $user = $request->user();
        }

        Validator::make($data, array_merge($this->userValidationRules, [
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

    public function updatePassword(Request $request, $user_id = null)
    {
        $this->wantJson();

        $data = $request->all();
        if ($user_id) {
            Gate::authorize('admin');
            $user = User::findOrFail($user_id);
        } else {
            $user = $request->user();
        }

        Validator::make($data, [
            'password' => ['required', 'min:6', 'max:120'],
        ])->validate();


        $user->password = Hash::make($data['password']);
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


    public function setAddress(Request $request)
    {
        $user = Auth::guard()->user();
        $data = $request->all();

        Validator::make($data, array_merge($this->addressValidationRules, []))->validate();

        $address = Address::updateOrCreate([
            "user_id" => $user->id,
        ], [
            "state" => $request->state,
            "city" => $request->city,
            "street_1" => $request->street_1,
            "street_2" => $request->street_2,
            "postal_code" => $request->postal_code,
        ]);

        return  response()->json([
            'address' => $address
        ]);
    }


    // Email verification 
    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function verify(Request $request)
    {
        $user = User::find($request->id);
        if (!hash_equals((string) $request->hash, sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException;
        }
        
        if ($user->hasVerifiedEmail()) {
            return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect($this->verifiedRedirectPath());
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect($this->verifiedRedirectPath())->with('verified', true);
    }
    
    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $request->wantsJson()
                        ? new JsonResponse([], 204)
                        : redirect($this->verifiedRedirectPath());
        }
        $request->user()->sendEmailVerificationNotification();

        return $request->wantsJson()
                    ? new JsonResponse([], 202)
                    : back()->with('resent', true);
    }

    public function verifiedRedirectPath()
    {
        return env('FRONT_URL') . '/email/verify/success';
    }
}
