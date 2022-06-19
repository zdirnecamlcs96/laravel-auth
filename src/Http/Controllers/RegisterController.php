<?php

namespace Zdirnecamlcs96\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            ...config('authentication.validation.register')
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return new User([
            'name' => $data['name'],
            'email' => $data['email'],
            // 'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return $this->__validationFail($validator);
        }

        if($request->filled('email')){
            $check_email = User::whereEmail($request->get('email'))
                        ->whereHas('contact', function ($query) {
                            $query->whereNotNull('verified_at');
                        })
                        ->exists();
            if($check_email) {
                return $this->__apiFailed(Str::__label('Email_Has_Been_Taken', [], 'call-center'));
            }
        }

        $user = User::create();

        event(new Registered($user));

        $this->guard()->login($user);

        return $this->registered($request, $user)
            ?: ( $this->__expectsJson()
                ? $this->__apiFailed('Register Failed.')
                : redirect($this->redirectPath()) );
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        $accessToken = $tokenResult->accessToken; // Passport
        $token->update([
            "expires_at" => $request->remember_me ? Carbon::now()->addWeeks(1) : $token->expires_at,
            'fcm_token' => $request->get('fcm_token')
        ]);

        return $this->__apiSuccess('Register Successful.', [
            "token" => $accessToken,
            "phone_verified" => $user->contact()->whereNotNull('verified_at')->exists(),
            "own_cars" => $user->own_cars->count() ?? 0
        ]);
    }
}
