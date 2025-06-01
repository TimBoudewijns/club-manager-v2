<!-- Teams Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <template x-for="team in teams" :key="team.id">
        <div @click="selectTeam(team)" 
             class="bg-white rounded-xl shadow-lg hover:shadow-2xl transform transition-all duration-300 hover:scale-105 cursor-pointer overflow-hidden group">
            <div class="bg-gradient-to-r from-orange-400 to-orange-500 h-2 group-hover:h-3 transition-all duration-300"></div>
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-2xl font-bold text-gray-900 group-hover:text-orange-600 transition-colors" x-text="team.name"></h3>
                    <div class="bg-orange-100 text-orange-600 px-3 py-1 rounded-full text-sm font-semibold">
                        <span x-text="team.season"></span>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center text-gray-600">
                        <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="font-medium">Coach:</span>
                        <span class="ml-2" x-text="team.coach"></span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <span class="text-sm text-gray-500">Click to manage players →</span>
                </div>
            </div>
        </div>
    </template>
</div>

<!-- Empty State -->
<div x-show="teams.length === 0" class="text-center py-16">
    <div class="bg-orange-50 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center">
        <svg class="w-12 h-12 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
    </div>
    <h3 class="text-2xl font-bold text-gray-900 mb-2">No teams yet</h3>
    <p class="text-gray-600 mb-6">Create your first team to get started managing your players.</p>
    <button @click="showCreateTeamModal = true" 
            class="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transform transition-all duration-200 hover:scale-105">
        Create Your First Team
    </button>
</div> 
