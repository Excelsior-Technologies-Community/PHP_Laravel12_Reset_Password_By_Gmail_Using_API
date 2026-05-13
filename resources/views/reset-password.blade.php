<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set New Password | Laravel 12</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border: none;
            border-radius: 1.25rem;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid #dee2e6;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(78, 84, 200, 0.25);
            border-color: #4e54c8;
        }

        .btn-primary {
            background: #4e54c8;
            border: none;
            padding: 0.75rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: #3a3f9a;
        }

        .input-group-text {
            background: transparent;
            border-radius: 0.75rem 0 0 0.75rem;
            border-right: none;
        }

        .form-control {
            border-left: none;
        }

        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: #4e54c8;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg overflow-hidden">
                    <div class="card-body p-4 p-sm-5">
                        <div class="text-center mb-4">
                            <div class="bg-primary bg-opacity-10 d-inline-block p-3 rounded-circle mb-3">
                                <i class="fa-solid fa-lock-open fa-2x text-primary"></i>
                            </div>
                            <h3 class="fw-bold text-dark">New Password</h3>
                            <p class="text-muted small">Enter your new credentials below</p>
                        </div>

                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        @if($errors->any())
                        <div class="alert alert-danger border-0 shadow-sm">
                            <ul class="mb-0 small">
                                @foreach($errors->all() as $error)
                                <li><i class="fa-solid fa-circle-exclamation me-2"></i>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <form method="POST" action="/reset-password">
                            @csrf

                            <input type="hidden" name="email" value="{{ request('email') }}">
                            <input type="hidden" name="token" value="{{ request('token') }}">

                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-bold">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text text-muted">
                                        <i class="fa-solid fa-key"></i>
                                    </span>
                                    <input type="password" name="password" 
                                        class="form-control" placeholder="••••••••" required autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-secondary small fw-bold">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text text-muted">
                                        <i class="fa-solid fa-shield-halved"></i>
                                    </span>
                                    <input type="password" name="password_confirmation" 
                                        class="form-control" placeholder="••••••••" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 shadow-sm">
                                <i class="fa-solid fa-rotate me-2"></i>Update Password
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <a href="/" class="text-decoration-none small text-muted">
                                <i class="fa-solid fa-arrow-left me-1"></i> Back to Login
                            </a>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4 text-white opacity-75 small">
                    &copy; {{ date('Y') }} <strong>Laravel 12</strong> API Security.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>