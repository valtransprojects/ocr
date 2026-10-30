@extends('app')

@section('title', 'Batch ANPR Uploader - Password Required')

@section('styles')
<style>
    .auth-card-wrapper {
        min-height: calc(100vh - 220px);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .auth-card {
        width: 100%;
        max-width: 440px;
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
    }
    .lock-icon-circle {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(var(--bs-primary-rgb), 0.12);
        color: var(--bs-primary);
        margin-bottom: 1.25rem;
    }
</style>
@endsection

@section('content')
<div class="auth-card-wrapper">
    <div class="card card-flush auth-card">
        <div class="card-body p-8 p-lg-10 text-center">
            <div class="lock-icon-circle">
                <i class="ki-outline ki-lock fs-2x text-primary"></i>
            </div>

            <h2 class="fw-bold text-gray-900 mb-2">Password Protected</h2>
            <p class="text-gray-500 fs-7 mb-6">
                Enter the password to access the Batch ANPR Uploader and image queue.
            </p>

            @if(session('error'))
                <div class="alert alert-danger d-flex align-items-center p-3 mb-6 text-start">
                    <i class="ki-outline ki-cross-circle fs-3 text-danger me-2"></i>
                    <div class="fs-7 text-danger fw-semibold">{{ session('error') }}</div>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info d-flex align-items-center p-3 mb-6 text-start">
                    <i class="ki-outline ki-information fs-3 text-info me-2"></i>
                    <div class="fs-7 text-info fw-semibold">{{ session('info') }}</div>
                </div>
            @endif

            <form method="POST" action="{{ route('ocr.batch.uploader.auth') }}" autocomplete="off">
                @csrf
                <div class="mb-5 text-start">
                    <label for="uploader-password" class="form-label fs-7 fw-semibold text-gray-700">Access Password</label>
                    <div class="position-relative">
                        <input
                            type="password"
                            id="uploader-password"
                            name="password"
                            class="form-control form-control-solid ps-12"
                            placeholder="Enter password"
                            required
                            autofocus
                        />
                        <span class="position-absolute top-50 translate-middle-y ms-4 text-gray-500">
                            <i class="ki-outline ki-key fs-4"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">
                    <i class="ki-outline ki-shield-tick fs-5 me-1"></i> Unlock Uploader
                </button>
            </form>

            <div class="mt-6 pt-5 border-top">
                <a href="{{ route('ocr.batch') }}" class="text-gray-600 text-hover-primary fs-7 fw-semibold d-inline-flex align-items-center gap-2">
                    <i class="ki-outline ki-arrow-left fs-6"></i> Return to Database Records Log
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

