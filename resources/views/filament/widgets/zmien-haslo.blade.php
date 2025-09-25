<div>
    {{-- Wyświetlenie formularza Filament --}}
    {{ $this->form }}
    <div class="mt-4 ml-3">
    <button 
        wire:click="zmienHaslo"
        class="mt-3 fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg  fi-btn-color-gray fi-color-gray fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm hidden sm:inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20 [input:checked+&]:bg-gray-400 [input:checked+&]:text-white [input:checked+&]:ring-0 [input:checked+&]:hover:bg-gray-300 dark:[input:checked+&]:bg-gray-600 dark:[input:checked+&]:hover:bg-gray-500"
    >
        Zmień hasło
    </button>
</div>

</div>

</div>
