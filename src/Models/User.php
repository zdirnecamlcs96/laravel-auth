<?php

namespace Zdirnecamlcs96\Auth\Models;

use Laravel\Sanctum\NewAccessToken;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Passport\PersonalAccessTokenResult;
use Zdirnecamlcs96\Auth\Contracts\ShouldAuthenticate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements ShouldAuthenticate
{
    use HasFactory, Notifiable, SoftDeletes;

    const TOKEN_NAME = "Authetication Token";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Extra register fields
     *
     * @return void
     */
    public function registerFields()
    {
        return [];
    }

    /**
     * generateAccessToken
     *
     * @return array
     */
    public function generateAccessToken()
    {
        $token = $this->createToken(self::TOKEN_NAME);

        $tokenDB = $accessToken = null;

        if (class_exists(NewAccessToken::class) && is_a($token, NewAccessToken::class)) {
            $tokenDB = $token->accessToken;
            $accessToken = $token->plainTextToken;
        }

        if (class_exists(PersonalAccessTokenResult::class) && is_a($token, PersonalAccessTokenResult::class)) {
            $tokenDB = $token->token;
            $accessToken = $token->accessToken;
        }

        return [
            "token_record" => $tokenDB,
            "access_token" => $accessToken
        ];
    }

    public function revokePassportAccessTokens($except = null)
    {
        // Passport
        foreach ($this->tokens()->where('name', self::TOKEN_NAME)->get() as $token) {
            $token->revoke();
        }
    }
}
