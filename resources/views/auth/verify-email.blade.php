<x-guest-layout>
    <h1 class="text-xl font-semibold text-ink">{{ __('Verify email address') }}</h1>

    <p class="mt-3 text-sm leading-6 text-muted">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <x-alert tone="info" class="mt-4">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </x-alert>
    @endif

    <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <x-primary-button>
                {{ __('Resend Verification Email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="rounded-lg px-2 py-1.5 text-sm font-semibold text-muted underline transition hover:text-brand focus:outline-none focus:ring-2 focus:ring-brand">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
