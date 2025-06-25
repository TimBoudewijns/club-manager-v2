<?php
/**
 * Header partial template with integrated tabs
 */
?>
<!-- Header Section with Integrated Tabs -->
<div class="header-section rounded-3xl shadow-2xl mb-6 md:mb-8 border border-orange-100/50 overflow-hidden transition-all duration-300 hover:shadow-3xl">
    <!-- Header Content -->
    <div class="p-6 md:p-8 lg:p-10 border-b border-gradient-to-r from-transparent via-orange-100/30 to-transparent">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="flex items-center space-x-6">
                <!-- Animated Icon -->
                <div class="gradient-animation rounded-3xl p-5 shadow-xl floating-element relative overflow-hidden">
                    <div class="absolute inset-0 bg-white/20 rounded-3xl"></div>
                    <svg class="w-10 h-10 md:w-12 md:h-12 text-white drop-shadow-lg relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-3 bg-gradient-to-r from-gray-900 via-orange-800 to-gray-900 bg-clip-text text-transparent leading-tight">
                        Club Manager
                    </h1>
                    <p class="text-gray-600 text-base md:text-lg flex items-center">
                        <svg class="w-5 h-5 mr-3 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Manage your hockey teams and players efficiently
                    </p>
                </div>
            </div>
            
            <div class="flex items-center space-x-6">
                <!-- User info (optional) -->
                <div class="hidden xl:flex items-center space-x-4 text-sm text-gray-600">
                    <div class="bg-gradient-to-br from-orange-100 to-orange-200 rounded-full p-3 shadow-lg">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Welcome back!</p>
                        <p class="text-xs text-gray-500">Ready to manage your teams?</p>
                    </div>
                </div>
                
                <!-- Season Selector -->
                <div class="relative">
                    <label class="text-sm font-semibold text-gray-700 mb-2 block">Season</label>
                    <div class="relative">
                        <select x-model="currentSeason" @change="changeSeason" 
                            class="select select-bordered bg-white/90 backdrop-filter backdrop-blur-sm border-orange-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 rounded-xl px-4 py-3 pr-10 appearance-none cursor-pointer shadow-lg hover:shadow-xl transition-all duration-300 font-medium">
                            <option value="2024-2025">2024-2025</option>
                            <option value="2025-2026">2025-2026</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none mt-7">
                            <svg class="h-5 w-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Integrated Tabs Section -->
    <div class="bg-gradient-to-r from-gray-50/80 via-orange-50/50 to-gray-50/80 backdrop-filter backdrop-blur-sm px-6 md:px-8 lg:px-10 py-4">
        <!-- Mobile tabs with scroll -->
        <div class="lg:hidden">
            <div class="flex space-x-2 overflow-x-auto scrollbar-hide pb-2">
                <button class="tab-button flex-shrink-0 py-3 px-6 rounded-xl font-semibold transition-all duration-300 whitespace-nowrap text-sm shadow-lg hover:shadow-xl"
                        :class="activeTab === 'player-management' ? 'active' : ''"
                        @click="activeTab = 'player-management'"
                        x-show="isTabAvailable('player-management')">
                    <span class="flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span>Players</span>
                    </span>
                </button>
                
                <button x-show="isTabAvailable('team-management')"
                        x-cloak
                        class="tab-button flex-shrink-0 py-3 px-6 rounded-xl font-semibold transition-all duration-300 whitespace-nowrap text-sm shadow-lg hover:shadow-xl"
                        :class="activeTab === 'team-management' ? 'active' : ''"
                        @click="activeTab = 'team-management'">
                    <span class="flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <span>Teams</span>
                    </span>
                </button>
                
                <button x-show="isTabAvailable('trainer-management')"
                        x-cloak
                        class="tab-button flex-shrink-0 py-3 px-6 rounded-xl font-semibold transition-all duration-300 whitespace-nowrap text-sm shadow-lg hover:shadow-xl"
                        :class="activeTab === 'trainer-management' ? 'active' : ''"
                        @click="activeTab = 'trainer-management'">
                    <span class="flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span>Trainers</span>
                    </span>
                </button>
            </div>
        </div>
        
        <!-- Desktop tabs -->
        <div class="hidden lg:flex items-center space-x-3">
            <button class="tab-button py-4 px-8 rounded-xl font-semibold transition-all duration-300 text-base shadow-lg hover:shadow-xl group"
                    :class="activeTab === 'player-management' ? 'active' : ''"
                    @click="activeTab = 'player-management'"
                    x-show="isTabAvailable('player-management')">
                <span class="flex items-center space-x-3">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>Player Management</span>
                </span>
            </button>
            
            <button x-show="isTabAvailable('team-management')"
                    x-cloak
                    class="tab-button py-4 px-8 rounded-xl font-semibold transition-all duration-300 text-base shadow-lg hover:shadow-xl group"
                    :class="activeTab === 'team-management' ? 'active' : ''"
                    @click="activeTab = 'team-management'">
                <span class="flex items-center space-x-3">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <span>Team Management</span>
                </span>
            </button>
            
            <button x-show="isTabAvailable('trainer-management')"
                    x-cloak
                    class="tab-button py-4 px-8 rounded-xl font-semibold transition-all duration-300 text-base shadow-lg hover:shadow-xl group"
                    :class="activeTab === 'trainer-management' ? 'active' : ''"
                    @click="activeTab = 'trainer-management'">
                <span class="flex items-center space-x-3">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span>Trainer Management</span>
                </span>
            </button>
        </div>
    </div>
</div>

<style>
/* Custom scrollbar hiding for mobile tabs */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

/* Enhanced floating animation */
@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    25% { transform: translateY(-8px) rotate(1deg); }
    50% { transform: translateY(-12px) rotate(0deg); }
    75% { transform: translateY(-8px) rotate(-1deg); }
}

.floating-element {
    animation: float 8s ease-in-out infinite;
}

/* Enhanced gradient animation */
@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.gradient-animation {
    background: linear-gradient(-45deg, #f97316, #ea580c, #fb923c, #fdba74);
    background-size: 400% 400%;
    animation: gradientShift 8s ease infinite;
}

/* Tab hover effects */
.tab-button:not(.active)::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    padding: 1px;
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.2), rgba(251, 146, 60, 0.2));
    mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    mask-composite: exclude;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.tab-button:not(.active):hover::before {
    opacity: 1;
}

/* Active tab glow effect */
.tab-button.active {
    position: relative;
}

.tab-button.active::after {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: inherit;
    background: linear-gradient(135deg, #f97316, #ea580c);
    filter: blur(8px);
    opacity: 0.3;
    z-index: -1;
}
</style>