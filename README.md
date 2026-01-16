# PHP_Laravel12_Reset_Password_By_Gmail_Using_API

##  Introduction

This project implements a **Password Reset System** using **Laravel 12** with **Gmail SMTP integration**.  
It supports both:

1. **API-based workflow** for mobile apps or Postman testing  
2. **Web-based workflow** with a reset password page and success messages  

Users can request a password reset by email, receive a link with a **reset button**, and update their password securely.

---

## Project Overview

**Features:**
- User registration and login (API-based)
- Forgot password via email (Gmail)
- Password reset via:
  - API (`/api/reset-password`)  
  - Web (`/reset-password`) with a Blade form and success message
- Token-based password reset with expiration
- Bootstrap 5.3 responsive design for reset page

**Tech Stack:**
- Laravel 12
- PHP 8.2+
- MySQL
- Blade Templates
- Tailwind / Bootstrap 5.3 (for web page)
- Gmail SMTP for email

---

## Project Name

```
PHP_Laravel12_Reset_Password_By_Gmail_Using_API
```

---

##  Step 1: Create Laravel 12 Project

```bash
composer create-project laravel/laravel PHP_Laravel12_Reset_Password_By_Gmail_Using_API "12.*"
cd PHP_Laravel12_Reset_Password_By_Gmail_Using_API
```

Start development server:

```bash
php artisan serve
```

---

##  Step 2: Database Configuration

Update `.env` file:

```env
DB_DATABASE=reset_password_gmail_api
DB_USERNAME=root
DB_PASSWORD=
```

Create database manually in MySQL:

```sql
CREATE DATABASE reset_password_gmail_api;
```

Otherwise run this command to create database:

```bash
php artisan migrate
```

---

##  Step 3: Gmail SMTP Configuration

Enable **2-Step Verification** in Gmail
Generate **App Password**

Update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=yourgmail@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=yourgmail@gmail.com
MAIL_FROM_NAME="Laravel12 Reset Password API"
```

---


##  Step 4: User Table (Default)

Already have password_reset_tokens table in this default User table

File: database/migrations/xxxx_xx_xx_000000_create_users_table.php 

Migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
```

Run:

```bash
php artisan migrate
```

---


##  Step 5: User Model (Default)

Laravel already provides `User` model.
Ensure password is **hashed**:

File: app/Models/User.php

```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

---

##  Step 6: API Controllers


### AuthController.php

```bash
php artisan make:controller API/AuthController
```

File:

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:6',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User registered successfully'
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        return response()->json([
            'status' => true,
            'message' => 'Login successful'
        ]);
    }
}
```

### PasswordResetController.php


```bash
php artisan make:controller API/PasswordResetController
```

Responsibilities:

* Reset button
* Verify token
* Reset password

File: app/Http/Controllers/API/PasswordResetController.php

```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    // SEND RESET TOKEN
    public function forgotPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'User not found'
        ]);
    }

    $token = Str::random(64);

    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $request->email],
        [
            'token' => $token,
            'created_at' => Carbon::now()
        ]
    );

    $resetLink = url('/reset-password?token='.$token.'&email='.$request->email);

    Mail::send([], [], function ($message) use ($request, $resetLink) {
        $message->to($request->email)
            ->subject('Reset Password')
            ->html("
                <p>Click the button below to reset your password:</p>
                <a href='$resetLink' 
                   style='padding:10px 20px;background:#2563eb;color:white;text-decoration:none;border-radius:5px;'>
                   Reset Password
                </a>
            ");
    });

    return response()->json([
        'status' => true,
        'message' => 'Reset password link sent to email'
    ]);
}

   
    // RESET PASSWORD
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|confirmed|min:6'
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$record) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired token'
            ]);
        }

        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

            //  THIS IS THE KEY LINE
    return back()->with('success', 'Password reset successful');
    }
}
```

---


##  Step 7: API Routes

`routes/api.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PasswordResetController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
```

---


## Step 8 : app.php

Create a file (or edit if it exists) in bootstrap/app.php and add this:

File : bootstrap/app.php

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // add this api route file
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

---

##  Step 9: Web Routes

File: routes/web.php

```php
<?php

use Illuminate\Support\Facades\Route;
Use App\Http\Controllers\API\PasswordResetController;
use Illuminate\Http\Request;

// GET route to open reset password page from email
Route::get('/reset-password', function (Request $request) {
    return view('reset-password', [
        'email' => $request->email,
        'token' => $request->token,
    ]);
});

// POST route to submit the form and show success message
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::get('/', function () {
    return view('welcome');
});
```

---

Step 10: Blade View for Password Reset

File: resources/views/reset-password.blade.php

