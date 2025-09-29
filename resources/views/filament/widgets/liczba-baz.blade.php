<div class="fi-wi-widget fi-account-widget col-span-2 row-span-1">
    <section>
        <div class="fi-section-content-ctn">
            <div class="fi-section-content grid gap-6 md:grid-cols-1">
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 h-24">
                    <div class="grid gap-y-2">
                        <div class="flex items-center gap-x-2">
                        <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
    {{ auth()->user()->isAdmin() 
        ? __('filament.total_bases_globally') 
        : __('filament.your_bases_created') 
    }}
</span>
                        </div>

                        <div class="fi-wi-stats-overview-stat-value text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ auth()->user()->isAdmin() ? \App\Models\Bazy::count() : \App\Models\Bazy::where('user_id', auth()->id())->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
