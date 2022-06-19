# Simple Auth for Laravel

We're using [Laravel Passport](https://laravel.com/docs/9.x/passport) or [Laravel Sanctum](https://laravel.com/docs/9.x/sanctum) for api authetication.

---

## Requirement:
1. Laravel 9 and above
2. Choose either `Laravel Passport` or `Laravel Sanctum`

---

## Setup
1. First, run `php artisan migrate` to setup migration in database
2. If you're using Laravel Passport, please run `php artisan passport:install` to install oauth client key
3. Extend use `Zdirnecamlcs96\Auth\Models\User as Base`
4. Import either one of the Trait in `app/Models/User.php` file
```
use Laravel\Passport\HasApiTokens;
use Laravel\Sanctum\HasApiTokens;
```
5. You may publish the `config/authetication.php` and modify the `mode`.
6. All done. You may test your auth with [Postman](https://www.postman.com/).
