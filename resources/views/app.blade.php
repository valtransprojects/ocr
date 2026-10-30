<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'ANPR OCR Comparison Portal')</title>

    <!-- Google Fonts (Local) -->
    <link rel="stylesheet" href="{{ asset('css/google-fonts.css') }}">

    <!-- Metronic Global Plugins CSS Bundle -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/global/plugins.bundle.css') }}">

    <!-- Metronic Theme CSS Bundle -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.bundle.css') }}">

    <!-- Custom Common CSS overrides -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('styles')
</head>

<body
    id="kt_app_body"
    data-kt-app-layout="light-sidebar"
    data-kt-app-header-fixed="true"
    class="app-default">

    <!--begin::Page wrapper-->
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <!--begin::Page container-->
        <div class="app-page d-flex flex-column flex-column-fluid" id="kt_app_page">

            <!--begin::Header-->
            <div
                id="kt_app_header"
                class="app-header"
                data-kt-sticky="true"
                data-kt-sticky-activate="{default: true, lg: true}"
                data-kt-sticky-name="app-header-minimize"
                data-kt-sticky-offset="{default: '200px', lg: '300px'}"
                data-kt-sticky-animation="false"
                style="border-bottom: 1px solid var(--bs-border-color); background-color: var(--bs-app-header-base-bg-color, var(--bs-body-bg));">
                <!--begin::Header container-->
                <div
                    class="app-container container-fluid d-flex align-items-stretch justify-content-between px-5 px-lg-8"
                    id="kt_app_header_container"
                    style="min-height: 70px;">
                    <!--begin::Brand-->
                    <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0 me-lg-15">
                        <a href="/" class="d-flex align-items-center gap-3 text-decoration-none">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="#ffbf3c" />
                                <path d="M2 17L12 22L22 17M2 12L12 17L22 12" stroke="#ffbf3c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span class="fs-3 fw-bold text-gray-900">OCR<span class="text-primary">.</span>Comparison</span>
                        </a>
                    </div>
                    <!--end::Brand-->


                    <!--begin::Header wrapper-->
                    <div class="d-flex align-items-stretch flex-lg-row-fluid" id="kt_app_header_wrapper">
                        <!--begin::Navbar-->
                        <div class="app-navbar flex-shrink-0 d-flex align-items-center ms-auto">
                            <div class="app-navbar-item ms-2 ms-md-4">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-icon-gray-600 btn-active-light-primary theme-toggle-btn d-flex align-items-center gap-2 px-3 py-2"
                                    id="theme-toggle-btn"
                                    aria-label="Toggle light/dark theme">
                                    <i class="ki-outline ki-moon fs-4" id="theme-toggle-icon"></i>
                                    <span class="fw-semibold fs-7 d-none d-sm-inline" id="theme-toggle-text">
                                        Dark Mode
                                    </span>
                                </button>
                            </div>
                        </div>
                        <!--end::Navbar-->
                    </div>
                    <!--end::Header wrapper-->
                </div>
                <!--end::Header container-->
            </div>
            <!--end::Header-->

            <!--begin::Wrapper-->
            <div class="app-wrapper d-flex flex-column flex-row-fluid" id="kt_app_wrapper">
                <!--begin::Main-->
                <div class="app-main d-flex flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">
                        <!--begin::Content-->
                        <div id="kt_app_content" class="app-content flex-column-fluid py-8 py-lg-10">
                            <div id="kt_app_content_container" class="app-container container-fluid px-5 px-lg-8">
                                @yield('content')
                            </div>
                        </div>
                        <!--end::Content-->
                    </div>

                    <!--begin::Footer-->
                    <div
                        id="kt_app_footer"
                        class="app-footer py-5 mt-auto"
                        style="border-top: 1px solid var(--bs-border-color); background-color: rgba(var(--bs-body-bg-rgb), 0.5);">
                        <div class="app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack px-5 px-lg-8">
                            <div class="text-gray-900 order-2 order-md-1 text-center text-md-start">
                                <span class="text-muted fw-semibold me-1">&copy; {{ date('Y') }}</span>
                                <a href="/" class="text-gray-800 text-hover-primary text-decoration-none fw-semibold">ANPR OCR Comparison Portal</a>
                            </div>
                            <div class="text-muted fw-semibold fs-7 order-1 order-md-2 mb-3 mb-md-0">
                                Engine comparison &middot; Candid &middot; Eydea &middot; ARH &middot; Parkonic Engine
                            </div>
                        </div>
                    </div>
                    <!--end::Footer-->
                </div>
                <!--end::Main-->
            </div>
            <!--end::Wrapper-->

        </div>
        <!--end::Page container-->
    </div>
    <!--end::Page wrapper-->

    <!-- Metronic Global Javascript Bundles -->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>

    <!-- Theme Toggler -->
    <script id="theme-switcher-script">
        document.addEventListener('DOMContentLoaded', () => {
            const htmlElement = document.documentElement;
            const themeToggleBtn = document.getElementById('theme-toggle-btn');
            const themeToggleIcon = document.getElementById('theme-toggle-icon');
            const themeToggleText = document.getElementById('theme-toggle-text');

            const getPreferredTheme = () => {
                const storedTheme = localStorage.getItem('theme');
                if (storedTheme) {
                    return storedTheme;
                }
                return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
            };

            const setTheme = (theme) => {
                htmlElement.setAttribute('data-bs-theme', theme);
                localStorage.setItem('theme', theme);

                if (theme === 'dark') {
                    themeToggleIcon.className = 'ki-outline ki-sun fs-4 text-warning';
                    themeToggleText.textContent = 'Light Mode';
                } else {
                    themeToggleIcon.className = 'ki-outline ki-moon fs-4 text-primary';
                    themeToggleText.textContent = 'Dark Mode';
                }
            };

            setTheme(getPreferredTheme());

            themeToggleBtn.addEventListener('click', () => {
                const currentTheme = htmlElement.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                setTheme(newTheme);
            });
        });
    </script>
    @yield('scripts')
</body>

</html>