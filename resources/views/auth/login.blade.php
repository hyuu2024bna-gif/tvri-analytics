<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | TVRI Aceh Analytics</title>

    {{-- Bootstrap 5 --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    {{-- Bootstrap Icons --}}
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>
        :root {
            --tvri-blue: #0757c9;
            --tvri-blue-dark: #063d91;
            --tvri-blue-light: #2f80ed;
            --text-dark: #142957;
            --text-muted: #536a99;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: #f8fbff;
        }

        .login-page {
            min-height: 100vh;
            display: flex;
        }

        /* =========================================
           LEFT PANEL
        ========================================= */

        .brand-panel {
            position: relative;
            width: 50%;
            min-height: 100vh;
            overflow: hidden;

            background:
                linear-gradient(
                    180deg,
                    rgba(5, 71, 158, 0.88) 0%,
                    rgba(0, 68, 155, 0.78) 45%,
                    rgba(2, 48, 112, 0.96) 100%
                ),
                url('{{ asset('images/tvri-aceh.jpg') }}');

            background-size: cover;
            background-position: center;
        }

        .brand-panel::after {
            content: "";
            position: absolute;
            inset: 0;

            background:
                radial-gradient(
                    circle at 5% 0%,
                    rgba(255,255,255,0.16),
                    transparent 28%
                );

            pointer-events: none;
        }

        .brand-content {
            position: relative;
            z-index: 2;

            min-height: 100vh;
            display: flex;
            flex-direction: column;

            padding: 70px 11%;
            color: white;
        }

        /* TVRI Logo */

        .tvri-logo {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 8px;
        }

        .tvri-logo img {
            width: 160px;
            height: auto;
            display: block;
        }

        .aceh-text {
            margin-left: 50px;
            margin-top: 8px;

            font-size: 25px; 
            font-weight: 700;
            letter-spacing: 2px;
        }

        .brand-title {
            margin-top: 32px;

            font-size: clamp(34px, 3vw, 48px);
            font-weight: 700;
            line-height: 1.08;

            max-width: 550px;
        }

        .brand-subtitle {
            margin-top: 12px;

            font-size: 25px;
            font-weight: 500;
        }

        .brand-line {
            width: 55px;
            height: 4px;

            margin: 25px 0 20px;

            background: #3da4ff;
        }

        .brand-description {
            max-width: 470px;

            font-size: 17px;
            line-height: 1.6;

            color: rgba(255, 255, 255, 0.9);
        }

        /* Footer kiri */

        .brand-footer {
            margin-top: auto;

            display: flex;
            align-items: center;
            gap: 28px;
        }

        .tagline {
            font-size: 16px;
            font-weight: 700;
            line-height: 1.25;
        }

        .footer-divider {
            width: 2px;
            height: 42px;
            background: rgba(255,255,255,0.7);
        }

        .social-icons {
            display: flex;
            gap: 20px;
        }

        .social-icons a {
            color: white;
            font-size: 22px;
            text-decoration: none;
            transition: transform 0.2s ease;
        }

        .social-icons a:hover {
            transform: translateY(-2px);
        }

        /* =========================================
           RIGHT PANEL
        ========================================= */

        .login-panel {
            position: relative;
            width: 50%;
            min-height: 100vh;

            background: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 50px 8%;
        }

        .login-panel::before {
            content: "";
            position: absolute;

            top: -100px;
            right: -100px;

            width: 250px;
            height: 250px;

            border: 45px solid rgba(7, 87, 201, 0.08);
            border-radius: 50%;
        }

        .login-panel::after {
            content: "";
            position: absolute;

            top: 55px;
            right: 70px;

            width: 70px;
            height: 70px;

            background-image: radial-gradient(
                #82aef5 1.8px,
                transparent 1.8px
            );

            background-size: 16px 16px;

            opacity: 0.8;
        }

        .login-wrapper {
            width: 100%;
            max-width: 550px;
            position: relative;
            z-index: 2;
        }

        .welcome-title {
            color: var(--text-dark);

            font-size: 43px;
            font-weight: 700;

            margin-bottom: 12px;
        }

        .welcome-description {
            color: var(--text-muted);

            font-size: 18px;
            line-height: 1.55;

            margin-bottom: 42px;
        }

        /* Input */

        .input-wrapper {
            position: relative;
            margin-bottom: 22px;
        }

        .input-icon {
            position: absolute;

            left: 27px;
            top: 50%;

            transform: translateY(-50%);

            font-size: 23px;

            color: #536d9f;

            z-index: 3;
        }

        .login-input {
            height: 68px;

            padding-left: 75px;
            padding-right: 55px;

            border: 1px solid #c5d5ef;
            border-radius: 12px;

            font-size: 18px;

            color: var(--text-dark);

            box-shadow: none;
        }

        .login-input:focus {
            border-color: var(--tvri-blue);
            box-shadow: 0 0 0 3px rgba(7,87,201,0.10);
        }

        .password-toggle {
            position: absolute;

            right: 22px;
            top: 50%;

            transform: translateY(-50%);

            border: none;
            background: transparent;

            color: #536d9f;

            font-size: 21px;
        }

        /* Remember / Forgot */

        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin: 26px 0 34px;
        }

        .form-check-input {
            width: 25px;
            height: 25px;

            margin-top: 0;

            border-radius: 6px;
            border-color: #9bb4dc;
        }

        .form-check-input:checked {
            background-color: var(--tvri-blue);
            border-color: var(--tvri-blue);
        }

        .form-check-label {
            margin-left: 8px;

            color: var(--text-dark);
            font-size: 16px;
        }

        .forgot-link {
            color: #0666db;
            text-decoration: none;

            font-size: 16px;
            font-weight: 500;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* Button */

        .login-button {
            width: 100%;
            height: 64px;

            border: none;
            border-radius: 11px;

            background: linear-gradient(
                90deg,
                #0757c9,
                #0648b3
            );

            color: white;

            font-size: 19px;
            font-weight: 700;

            transition: all 0.2s ease;
        }

        .login-button:hover {
            transform: translateY(-1px);

            box-shadow:
                0 8px 20px rgba(7,87,201,0.25);
        }

        .login-button i {
            margin-left: 15px;
            font-size: 22px;
        }

        /* Copyright */

        .copyright {
            position: absolute;

            bottom: 45px;
            left: 50%;

            transform: translateX(-50%);

            width: 55%;

            display: flex;
            align-items: center;
            gap: 25px;

            color: #536a99;

            font-size: 14px;
            white-space: nowrap;
        }

        .copyright::before,
        .copyright::after {
            content: "";
            height: 1px;

            flex: 1;

            background: #bfd0ec;
        }

        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 992px) {

            .login-page {
                flex-direction: column;
            }

            .brand-panel,
            .login-panel {
                width: 100%;
                min-height: auto;
            }

            .brand-panel {
                min-height: 500px;
            }

            .brand-content {
                min-height: 500px;
            }

            .login-panel {
                padding: 70px 8% 130px;
            }

            .copyright {
                bottom: 40px;
            }
        }

        @media (max-width: 576px) {

            .brand-panel {
                min-height: 440px;
            }

            .brand-content {
                min-height: 440px;
                padding: 45px 30px;
            }

            .tvri-logo img {
                width: 120px;
            }

            .aceh-text {
                margin-left: 6px;
                font-size: 22px;
            }

            .brand-title {
                margin-top: 25px;
                font-size: 31px;
            }

            .brand-subtitle {
                font-size: 20px;
            }

            .brand-description {
                font-size: 14px;
            }

            .brand-footer {
                gap: 15px;
            }

            .tagline {
                font-size: 12px;
            }

            .social-icons {
                gap: 12px;
            }

            .social-icons a {
                font-size: 18px;
            }

            .welcome-title {
                font-size: 34px;
            }

            .welcome-description {
                font-size: 16px;
            }

            .login-input {
                height: 60px;
                font-size: 16px;
            }

            .login-options {
                font-size: 14px;
            }

            .copyright {
                width: 80%;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>

<div class="login-page">

    {{-- =========================================
         PANEL KIRI
    ========================================== --}}
    <section class="brand-panel">

        <div class="brand-content">

            {{-- Logo --}}
            <div>
                <div class="tvri-logo">
                    <img src="{{ asset('images/tvri-logo.webp') }}" alt="Logo TVRI">
                </div>

                <div class="aceh-text">
                    ACEH
                </div>
            </div>

            {{-- Judul --}}
            <div>
                <h1 class="brand-title">
                    Dashboard Analitik<br>
                    Media Sosial
                </h1>

                <div class="brand-subtitle">
                    TVRI Stasiun Aceh
                </div>

                <div class="brand-line"></div>

                <p class="brand-description">
                    Memonitor performa konten media sosial
                    untuk mendukung penyebaran informasi
                    yang lebih luas dan tepat sasaran.
                </p>
            </div>

            {{-- Footer --}}
            <div class="brand-footer">

                <div class="tagline">
                    Media Pemersatu Bangsa
                </div>

                <div class="footer-divider"></div>

                <div class="social-icons">

                    <a href="#" aria-label="YouTube">
                        <i class="bi bi-youtube"></i>
                    </a>

                    <a href="#" aria-label="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>

                    <a href="#" aria-label="TikTok">
                        <i class="bi bi-tiktok"></i>
                    </a>

                    <a href="#" aria-label="Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>

                </div>

            </div>

        </div>

    </section>


    {{-- =========================================
         PANEL KANAN
    ========================================== --}}
    <section class="login-panel">

        <div class="login-wrapper">

            <h1 class="welcome-title">
                Selamat Datang
            </h1>

            <p class="welcome-description">
                Silakan masuk ke akun Anda untuk mengakses
                dashboard analitik media sosial TVRI Stasiun Aceh.
            </p>


            {{-- Session Status --}}
            @if (session('status'))
                <div class="alert alert-success mb-4">
                    {{ session('status') }}
                </div>
            @endif


            {{-- Error Login --}}
            @if ($errors->any())
                <div class="alert alert-danger mb-4">
                    Email atau password yang Anda masukkan tidak sesuai.
                </div>
            @endif


            <form method="POST" action="{{ route('login') }}">

                @csrf

                {{-- Email --}}
                <div class="input-wrapper">

                    <i class="bi bi-person-fill input-icon"></i>

                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="form-control login-input @error('email') is-invalid @enderror"
                        placeholder="Email atau Username"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                    >

                </div>


                {{-- Password --}}
                <div class="input-wrapper">

                    <i class="bi bi-lock-fill input-icon"></i>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control login-input"
                        placeholder="Password"
                        required
                        autocomplete="current-password"
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        id="togglePassword"
                        aria-label="Tampilkan password"
                    >
                        <i class="bi bi-eye-slash"></i>
                    </button>

                </div>


                {{-- Options --}}
                <div class="login-options">

                    <div class="form-check d-flex align-items-center">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="remember"
                            id="remember_me"
                        >

                        <label
                            class="form-check-label"
                            for="remember_me"
                        >
                            Ingatkan saya
                        </label>

                    </div>


                    @if (Route::has('password.request'))
                        <a
                            href="{{ route('password.request') }}"
                            class="forgot-link"
                        >
                            Lupa password?
                        </a>
                    @endif

                </div>


                {{-- Login --}}
                <button
                    type="submit"
                    class="login-button"
                >
                    Masuk
                    <i class="bi bi-arrow-right"></i>
                </button>

            </form>

        </div>


        {{-- Copyright --}}
        <div class="copyright">
            © {{ date('Y') }} TVRI Stasiun Aceh
        </div>

    </section>

</div>


<script>
    const togglePassword =
        document.getElementById('togglePassword');

    const password =
        document.getElementById('password');

    togglePassword.addEventListener('click', function () {

        const type =
            password.getAttribute('type') === 'password'
                ? 'text'
                : 'password';

        password.setAttribute('type', type);

        const icon =
            this.querySelector('i');

        icon.classList.toggle('bi-eye');
        icon.classList.toggle('bi-eye-slash');

    });
</script>

</body>
</html>