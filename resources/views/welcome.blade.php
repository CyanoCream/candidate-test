<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>CLT Manager</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { font-family: 'Figtree', sans-serif; }
            .bg-overlay { background: rgba(0, 0, 0, 0.4); }
            .animate-fade-in { animation: fadeIn 1s ease-out; }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>
    </head>
    <body class="antialiased">
        <div class="position-relative vh-100 overflow-hidden bg-dark">
            <img src="https://app.clttoolbox.com.au/images/login-bg.jpg" class="position-absolute w-100 h-100 object-fit-cover" alt="Background">
            <div class="position-absolute top-0 start-0 w-100 h-100 bg-overlay"></div>
            
            <div class="position-relative z-1 d-flex flex-column vh-100">
                <header class="p-4 d-flex justify-content-end">
                    @if (Route::has('login'))
                        <div class="animate-fade-in">
                            @auth
                                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-link text-white text-decoration-none p-0">Logout</button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-outline-light px-4">Log in</a>
                            @endauth
                        </div>
                    @endif
                </header>

                <main class="flex-grow-1 d-flex flex-column align-items-center justify-content-center text-center px-4">
                    <img src="https://app.clttoolbox.com.au/images/logos/logo_color_white.png" alt="Logo" class="mb-5 animate-fade-in" style="max-width: 300px;">
                    
                    <div class="animate-fade-in">
                        @auth
                            <a href="{{ route('suppliers.index') }}" class="btn btn-danger btn-lg px-5 py-3 fw-bold">
                                Go to Suppliers Management
                            </a>
                        @endauth
                    </div>
                </main>

                <footer class="p-4 text-center text-white-50">
                    &copy; {{ date('Y') }} CLT Manager. Built with Laravel.
                </footer>
            </div>
        </div>
    </body>
</html>
