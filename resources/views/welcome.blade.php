<!DOCTYPE html>
<html lang="id">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@appName - Sistem Pembayaran Akademik</title>
    <meta name="description" content="Sistem Pembayaran Akademik Yayasan Mu'allimin Mu'allimat YASMU - Solusi terpadu untuk manajemen keuangan pendidikan">
    
    <!-- Favicon -->
    @php
        $institution = \App\Models\Institution::first();
        $faviconUrl = $institution && $institution->logo ? asset('storage/'.$institution->logo) : asset('favicon.ico');
    @endphp
    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ $faviconUrl }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --text-dark: #1f2937;
            --white: #ffffff;
        }
        * { box-sizing: border-box; }
            body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
            background: linear-gradient(135deg, #1e4d2b 0%, #ffffff 100%);
            min-height: 100vh;
        }
        /* Sticky login bar */
        .login-sticky {
            position: sticky;
            top: 0;
            z-index: 1030;
            backdrop-filter: saturate(180%) blur(6px);
            background: rgba(255,255,255,0.12);
        }
        .login-sticky .btn-login {
            background: var(--white);
            color: var(--primary-color);
            border: 1px solid rgba(37,99,235,0.25);
            font-weight: 600;
        }
        .login-sticky .btn-login:hover { color: var(--white); background: var(--primary-color); }

        .hero-section { padding: 90px 0 40px; color: var(--white); text-align: center; }
        .hero-title { font-size: 3.25rem; font-weight: 800; margin-bottom: 1rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.25); }
        .hero-subtitle { font-size: 1.15rem; font-weight: 300; margin-bottom: 2rem; opacity: 0.95; }

        .features-section { padding: 60px 0 40px; background: var(--white); }
        .feature-card { text-align: center; padding: 2rem; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: transform 0.25s ease, box-shadow 0.25s ease; background: var(--white); height: 100%; }
        .feature-card:hover { transform: translateY(-8px); box-shadow: 0 18px 36px rgba(0,0,0,0.12); }
        .feature-icon { font-size: 2.5rem; color: var(--primary-color); margin-bottom: 0.75rem; }

        .stats-section { padding: 50px 0; background: var(--primary-color); color: var(--white); }
        .stat-item { text-align: center; padding: 1rem; }
        .stat-number { font-size: 2.1rem; font-weight: 800; margin-bottom: 0.35rem; }
        .stat-label { font-size: 0.95rem; opacity: 0.95; }

        /* Modal styles */
        .app-logo { width: 72px; height: 72px; border-radius: 18px; display: flex; align-items: center; justify-content: center; background: var(--primary-color); color: var(--white); font-size: 1.5rem; font-weight: 700; overflow: hidden; }
        .app-logo img { width: 100%; height: 100%; object-fit: cover; }
        .form-control { border: 2px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; font-size: 1rem; }
        .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.2rem rgba(37,99,235,0.18); }
        .btn-primary { background: var(--primary-color); border: none; border-radius: 10px; padding: 12px 22px; font-weight: 600; }
        .btn-primary:hover { background: var(--secondary-color); }

        .footer { background: #0f172a; color: var(--white); text-align: center; padding: 22px 0; }
        @media (max-width: 768px) { .hero-title { font-size: 2.4rem; } }
        </style>
    </head>
<body>
    @php
        $institution = \App\Models\Institution::first();
        $logoUrl = $institution && $institution->logo ? asset('storage/'.$institution->logo) : null;
    @endphp

    

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <h1 class="hero-title">@appName</h1>
                    <p class="hero-subtitle">@appDescription</p>
                    <p class="hero-subtitle">Solusi terpadu untuk manajemen keuangan pendidikan yang modern, aman, dan efisien</p>
                    <div class="mt-3">
                        <button class="btn btn-light fw-semibold" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-user-lock me-2"></i>Masuk ke Sistem
                        </button>
                            </div>
                                </div>
                            </div>
                        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="h1 mb-2">Fitur Unggulan</h2>
                    <p class="text-muted">Platform pembayaran SPP yang dirancang khusus untuk kebutuhan pendidikan</p>
                </div>
                            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                        <h5 class="mb-1">Keamanan Tingkat Tinggi</h5>
                        <p class="text-muted mb-0">2FA, audit trail, dan enkripsi data untuk keamanan maksimal</p>
                                </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                        <h5 class="mb-1">Laporan Real-time</h5>
                        <p class="text-muted mb-0">Dashboard dengan statistik keuangan yang akurat dan up-to-date</p>
                            </div>
                        </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                        <h5 class="mb-1">PWA & Mobile Ready</h5>
                        <p class="text-muted mb-0">Aplikasi siap diinstall pada perangkat mobile dengan fitur offline</p>
                            </div>
                                </div>
                            </div>
                        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3"><div class="stat-item"><div class="stat-number">100%</div><div class="stat-label">Keamanan Data</div></div></div>
                <div class="col-md-3"><div class="stat-item"><div class="stat-number">24/7</div><div class="stat-label">Monitoring</div></div></div>
                <div class="col-md-3"><div class="stat-item"><div class="stat-number">99.9%</div><div class="stat-label">Uptime</div></div></div>
                <div class="col-md-3"><div class="stat-item"><div class="stat-number">Real-time</div><div class="stat-label">Sinkronisasi</div></div></div>
            </div>
        </div>
    </section>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pt-4 pb-0 justify-content-center">
                    <div class="app-logo">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Logo Lembaga" />
                        @else
                            <i class="fas fa-graduation-cap"></i>
                        @endif
                    </div>
                </div>
                <div class="modal-body px-4 pb-4">
                                            <h5 class="text-center mb-3 fw-bold">Login @appName</h5>
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                            @error('email')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="current-password">
                            @error('password')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">Remember Me</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                    </div>
                    </form>
                    <div class="text-center mt-3">
                        @if (Route::has('password.request'))
                            <a class="btn btn-link" href="{{ route('password.request') }}">Lupa Password?</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
                            <p class="mb-0">&copy; {{ date('Y') }} @appName. Semua hak dilindungi.</p>
                            <small class="opacity-75">@appDescription</small>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- PWA Service Worker (opsional) -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js').catch(() => {});
            });
        }
    </script>
    </body>
</html>
