<?php
/**
 * Header partial template
 */
?>
<!-- Header Section -->
<div class="bg-gradient-to-br from-white to-orange-50 rounded-2xl shadow-xl p-4 md:p-8 mb-8 border border-orange-100 relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
            <defs>
                <pattern id="headerPattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                    <circle cx="10" cy="10" r="1" fill="#f97316"/>
                </pattern>
            </defs>
            <rect width="100" height="100" fill="url(#headerPattern)"/>
        </svg>
    </div>
    
    <!-- Orange accent corner -->
    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-orange-400 to-orange-600 rounded-bl-full opacity-10"></div>
    <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-orange-300 to-orange-500 rounded-tr-full opacity-10"></div>
    
    <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <!-- Icon -->
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-4 shadow-lg">
                <svg class="w-8 h-8 md:w-10 md:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-gray-900 to-orange-600 bg-clip-text text-transparent mb-2">
                    Club Manager
                </h1>
                <p class="text-gray-600 text-sm md:text-base flex items-center">
                    <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Manage your hockey teams and players efficiently
                </p>
            </div>
        </div>
        
        <div class="flex items-center space-x-4">
            <!-- User info (optional) -->
            <div class="hidden lg:flex items-center space-x-3 text-sm text-gray-600">
                <div class="bg-orange-100 rounded-full p-2">
                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <span>Welcome back!</span>
            </div>
            
            <!-- Season Selector -->
            <div class="relative">
                <label class="text-sm font-medium text-gray-700 mb-1 block">Season</label>
                <div class="relative">
                    <select x-model="currentSeason" @change="changeSeason" 
                        class="select select-bordered bg-white border-gray-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-lg px-4 py-2 pr-10 appearance-none cursor-pointer shadow-sm hover:shadow-md transition-shadow">
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
</div>