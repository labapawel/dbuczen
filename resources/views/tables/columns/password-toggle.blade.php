<div x-data="{ show: false }" class="flex items-center">
    <!-- ukryte hasło -->
    <span x-show="!show">{{ str_repeat('•', strlen($getState())) }}</span>
    
    <!-- widoczne hasło -->
    <span x-show="show">{{ $getState() }}</span>

    <!-- przycisk oko -->
    <button type="button" @click="show = !show" class="ml-2 text-gray-600 hover:text-gray-900">
        <x-heroicon-o-eye x-show="!show" class="w-4 h-4"/>
        <x-heroicon-o-eye-slash x-show="show" class="w-4 h-4"/>
    </button>
</div>
