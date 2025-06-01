<!-- Header Section -->
<div class="bg-white rounded-2xl shadow-xl p-4 md:p-8 mb-8 border-t-4 border-orange-500">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">Club Manager</h1>
            <p class="text-gray-600 text-sm md:text-base">Manage your hockey teams and players efficiently</p>
        </div>
        <div class="flex items-center space-x-4">
            <div class="relative">
                <label class="text-sm font-medium text-gray-700 mb-1 block">Season</label>
                <select x-model="currentSeason" @change="changeSeason" 
                    class="select select-bordered bg-white border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg px-4 py-2 pr-10 appearance-none cursor-pointer">
                    <option value="2024-2025">2024-2025</option>
                    <option value="2025-2026">2025-2026</option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none mt-6">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div> 