```html
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            border-radius: 1rem;
        }

        .form-control {
            border-radius: 0.5rem;
        }

        .btn-primary {
            border-radius: 0.5rem;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-body p-4">

                        <h3 class="text-center mb-4 fw-bold">Reset Password</h3>

                        <!-- Success Message -->
                        @if(session('success'))
                        <div class="alert alert-success text-center">
                            {{ session('success') }}
                        </div>
                        @endif

                        <!-- Error Messages -->
                        @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <form method="POST" action="/reset-password">
                            @csrf

                            <input type="hidden" name="email" value="{{ request('email') }}">
                            <input type="hidden" name="token" value="{{ request('token') }}">

                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="password" class="form-control"
                                    placeholder="Enter new password" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-control"
                                    placeholder="Confirm new password" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Reset Password
                            </button>
                        </form>

                    </div>
                </div>

                <p class="text-center text-white mt-3 small">
                    © {{ date('Y') }} PHP Laravel 12
                </p>
            </div>
        </div>
    </div>

</body>

</html>
```

---

Step 11: Start Development Server

Before testing APIs or web pages, you need to start the Laravel development server.

```bash
php artisan serve
```

This will start the server at:

```bash
http://127.0.0.1:8000
```

Now your API and web routes are accessible.
---


Step 12: Postman Testing Example

You can now test all APIs using Postman, including forgot-password and reset-password with the reset button.

### Register User

Method: POST

URL: 

```
http://127.0.0.1:8000/api/register
```

Body (JSON):

```
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Expected Response:

```
{
  "status": true,
  "message": "User registered successfully"
}
```
### Login User

Method: POST

URL:

```
 http://127.0.0.1:8000/api/login
```

Body (JSON):

```
{
  "email": "john@example.com",
  "password": "password123"
}
```

Expected Response:

```
{
  "status": true,
  "message": "Login successful"
}
```

### Forgot Password (Send Reset Email)

Method: POST

URL: 

```
http://127.0.0.1:8000/api/forgot-password
```

Body (JSON):

```
{
  "email": "john@example.com"
}
```

Expected Response:

```
{
  "status": true,
  "message": "Reset password link sent to email"
}
```

Step to simulate the Reset Password button:

Open Gmail inbox for john@example.com.

Open the email sent by Laravel — it contains a Reset Password button.

After click it opens the another page with New Password and Confirm Password as per below link.

Copy the link from the button, e.g.:

```
http://127.0.0.1:8000/reset-password?token=6f3d2a1b...&email=john@example.com
```

### To Verify Login User With New Password

Method: POST

URL:

```
 http://127.0.0.1:8000/api/login
```

Body (JSON):

```
{
  "email": "john@example.com",
  "password": "newpassword"
}
```

---

## Project Structure

```
PHP_Laravel12_Reset_Password_By_Gmail_Using_API/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │       └── API/
│   │           ├── AuthController.php
│   │           └── PasswordResetController.php
│   │
│   ├── Models/
│   │   └── User.php
│
├── bootstrap/
│   └── app.php                                        # Bootstrap application file
│
├── database/
│   ├── migrations/
│   │   ├── 2014_10_12_000000_create_users_table.php   # default users table already have password_reset_tokens
│   │      
│
├── resources/
│   └── views/
│       └── reset-password.blade.php                   # Blade view for web reset
│
├── routes/
│   ├── api.php                                        # API routes
│   └── web.php                                        # Web routes for Blade form
│
├── config/
│   └── mail.php                                       # Mail configuration (Gmail SMTP)
│
├── .env                                              # Environment variables
├── README.md                                        # Project explanation & instructions
└── composer.json                                     # Laravel dependencies
```

## Output:

### Register

<img width="1384" height="998" alt="Screenshot 2026-01-16 130514" src="https://github.com/user-attachments/assets/10581219-eb47-4e78-886b-ba81bfe0051e" />

### Login

<img width="1380" height="997" alt="Screenshot 2026-01-16 130618" src="https://github.com/user-attachments/assets/9e21d93a-8140-456e-bcc1-9f46d9cba145" />

### Forgot Password

<img width="1384" height="1006" alt="Screenshot 2026-01-16 130705" src="https://github.com/user-attachments/assets/48f1b3f9-51b9-4647-bcdf-4c7459ece53f" />

### Reset Password Via Button in Gmail

<img width="1164" height="840" alt="Screenshot 2026-01-16 130724" src="https://github.com/user-attachments/assets/642063dd-2ad0-49ac-ac5b-2157367ea726" />

<img width="1919" height="1032" alt="Screenshot 2026-01-16 130749" src="https://github.com/user-attachments/assets/0b267743-66cb-4821-af3c-4ffffc2ad823" />

<img width="1919" height="1038" alt="Screenshot 2026-01-16 130805" src="https://github.com/user-attachments/assets/2074a1b1-5295-469e-8539-486805a44435" />

### To Verify Login User With New Password

<img width="1371" height="989" alt="Screenshot 2026-01-16 130843" src="https://github.com/user-attachments/assets/80919227-8918-4595-a50e-eb3df8f05445" />

---

Your PHP_Laravel12_Reset_Password_By_Gmail_Using_API Project is now Ready!
