@extends('auth::layouts.auth')

@section('title', __('auth::auth.login') . ' — ' . config('app.name', 'Slendie'))

@section('content')
    <h1>{{ __('auth::auth.login') }}</h1>

    <form method="POST" action="{{ url('/login') }}" novalidate>
        {!! csrf_field() !!}

        <label for="email">{{ __('auth::auth.email') }}</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
        @error('email') <p class="error" role="alert">{{ $message }}</p> @enderror

        <label for="password">{{ __('auth::auth.password') }}</label>
        <input id="password" type="password" name="password" autocomplete="current-password" required>
        @error('password') <p class="error" role="alert">{{ $message }}</p> @enderror

        <label class="remember" for="remember">
            <input id="remember" type="checkbox" name="remember" value="1">
            {{ __('auth::auth.remember') }}
        </label>

        <button type="submit">{{ __('auth::auth.submit') }}</button>
    </form>
@endsection
