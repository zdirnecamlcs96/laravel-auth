<?php

namespace Zdirnecamlcs96\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\ApiResource
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);
        $driver = $this->attemptLogin($request);

        if ($driver) {
            return $this->sendLoginResponse($driver, $request);
        }

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
            ...config('laravel_auth.fields')
        ]);
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\Driver|null
     */
    protected function attemptLogin(Request $request)
    {
        $driver = Driver::where($this->username(), $request->get($this->username()))
            ->whereNull('suspended_at')
            ->first();

        if (empty($driver)) {
            return false;
        }

        $user = User::role(Role::DRIVER)->find($driver->id);

        if (!empty($user)) {
            $hashCheck = Hash::check($request->get('password'), $driver->password);

            if ($hashCheck) {
                return $driver;
            }
        }

        return false;
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'password');
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Auth\RequestGuard
     */
    protected function guard()
    {
        return Auth::guard(Driver::GUARD);
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \App\Models\Driver  $driver
     * @return \App\Http\Resources\ApiResource
     */
    protected function sendLoginResponse(Driver $driver, $request)
    {
        optional($driver->tokens())->update(['revoked' => 1]);
        $tokenResult = $driver->createToken('Driver App');
        $token = $tokenResult->token;
        $accessToken = $tokenResult->accessToken;

        $token->update([
            'fcm_token' => $request->get('fcm_token'),
            'device_type' => $request->get('device_type')
        ]);

        return $this->__apiSuccess('Login sucessful.', [
            "access_token" => $accessToken,
            "fcm_token" => $token->fcm_token,
            "device_type" => $token->device_type
        ]);
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'email';
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        if ($request->user()->token()->revoke()) {
            return $this->__apiSuccess('Logout success.');
        }

        return $this->__apiSuccess('Logout failed.');
    }
}
