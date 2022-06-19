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
            "name" => "required|string",
            "contact" => "required|string"
        ]);

        $this->__currentUser()->update([
            "name" => $request->get('name'),
            "contact" => $request->get('contact')
        ]);

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
            "old_password" => "required|string",
            "new_password" => "required|string|confirmed|min:8",
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
