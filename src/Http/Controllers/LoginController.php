<?php

namespace Zdirnecamlcs96\Auth\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Validation\ValidationException;
use Zdirnecamlcs96\Auth\Models\SocialIdentity;
use Zdirnecamlcs96\Auth\Contracts\ShouldAuthenticate;
use Illuminate\Support\Str;


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
        $user = $this->attemptLogin($request);

        if ($user) {
            return $this->sendLoginResponse($user, $request);
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
            ...config('authentication.rules.login')
        ], [
            ...config('authentication.messages.login')
        ]);
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\User|null
     */
    protected function attemptLogin(Request $request)
    {
        $class = config("authentication.models.user");

        $user = $class::where($this->username(), $request->get($this->username()))->first();

        if (!empty($user)) {
            $hashCheck = Hash::check($request->get('password'), $user->password);

            if ($hashCheck) {
                return $user;
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
        return Auth::guard();
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
     * @param  \App\Models\User  $user
     * @return \App\Http\Resources\ApiResource
     */
    protected function sendLoginResponse($user, $request)
    {
        /**
         * Revoke all personal access token
         */
        $user?->tokens()
            ->update([
                'revoked_at' => Carbon::now()
            ]);

        match (config('authentication.mode')) {
            ShouldAuthenticate::PASSPORT => $user->revokePassportAccessTokens(),
            ShouldAuthenticate::SANCTUM => $user->tokens()->delete()
        };

        /**
         * Create new personal access token
         */
        list('token_record' => $tokenDB, 'access_token' => $accessToken) = $user->generateAccessToken();

        /**
         * Update FCM Token & Device Type
         */
        $tokenDB->update([
            'fcm_token' => $request->get('fcm_token'),
            'device_type' => $request->get('device_type')
        ]);

        return $this->__apiSuccess(__('authentication::auth.login.success'), [
            "access_token" => $accessToken,
            "fcm_token" => $tokenDB->fcm_token,
            "device_type" => $tokenDB->device_type
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

        $user = $request->user();

        $success = match (config('authentication.mode')) {
            ShouldAuthenticate::PASSPORT => $user->token()->revoke(),
            ShouldAuthenticate::SANCTUM => $user->currentAccessToken()->delete(),
            default => false
        };

        if ($success) {
            return $this->__apiSuccess(__('authentication::auth.logout.success'));
        }

        return $this->__apiFailed(__('authentication::auth.logout.failed'));
    }

    /**
     * Request Third Party Login Redirect URL
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function thirdPartyLogin(Request $request)
    {
        $rules = [
            "third_party_type" => "required|string|in:google,facebook,apple",
            "mode" => "nullable|string"
        ];

        $type = $request->get('third_party_type');

        $provider = ucfirst($type);

        $this->validate($request, $rules);

        return $this->__apiSuccess("Redirecting to {$provider}...", [
            "url" => Socialite::driver($type)
                        ->stateless()
                        ->redirect()
                        ->getTargetUrl()
        ]);
    }

    /**
     * Third Party Login Callback
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed $provider
     * @return void
     */
    public function thirdPartyLoginCallback(Request $request, $provider)
    {

        $data = [];

        if($request->has('error')) {
            $err_reason = ucfirst($provider) . " login failed. (" . ucwords(mb_ereg_replace('_', ' ', $request->get('error_reason'))) . ")";
            $data = [
                "tpl_success" => $provider,
                "error" => $err_reason
            ];

        }else {
            // Log::info("Processing $provider login...");

            $custom = [];

            $socialite = Socialite::driver($provider)->stateless()->user();

            // Log::info("Fetched $provider user.");

            if(!empty($socialite))
            {
                $id = $socialite->id;
                $avatar = $socialite->avatar;
                $email = $socialite->email;
                $username = $this->__isEmpty($socialite->name, 'User' . rand('00000000', '99999999'));

                $account = SocialIdentity::whereProviderName($provider)
                                ->whereProviderId($id)
                                ->first();

                if (empty($account)) {
                    $account = new SocialIdentity([
                        "provider_id" => $id,
                        "provider_name" => $provider
                    ]);
                    $account->save();
                }

                $account->update([
                    "token" => Str::uuid(32),
                    "token_expired_at" => Carbon::now()->addMinutes(5)
                ]);

                $data = [
                    "tpl_success" => $provider,
                    "uid" => $id,
                    "avatar" => $avatar,
                    "email" => $email,
                    "username" => $username
                ];

            }
        }

        $url = config('authentication.third_party.app_login_url');

        return redirect()->to($url . http_build_query($data));

    }
}
