<div x-data="{ open: false }" class="space-y-4">

    <!-- Guzik toggle z ikoną -->
    <div class="mb-4">
        <button 
            @click="open = !open"
            class="fi-btn relative flex items-center justify-between w-48 font-semibold outline-none transition duration-150 focus-visible:ring-2 rounded-lg fi-btn-color-gray fi-color-gray fi-size-md fi-btn-size-md gap-2 px-3 py-2 text-sm shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20"
        >
            <span>Zmień hasło</span>
            <!-- Strzałka -->
            <svg 
                xmlns="http://www.w3.org/2000/svg" 
                class="h-5 w-5 transform transition-transform duration-300"
                :class="{'rotate-180': open}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
    </div>

    <!-- Formularz -->
    <div x-show="open" x-cloak class="transition-all duration-300">
        {{ $this->form }}

        <div class="mt-4 ml-3">
            <button 
                wire:click="zmienHaslo"
                class="mt-3 fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-gray fi-color-gray fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20"
            >
                Zapisz nowe hasło
            </button>
        </div>
    </div>
</div>
