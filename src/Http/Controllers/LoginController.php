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
use Zdirnecamlcs96\Auth\Models\Media;


class LoginController extends Controller
{

    private function getUserClass()
    {
        return config("authentication.models.user");
    }

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

        if ($request->filled('third_party_type')) {
            $user = $this->thirdPartyLogin($request);
        } else {
            $user = $this->attemptLogin($request);
        }

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

        $user = $this->getUserClass()::where($this->username(), $request->get($this->username()))->first();

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
            $this->username() => [ __($request->filled('third_party_type') === "web" ? 'auth.third_party_expired' : 'auth.failed') ],
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
     * Attempt to log the user into the application with social login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function thirdPartyLogin(Request $request)
    {
        $device_type = $request->get('device_type');
        $email = $request->get('email');
        $provider_id = $request->get('third_party_id');
        $provider_name = $request->get('third_party_type'); // tpl_provider
        $tpl_token = $request->get('third_party_token');
        $provider_image = $request->get('third_party_image');
        $username = $this->__requestFilled('third_party_username', 'User' . rand('00000000', '99999999'));

        $user = null;
        $isWeb = $device_type === "web";

        $socialIdentity = SocialIdentity::whereProviderName($provider_name)
            ->whereProviderId($provider_id)
            ->when($isWeb, fn($query) => $query->whereNotNull('token')->whereToken($tpl_token))
            ->first();

        if(empty($socialIdentity)) {

            if($isWeb) {
                return false;
            }

            $socialIdentity = SocialIdentity::create([
                "provider_id" => $provider_id,
                "provider_name" => $provider_name,
            ]);
        }

        // Check if token expired
        if ($isWeb && Carbon::now()->isAfter($socialIdentity->token_expired_at)) {
            return false;
        }

        $user = $socialIdentity->user;

        // Create user account if not found
        if (!$user) {
            $user = $this->getUserClass()::create([
                'email' => $email,
                config('authentication.third_party.username')  => $username,
                'password' => bcrypt(Str::random(16))
            ]);

            $socialIdentity->user()->associate($user);
            $socialIdentity->save();
        }

        if (!$user->{config('authentication.third_party.username')})
        {
            $user->update([
                config('authentication.third_party.username')  => $username,
            ]);
        }

        // Reset social token once login success
        $socialIdentity->update([
            "token" => null
        ]);

        // Upload profile image
        // if ($user->profileImage()->doesntExist() && $request->filled('third_party_image')) {
        //     $file = $this->__storeImage($provider_image, Media::PROFILE_IMAGE);

        //     $user->profileImage()->create([
        //         "name" => $file->filename,
        //         "original_name" => $provider_image,
        //         "extension" => $file->extension,
        //         "mime" => $file->mime(),
        //         "size" => $file->filesize(),
        //         "path" => Media::PATH_TO_STORAGE,
        //         "ip_address" => $request->ip()
        //     ]);
        // }

        return $user;
    }

    /**
     * Request Third Party Login Redirect URL
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function statelessThirdParty(Request $request)
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
    public function statelessThirdPartyCallback(Request $request, $provider)
    {

        $data = [];

        if($request->has('error')) {
            $err_reason = ucfirst($provider) . " login failed. (" . ucwords(mb_ereg_replace('_', ' ', $request->get('error_reason'))) . ")";
            $data = [
                "tpl_provider" => $provider,
                "tpl_error" => $err_reason
            ];

        }else {
            try {
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

                    $socialIdentity = SocialIdentity::updateOrCreate([
                        "provider_id" => $id,
                        "provider_name" => $provider
                    ],[
                        "token" => Str::uuid(32),
                        "token_expired_at" => Carbon::now()->addMinutes(5)
                    ]);

                    $data = [
                        "tpl_provider" => $provider,
                        "tpl_token" => $socialIdentity->token?->toString(),
                        "uid" => $id,
                        "avatar" => $avatar,
                        "email" => $email,
                        "username" => $username,
                    ];
                }
            } catch (\Throwable $th) {
                $data = [
                    "tpl_provider" => $provider,
                    "tpl_error" => ucfirst($provider) . " login error. Please try again later."
                ];
            }
        }

        $url = config('authentication.third_party.app_login_url');

        return redirect()->to($url ."?". http_build_query($data));

    }
}
