<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />   
     
        <div class="mb-4 text-sm text-gray-600">
            {{ __('Please set up Two-Factor Authentication to secure your account.') }}
        </div>

        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Setup Two-Factor Authentication</h2>
            
            <!-- QR Code Section -->
            <div class="text-center mb-6">
                <div class="mb-4">
                    <img src="{{ $qrCode }}" alt="QR Code" class="mx-auto">
                </div>
                <p class="text-sm text-gray-600 mb-2">
                    Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.)
                </p>
                <p class="text-xs text-gray-500">
                    Or manually enter this secret: <code class="bg-gray-100 px-2 py-1 rounded">{{ $secret }}</code>
                </p>
            </div>

            <!-- Verification Form -->
            <form method="POST" action="{{ route('2fa.verify') }}">
                @csrf
                
                <div class="mb-4">
                    <x-input-label for="code" :value="__('Verification Code')" />
                    <x-text-input id="code" 
                        class="block mt-1 w-full text-center text-lg tracking-widest" 
                        type="text" 
                        name="code" 
                        :value="old('code')" 
                        required 
                        autofocus 
                        autocomplete="off"
                        placeholder="123456"
                        maxlength="6" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between">
                    <x-primary-button class="w-full justify-center">
                        {{ __('Verify & Continue') }}
                    </x-primary-button>
                </div>
            </form>

            <!-- Reset Option -->
            <div class="mt-4 text-center">
                <form method="POST" action="{{ route('2fa.reset') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-gray-600 hover:text-gray-900 underline">
                        {{ __('Reset 2FA Setup') }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Instructions -->
        <div class="mt-6 text-center">
            <details class="text-sm text-gray-600">
                <summary class="cursor-pointer hover:text-gray-900">Need help setting up?</summary>
                <div class="mt-2 text-left max-w-md mx-auto">
                    <p class="mb-2">1. Download an authenticator app:</p>
                    <ul class="list-disc list-inside mb-2 text-xs">
                        <li>Google Authenticator (iOS/Android)</li>
                        <li>Authy (iOS/Android/Desktop)</li>
                        <li>Microsoft Authenticator</li>
                    </ul>
                    <p class="mb-2">2. Scan the QR code with your app</p>
                    <p class="mb-2">3. Enter the 6-digit code from your app</p>
                    <p class="text-xs text-gray-500">The code changes every 30 seconds</p>
                </div>
            </details>
        </div>
</x-guest-layout>
