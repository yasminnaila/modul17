@extends('layouts.app')

@section('content')
@php($hideNavbar = true)
<style>
    body {
        background-color: #007bff;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }
    .card {
        border-radius: 10px;
        box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.2);
        width: 400px;
    }
    .logo {
        width: 50px;
        height: 50px;
        margin-bottom: 10px;
    }
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }
</style>

<div class="card p-4">
    <div class="text-center">
        <img src="{{ asset('path-to-your-logo/logo.png') }}" alt="Logo" class="logo">
        <h4 class="mt-2">Employee Data Master</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">Enter Your Email</label>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Enter Your Password</label>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">Remember Me</label>
            </div>

            <button type="submit" class="btn btn-primary w-100">Log In</button>

            @if (Route::has('password.request'))
            <a class="btn btn-link d-block text-center mt-3" href="{{ route('password.request') }}">Forgot Your Password?</a>
            @endif
        </form>
    </div>
</div>
@endsection
