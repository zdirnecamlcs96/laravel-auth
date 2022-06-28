<?php

namespace Zdirnecamlcs96\Auth\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

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

        if($request->filled('email'))
        {
            $class = config("authentication.models.user");

            $check_email = $class::whereEmail($request->get('email'))
                        ->when(method_exists($class, 'contact'), fn($query) =>
                            $query->whereHas('contact', fn($query) => $query->whereNotNull('verified_at'))
                        )
                        ->exists();

            if($check_email) {
                return $this->__apiFailed(Str::__label('Email_Has_Been_Taken', [], 'call-center'));
            }
        }

        $data = array_intersect_key($request->all(), array_flip(array_keys(config('authentication.validation.register'))));

        $data['password'] = bcrypt($request->get('password'));

        $user = $class::create($data);

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
        /**
         * Create new personal access token
         */
        list('token_record' => $tokenDB, 'access_token' => $accessToken) = $user->generateAccessToken();

        $tokenDB->update([
            "expires_at" => $request->remember_me ? Carbon::now()->addWeeks(1) : $tokenDB->expires_at,
            'fcm_token' => $request->get('fcm_token')
        ]);

        $data = [
            "token" => $accessToken,
        ];

        $class = config("authentication.models.user");

        if (method_exists($class, 'contact')) {
            $data = array_merge($data, ["phone_verified" => $user->contactVerified()]);
        }

        return $this->__apiSuccess(__('authentication::auth.register.success'), $data);
    }
}
