<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@appName</title>

    <!-- Favicon -->
    @php
        $institution = \App\Models\Institution::first();
        $faviconUrl = $institution && $institution->logo ? Storage::url($institution->logo) : asset('favicon.ico');
    @endphp
    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ $faviconUrl }}">

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Styles -->
    <style>
        :root {
            --app-primary-color: #2563eb;
            --app-secondary-color: #1e40af;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--app-primary-color) !important;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--app-primary-color) 10%, var(--app-secondary-color) 100%);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            position: fixed;
            top: 0;
            left: -250px;
            z-index: 1000;
            width: 250px;
            transition: left 0.3s ease;
        }
        
        /* Responsive sidebar behavior */
        @media (min-width: 1200px) {
            .sidebar {
                left: 0;
            }
        }
        
        @media (max-width: 1199px) {
            .sidebar {
                left: -250px;
            }
            
            .sidebar.show {
                left: 0;
            }
        }
        
        
        .sidebar.show {
            left: 0;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            border-radius: 0.35rem;
            margin: 0.2rem 0.5rem;
            transition: all 0.2s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link i {
            margin-right: 0.5rem;
            width: 1rem;
        }
        
        /* Collapse menu styling */
        .sidebar .nav-link {
            transition: all 0.3s ease;
            position: relative;
        }
        
        .sidebar .nav-link .fa-chevron-down {
            transition: transform 0.3s ease;
            font-size: 0.8em;
        }
        
        .sidebar .nav-link[data-bs-toggle="collapse"] {
            cursor: pointer;
        }
        
        .sidebar .nav-link[data-bs-toggle="collapse"]:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .collapse .nav-link {
            padding-left: 2rem;
            font-size: 0.9em;
            border-left: 2px solid rgba(255, 255, 255, 0.2);
            margin-left: 0.5rem;
        }
        
        .sidebar .collapse .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.05);
            border-left-color: rgba(255, 255, 255, 0.5);
        }
        
        .sidebar .collapse .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
            border-left-color: #fff;
        }
        
        /* Smooth collapse animation */
        .sidebar .collapse {
            transition: height 0.3s ease;
        }
        
        /* Active state for parent menu */
        .sidebar .nav-link[aria-expanded="true"] {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Footer styling */
        .footer {
            background-color: #fff;
            border-top: 1px solid #e3e6f0;
            padding: 1rem 0;
            margin-top: auto;
            box-shadow: 0 -0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-left: 0;
            transition: margin-left 0.3s;
        }
        
        .footer-content {
            padding: 0.5rem 0;
        }
        
        .footer p {
            color: #5a5c69;
            font-size: 0.9rem;
        }
        
        .footer small {
            color: #858796;
            font-size: 0.8rem;
        }
        
        .footer .text-primary {
            color: var(--app-primary-color) !important;
        }
        
        /* Ensure footer stays at bottom */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .main-content {
            margin-left: 0;
            margin-top: 56px;
            transition: margin-left 0.3s;
            padding: 1rem;
            width: 100%;
            min-height: calc(100vh - 56px);
            flex: 1;
        }
        
        /* Responsive main content */
        @media (min-width: 1200px) {
            .main-content {
                margin-left: 250px;
            }
        }
        
        @media (max-width: 1199px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        .main-content.sidebar-open {
            margin-left: 250px;
        }
        
        .btn-primary {
            background-color: var(--app-primary-color) !important;
            border-color: var(--app-primary-color) !important;
        }
        
        .btn-primary:hover {
            background-color: var(--app-secondary-color) !important;
            border-color: var(--app-secondary-color) !important;
        }
        
        .text-primary {
            color: var(--app-primary-color) !important;
        }
        
        .bg-primary {
            background-color: var(--app-primary-color) !important;
        }
        
        .border-primary {
            border-color: var(--app-primary-color) !important;
        }
        
        .card-header {
            background-color: var(--app-primary-color) !important;
            color: white !important;
        }
        
        .table-dark {
            background-color: var(--app-primary-color) !important;
        }
        
        .badge.bg-primary {
            background-color: var(--app-primary-color) !important;
        }
        
        .badge.bg-secondary {
            background-color: var(--app-secondary-color) !important;
        }
        
        .navbar {
            margin-left: 0;
            width: 100%;
            position: fixed;
            top: 0;
            z-index: 1001;
        }
        
        /* Overlay untuk sidebar */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        @media (min-width: 1200px) {
            .sidebar {
                left: 0;
                position: fixed;
            }
            
            .main-content {
                margin-left: 250px;
                margin-top: 56px;
                padding: 2rem;
                width: calc(100% - 250px);
            }
            
            .navbar {
                margin-left: 250px;
                width: calc(100% - 250px);
            }
            
            .footer {
                margin-left: 250px;
                width: calc(100% - 250px);
            }
            
            .sidebar-overlay {
                display: none;
            }
        }
        
        @media (min-width: 768px) and (max-width: 1199px) {
            .sidebar {
                left: -250px;
                position: fixed;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
                margin-top: 56px;
                padding: 1.5rem;
                min-width: 0;
                width: 100%;
            }
            
            .navbar {
                margin-left: 0;
                width: 100%;
            }
            
            .sidebar-overlay {
                display: block;
            }
            
            /* Tablet-specific adjustments */
            .hero-banner {
                padding: 1.5rem;
            }
            
            .hero-title {
                font-size: 1.8rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .stats-card {
                padding: 1rem;
            }
            
            .stats-card h3 {
                font-size: 1.5rem;
            }
            
            .action-btn {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
            
            .action-btn i {
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 767px) {
            .main-content {
                padding: 0.75rem;
            }
        }
        
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 600;
        }
        
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }
        
        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }
        
        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }
        
        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }
        
        .text-gray-300 {
            color: #dddfeb !important;
        }
        
        .text-gray-800 {
            color: #5a5c69 !important;
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .navbar-toggler {
            border: none;
            padding: 0.25rem 0.5rem;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            color: #4e73df;
            font-size: 1.25rem;
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }
        
        .sidebar-toggle:hover {
            background-color: rgba(78, 115, 223, 0.1);
            color: #224abe;
        }
        
        .sidebar-toggle:focus {
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        /* Hard hide any global carousel/swiper nav controls that may overlay pages */
        .carousel-control-prev,
        .carousel-control-next,
        .carousel-control-prev-icon,
        .carousel-control-next-icon,
        .swiper-button-prev,
        .swiper-button-next {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
            background: none !important;
        }
        /* Also shrink any huge standalone arrow icons if injected */
        .fa-angle-left, .fa-angle-right, .fa-chevron-left, .fa-chevron-right {
            font-size: 1rem !important;
        }
    </style>
</head>
<body>
    <div id="app">
        <!-- Top Navigation -->
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container-fluid">
                <button class="sidebar-toggle d-xl-none me-2" type="button" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                
                <a class="navbar-brand" href="{{ url('/') }}">
                                            <i class="fas fa-graduation-cap me-2"></i>@appName
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle me-1"></i>{{ Auth::user()->name }}
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="{{ route('settings.index') }}">
                                        <i class="fas fa-cog me-2"></i>Pengaturan
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt me-2"></i>{{ __('Logout') }}
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        
        <!-- Sidebar -->
        @auth
        <nav class="sidebar" id="sidebar">
                <div class="p-3">
                    <div class="text-center mb-4">
                        <h6 class="text-white-50">Menu Navigasi</h6>
                    </div>
                    
                    <ul class="nav flex-column">
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                                <i class="fas fa-tachometer-alt"></i>Dashboard
                            </a>
                        </li>
                        
                        @if(Auth::user()->isSuperAdmin())
                        <!-- Master Data -->
                        <li class="nav-item">
                            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#masterData" role="button" aria-expanded="false" aria-controls="masterData">
                                <i class="fas fa-database"></i>Master Data
                                <i class="fas fa-chevron-down ms-auto"></i>
                            </a>
                            <div class="collapse" id="masterData">
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                                            <i class="fas fa-users-cog"></i>Manajemen Pengguna
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('institutions.*') ? 'active' : '' }}" href="{{ route('institutions.index') }}">
                                            <i class="fas fa-building"></i>Lembaga
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('academic-years.*') ? 'active' : '' }}" href="{{ route('academic-years.index') }}">
                                            <i class="fas fa-calendar-alt"></i>Tahun Ajaran
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('classes.*') ? 'active' : '' }}" href="{{ route('classes.index') }}">
                                            <i class="fas fa-chalkboard"></i>Kelas
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('scholarship-categories.*') ? 'active' : '' }}" href="{{ route('scholarship-categories.index') }}">
                                            <i class="fas fa-graduation-cap"></i>Kategori Beasiswa
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        
                        <!-- Data Siswa -->
                        <li class="nav-item">
                            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#studentData" role="button" aria-expanded="false" aria-controls="studentData">
                                <i class="fas fa-users"></i>Data Siswa
                                <i class="fas fa-chevron-down ms-auto"></i>
                            </a>
                            <div class="collapse" id="studentData">
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}" href="{{ route('students.index') }}">
                                            <i class="fas fa-users"></i>Data Siswa
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('student-promotions.*') ? 'active' : '' }}" href="{{ route('student-promotions.index') }}">
                                            <i class="fas fa-arrow-up"></i>Promosi Siswa
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        
                        <!-- Keuangan -->
                        <li class="nav-item">
                            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#financial" role="button" aria-expanded="false" aria-controls="financial">
                                <i class="fas fa-money-bill-wave"></i>Keuangan
                                <i class="fas fa-chevron-down ms-auto"></i>
                            </a>
                            <div class="collapse" id="financial">
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('fee-structures.*') ? 'active' : '' }}" href="{{ route('fee-structures.index') }}">
                                            <i class="fas fa-list-alt"></i>Struktur Biaya
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">
                                            <i class="fas fa-credit-card"></i>Pembayaran
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                                            <i class="fas fa-tags"></i>Kategori Keuangan
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('activity-plans.*') ? 'active' : '' }}" href="{{ route('activity-plans.index') }}">
                                            <i class="fas fa-clipboard-list"></i>Rencana Kegiatan
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('activity-realizations.*') ? 'active' : '' }}" href="{{ route('activity-realizations.index') }}">
                                            <i class="fas fa-chart-pie"></i>Realisasi Kegiatan
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        @else
                        <!-- Staff/Kasir Menu -->
                        @if(in_array(Auth::user()->role, ['staff', 'kasir']))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('staff.students.*') ? 'active' : '' }}" href="{{ route('staff.students.index') }}">
                                <i class="fas fa-users"></i>Data Siswa
                            </a>
                        </li>
                        @endif
                        
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">
                                <i class="fas fa-credit-card"></i>Pembayaran
                            </a>
                        </li>
                        @endif
                        
                        <!-- Laporan -->
                        <li class="nav-item">
                            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#laporan" role="button" aria-expanded="false" aria-controls="laporan">
                                <i class="fas fa-chart-bar"></i>Laporan
                                <i class="fas fa-chevron-down ms-auto"></i>
                            </a>
                            <div class="collapse" id="laporan">
                                <ul class="nav flex-column ms-3">
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.payments') }}">
                                            <i class="fas fa-credit-card"></i>Laporan Pembayaran
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('financial-reports.*') ? 'active' : '' }}" href="{{ route('financial-reports.index') }}">
                                            <i class="fas fa-calculator"></i>Laporan Keuangan
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        
                        <!-- Pengaturan -->
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                                <i class="fas fa-cog"></i>Pengaturan
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            @endauth

            <!-- Main Content -->
            <main class="main-content flex-grow-1">
                @yield('content')
            </main>
            
            <!-- Footer -->
            <footer class="footer mt-auto">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12 text-center">
                            <div class="footer-content">
                                <p class="mb-0">
                                    <i class="fas fa-graduation-cap text-primary me-2"></i>
                                    <strong>SISPEMA YASMU</strong>
                                    <span class="mx-3">|</span>
                                    <i class="fas fa-calendar-alt text-primary me-2"></i>
                                    © {{ date('Y') }} All rights reserved.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            } else {
                sidebar.classList.add('show');
                overlay.classList.add('show');
            }
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const toggleBtn = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth < 1200) {
                if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                }
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            // On desktop (xl and above), sidebar is always visible
            if (window.innerWidth >= 1200) {
                sidebar.classList.add('show');
                overlay.classList.remove('show');
            } else {
                // On mobile/tablet, hide sidebar by default
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });
        
        // Initialize sidebar state on page load
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            // Show sidebar on desktop by default (xl breakpoint = 1200px)
            if (window.innerWidth >= 1200) {
                sidebar.classList.add('show');
            } else {
                sidebar.classList.remove('show');
            }
        });
        
        // Handle collapse menu animations
        document.addEventListener('DOMContentLoaded', function() {
            // Handle collapse toggle
            const collapseElements = document.querySelectorAll('[data-bs-toggle="collapse"]');
            
            collapseElements.forEach(function(element) {
                element.addEventListener('click', function() {
                    const target = document.querySelector(this.getAttribute('href'));
                    const chevron = this.querySelector('.fa-chevron-down');
                    
                    if (target) {
                        target.addEventListener('shown.bs.collapse', function() {
                            if (chevron) {
                                chevron.style.transform = 'rotate(180deg)';
                            }
                        });
                        
                        target.addEventListener('hidden.bs.collapse', function() {
                            if (chevron) {
                                chevron.style.transform = 'rotate(0deg)';
                            }
                        });
                    }
                });
            });
        });
        
        // Load app colors from settings
        function loadAppColors() {
            fetch('/api/app-settings/colors', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
                .then(response => {
                    if (response.status === 401) {
                        // User not authenticated, skip loading colors
                        console.log('User not authenticated, skipping color loading');
                        return;
                    }
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.primary_color) {
                        document.documentElement.style.setProperty('--app-primary-color', data.primary_color);
                    }
                    if (data && data.secondary_color) {
                        document.documentElement.style.setProperty('--app-secondary-color', data.secondary_color);
                    }
                })
                .catch(error => {
                    console.log('Using default colors');
                });
        }
        
        // Load colors when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadAppColors();
        });
    </script>
    
    @stack('scripts')
</body>
</html>
