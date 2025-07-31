<?php
/**
 * Modern Sidebar Navigation
 * Collapsible sidebar with tabs for desktop, hidden on mobile
 */
?>
<!-- Desktop Sidebar Navigation -->
<div class="hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 lg:z-50 lg:w-72 sidebar-transition"
     :class="sidebarCollapsed ? 'lg:w-20' : 'lg:w-72'">
    
    <!-- Sidebar Container -->
    <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white border-r border-gray-200 shadow-lg">
        
        <!-- Header with Logo and Collapse Button -->
        <div class="flex h-16 shrink-0 items-center justify-between px-4 border-b border-gray-200">
            <!-- Logo/Brand -->
            <div class="flex items-center" :class="sidebarCollapsed ? 'justify-center w-full' : ''">
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-2 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div x-show="!sidebarCollapsed" x-transition class="ml-3">
                    <h2 class="text-lg font-bold text-gray-900">Club Manager</h2>
                    <p class="text-xs text-gray-500">Dashboard</p>
                </div>
            </div>
            
            <!-- Collapse Button -->
            <button @click="toggleSidebar()" 
                    class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
                    :class="sidebarCollapsed ? 'mx-auto' : ''">
                <svg class="w-5 h-5 text-gray-600 transition-transform" 
                     :class="sidebarCollapsed ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                </svg>
            </button>
        </div>
        
        <!-- Navigation -->
        <nav class="flex flex-1 flex-col px-4 pb-4">
            <ul role="list" class="flex flex-1 flex-col gap-y-2">
                
                <!-- Player Management -->
                <li x-show="isTabAvailable('player-management')">
                    <button @click="activeTab = 'player-management'"
                            class="group flex w-full items-center gap-x-3 rounded-lg p-3 text-sm font-semibold transition-all duration-200"
                            :class="activeTab === 'player-management' ? 
                                'bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-lg' : 
                                'text-gray-700 hover:text-orange-600 hover:bg-orange-50'">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" x-transition>Player Management</span>
                        
                        <!-- Active Indicator -->
                        <div x-show="activeTab === 'player-management' && !sidebarCollapsed" 
                             class="ml-auto">
                            <div class="h-2 w-2 rounded-full bg-white opacity-75"></div>
                        </div>
                    </button>
                </li>
                
                <!-- Team Management -->
                <li x-show="isTabAvailable('team-management')" x-cloak>
                    <button @click="activeTab = 'team-management'"
                            class="group flex w-full items-center gap-x-3 rounded-lg p-3 text-sm font-semibold transition-all duration-200"
                            :class="activeTab === 'team-management' ? 
                                'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg' : 
                                'text-gray-700 hover:text-blue-600 hover:bg-blue-50'">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" x-transition>Team Management</span>
                        
                        <!-- Active Indicator -->
                        <div x-show="activeTab === 'team-management' && !sidebarCollapsed" 
                             class="ml-auto">
                            <div class="h-2 w-2 rounded-full bg-white opacity-75"></div>
                        </div>
                    </button>
                </li>
                
                <!-- Trainer Management -->
                <li x-show="isTabAvailable('trainer-management')" x-cloak>
                    <button @click="activeTab = 'trainer-management'"
                            class="group flex w-full items-center gap-x-3 rounded-lg p-3 text-sm font-semibold transition-all duration-200"
                            :class="activeTab === 'trainer-management' ? 
                                'bg-gradient-to-r from-green-500 to-green-600 text-white shadow-lg' : 
                                'text-gray-700 hover:text-green-600 hover:bg-green-50'">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" x-transition>Trainer Management</span>
                        
                        <!-- Active Indicator -->
                        <div x-show="activeTab === 'trainer-management' && !sidebarCollapsed" 
                             class="ml-auto">
                            <div class="h-2 w-2 rounded-full bg-white opacity-75"></div>
                        </div>
                    </button>
                </li>
                
                <!-- Import/Export -->
                <li x-show="isTabAvailable('import-export')" x-cloak>
                    <button @click="activeTab = 'import-export'"
                            class="group flex w-full items-center gap-x-3 rounded-lg p-3 text-sm font-semibold transition-all duration-200"
                            :class="activeTab === 'import-export' ? 
                                'bg-gradient-to-r from-purple-500 to-purple-600 text-white shadow-lg' : 
                                'text-gray-700 hover:text-purple-600 hover:bg-purple-50'">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" x-transition>Import/Export</span>
                        
                        <!-- Active Indicator -->
                        <div x-show="activeTab === 'import-export' && !sidebarCollapsed" 
                             class="ml-auto">
                            <div class="h-2 w-2 rounded-full bg-white opacity-75"></div>
                        </div>
                    </button>
                </li>
                
                <!-- Spacer -->
                <li class="flex-1"></li>
                
                <!-- Season Selector -->
                <li x-show="!sidebarCollapsed" x-transition>
                    <div class="border-t border-gray-200 pt-4">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Season</label>
                        <select x-model="currentSeason" 
                                @change="changeSeason()"
                                class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="2024-2025">2024-2025</option>
                            <option value="2023-2024">2023-2024</option>
                            <option value="2022-2023">2022-2023</option>
                        </select>
                    </div>
                </li>
                
                <!-- User Info -->
                <li x-show="!sidebarCollapsed" x-transition>
                    <div class="bg-gray-50 rounded-lg p-3 mt-4">
                        <div class="flex items-center">
                            <div class="bg-gradient-to-br from-gray-400 to-gray-500 rounded-full w-8 h-8 flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900">Current User</p>
                                <p class="text-xs text-gray-500" x-text="userPermissions.user_type || 'Loading...'"></p>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </nav>
    </div>
