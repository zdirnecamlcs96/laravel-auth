<?php

namespace Zdirnecamlcs96\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Zdirnecamlcs96\Auth\Http\Resources\Resource;
use Carbon\Carbon;
use Hash;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        return $this->__apiSuccess(__('authentication::profile.show'), new Resource($this->__currentUser()));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            ...config('authentication.rules.account.update')
        ]);

        $data = array_intersect_key($request->all(), array_flip(array_keys(config('authentication.rules.account.update'))));

        $this->__currentUser()->update($data);

        return $this->__apiSuccess(__('authentication::profile.update'));
    }

    /**
     * Change account password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function changePassword(Request $request)
    {
        $this->validate($request, [
            ...config('authentication.rules.password.change')
        ]);

        $user = $this->__currentUser();

        if (!Hash::check($request->get('old_password'), $user->password))
        {
            return $this->__apiFailed(__('authentication::profile.old_password_invalid'));
        }

        $user->update([
            "password" => Hash::make($request->get('new_password'))
        ]);

        return $this->__apiSuccess(__('authentication::profile.change_password_success'));
    }

    /**
     * Delete account
     *
     * Due to Apple June 30 policy, IOS need to handle delete personal data function
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        $user = $this->__currentUser();

        $user->update([
            "suspended_at" => Carbon::now()
        ]);

        $user->token()->revoke();

        return $this->__apiSuccess(__('authentication::profile.destroy'));
    }
}
