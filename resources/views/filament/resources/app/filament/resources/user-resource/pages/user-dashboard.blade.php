<x-filament-panels::page>
    <div class="space-y-4">
        <h1 class="text-2xl font-bold">Witaj, {{ auth()->user()->name }}!</h1>

        <p>To jest Twój dashboard użytkownika 🎉</p>

        <div class="mt-6">
            <a href="{{ route('logout') }}" 
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="text-red-600 hover:underline">
                Wyloguj się
            </a>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>
    </div>
</x-filament-panels::page>