</div>

<!-- Mobile Sidebar Overlay -->
<div class="lg:hidden">
    <!-- Mobile sidebar backdrop -->
    <div x-show="mobileSidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-gray-900/80" 
         @click="closeMobileSidebar()"></div>

    <!-- Mobile sidebar -->
    <div x-show="mobileSidebarOpen"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl">
        
        <!-- Mobile Header -->
        <div class="flex h-16 shrink-0 items-center justify-between px-4 border-b border-gray-200">
            <div class="flex items-center">
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-2 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h2 class="text-lg font-bold text-gray-900">Club Manager</h2>
                </div>
            </div>
            
            <button @click="closeMobileSidebar()" class="p-2 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Mobile Navigation - Same as desktop but without collapse logic -->
        <nav class="flex flex-1 flex-col px-4 pb-4 mt-5">
            <ul role="list" class="flex flex-1 flex-col gap-y-2">
                <!-- Player Management -->
                <li x-show="isTabAvailable('player-management')">
                    <button @click="activeTab = 'player-management'; closeMobileSidebar()"
                            class="group flex w-full items-center gap-x-3 rounded-lg p-3 text-sm font-semibold transition-all duration-200"
                            :class="activeTab === 'player-management' ? 
                                'bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-lg' : 
                                'text-gray-700 hover:text-orange-600 hover:bg-orange-50'">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span>Player Management</span>  
                    </button>
                </li>
                
                <!-- Team Management -->
                <li x-show="isTabAvailable('team-management')" x-cloak>
                    <button @click="activeTab = 'team-management'; closeMobileSidebar()"
                            class="group flex w-full items-center gap-x-3 rounded-lg p-3 text-sm font-semibold transition-all duration-200"
                            :class="activeTab === 'team-management' ? 
                                'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg' : 
                                'text-gray-700 hover:text-blue-600 hover:bg-blue-50'">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <span>Team Management</span>
                    </button>
                </li>
                
                <!-- Trainer Management -->
                <li x-show="isTabAvailable('trainer-management')" x-cloak>
                    <button @click="activeTab = 'trainer-management'; closeMobileSidebar()"
                            class="group flex w-full items-center gap-x-3 rounded-lg p-3 text-sm font-semibold transition-all duration-200"
                            :class="activeTab === 'trainer-management' ? 
                                'bg-gradient-to-r from-green-500 to-green-600 text-white shadow-lg' : 
                                'text-gray-700 hover:text-green-600 hover:bg-green-50'">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span>Trainer Management</span>
                    </button>
                </li>
                
                <!-- Import/Export -->
                <li x-show="isTabAvailable('import-export')" x-cloak>
                    <button @click="activeTab = 'import-export'; closeMobileSidebar()"
                            class="group flex w-full items-center gap-x-3 rounded-lg p-3 text-sm font-semibold transition-all duration-200"
                            :class="activeTab === 'import-export' ? 
                                'bg-gradient-to-r from-purple-500 to-purple-600 text-white shadow-lg' : 
                                'text-gray-700 hover:text-purple-600 hover:bg-purple-50'">
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                        </svg>
                        <span>Import/Export</span>
                    </button>
                </li>
            </ul>
        </nav>
    </div>
</div>