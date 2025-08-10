<?php
/**
 * Club Manager Landing Page - For Owners and Club Managers
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Manager - Streamline Your Sports Club Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        }
        .feature-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .feature-card:hover {
            transform: translateY(-4px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Hero Section -->
    <div class="gradient-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24">
            <div class="text-center">
                <div class="mb-8">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-6">
                        <svg class="w-10 h-10 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                </div>
                <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                    Club Manager
                </h1>
                <p class="text-xl md:text-2xl text-orange-100 mb-8 max-w-3xl mx-auto">
                    The complete solution for sports club owners and managers to streamline team management, player tracking, and club operations
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button class="bg-white text-orange-600 font-bold py-4 px-8 rounded-xl shadow-lg hover:bg-orange-50 transition-all duration-200 transform hover:scale-105">
                        Get Started Today
                    </button>
                    <button class="bg-orange-600 text-white font-bold py-4 px-8 rounded-xl shadow-lg hover:bg-orange-700 transition-all duration-200 border border-orange-400">
                        View Demo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Benefits Section -->
    <div class="py-16 lg:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Why Choose Club Manager?
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Built specifically for sports club owners and managers who need efficient, professional tools to manage their teams and players
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Benefit 1 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Save Hours Every Week</h3>
                    <p class="text-gray-600">
                        Automate administrative tasks and reduce paperwork. Spend more time coaching and less time on management.
                    </p>
                </div>

                <!-- Benefit 2 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Professional Organization</h3>
                    <p class="text-gray-600">
                        Keep all your club data organized in one professional platform. No more scattered spreadsheets or lost information.
                    </p>
                </div>

                <!-- Benefit 3 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 515.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 919.288 0M15 7a3 3 0 11-6 0 3 3 0 616 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Empower Your Team</h3>
                    <p class="text-gray-600">
                        Give trainers and coaches the tools they need while maintaining full control and oversight of your club operations.
                    </p>
                </div>

                <!-- Benefit 4 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Data-Driven Decisions</h3>
                    <p class="text-gray-600">
                        Track player progress, team performance, and club growth with comprehensive reporting and analytics.
                    </p>
                </div>

                <!-- Benefit 5 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Secure & Reliable</h3>
                    <p class="text-gray-600">
                        Your club data is protected with enterprise-grade security. Automatic backups ensure you never lose important information.
                    </p>
                </div>

                <!-- Benefit 6 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Easy Data Migration</h3>
                    <p class="text-gray-600">
                        Import your existing club data effortlessly with our CSV import wizard. Get up and running in minutes, not days.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Core Features Section -->
    <div class="py-16 lg:py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Complete Club Management Suite
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Everything you need to run a professional sports club, all in one intuitive platform
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16">
                <!-- Feature 1: Player Management -->
                <div class="flex flex-col lg:flex-row items-center gap-8">
                    <div class="lg:w-1/2">
                        <div class="bg-white rounded-2xl p-8 shadow-lg">
                            <div class="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center mb-6">
                                <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 515.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 919.288 0M15 7a3 3 0 11-6 0 3 3 0 616 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">Player Management</h3>
                            <p class="text-gray-600 mb-6">
                                Comprehensive player profiles, performance tracking, and evaluation systems. Monitor progress across seasons and teams.
                            </p>
                            <ul class="space-y-3 text-gray-600">
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                                    Individual player profiles with photos and contact details
                                </li>
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                                    Performance evaluations and skill assessments
                                </li>
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                                    Multi-team player assignments and history tracking
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="lg:w-1/2">
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl p-8 border border-orange-200">
                            <div class="space-y-4">
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                                            <span class="text-orange-600 font-bold">JD</span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">John Doe</p>
                                            <p class="text-sm text-gray-500">Forward • Team A</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <p class="text-sm font-medium text-gray-700 mb-2">Latest Evaluation</p>
                                    <div class="flex space-x-2">
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">Improved</span>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">Technical Skills</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 2: Team Management -->
                <div class="flex flex-col lg:flex-row-reverse items-center gap-8">
                    <div class="lg:w-1/2">
                        <div class="bg-white rounded-2xl p-8 shadow-lg">
                            <div class="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center mb-6">
                                <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">Team Management</h3>
                            <p class="text-gray-600 mb-6">
                                Create and manage multiple teams with ease. Assign coaches, track rosters, and organize your entire club structure.
                            </p>
                            <ul class="space-y-3 text-gray-600">
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                                    Unlimited teams and flexible roster management
                                </li>
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                                    Coach and trainer assignment system
                                </li>
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                                    Season-based team organization
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="lg:w-1/2">
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl p-8 border border-orange-200">
                            <div class="space-y-4">
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-semibold text-gray-900">Elite Team A</p>
                                            <p class="text-sm text-gray-500">Coach: Mike Johnson</p>
                                        </div>
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">Active</span>
                                    </div>
                                </div>
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-semibold text-gray-900">Youth Development</p>
                                            <p class="text-sm text-gray-500">Coach: Sarah Wilson</p>
                                        </div>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">16 Players</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 3: Trainer Management -->
                <div class="flex flex-col lg:flex-row items-center gap-8">
                    <div class="lg:w-1/2">
                        <div class="bg-white rounded-2xl p-8 shadow-lg">
                            <div class="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center mb-6">
                                <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">Trainer Management</h3>
                            <p class="text-gray-600 mb-6">
                                Streamlined trainer recruitment and management. Send invitations, track credentials, and manage team assignments.
                            </p>
                            <ul class="space-y-3 text-gray-600">
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                                    Email-based trainer invitation system
                                </li>
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                                    Role-based access control and permissions
                                </li>
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                                    Trainer seat management and limits
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="lg:w-1/2">
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl p-8 border border-orange-200">
                            <div class="space-y-4">
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">Mike Johnson</p>
                                            <p class="text-sm text-gray-500">Active • 2 Teams</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">sarah.wilson@email.com</p>
                                            <p class="text-sm text-gray-500">Invitation Pending</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 4: Import/Export -->
                <div class="flex flex-col lg:flex-row-reverse items-center gap-8">
                    <div class="lg:w-1/2">
                        <div class="bg-white rounded-2xl p-8 shadow-lg">
                            <div class="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center mb-6">
                                <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">Data Import/Export</h3>
                            <p class="text-gray-600 mb-6">
                                Seamlessly migrate your existing data and maintain backups. Full CSV support with guided import wizards.
                            </p>
                            <ul class="space-y-3 text-gray-600">
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                                    Step-by-step import wizard with preview
                                </li>
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                                    CSV templates for teams, players, and trainers
                                </li>
                                <li class="flex items-center">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span>
                                    Automated data validation and error handling
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="lg:w-1/2">
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl p-8 border border-orange-200">
                            <div class="space-y-4">
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19v-6m3 6v-6m0 0l3-3m-3 3l-3-3"></path>
                                            </svg>
                                            <span class="font-semibold text-gray-900">Import Players</span>
                                        </div>
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">Ready</span>
                                    </div>
                                </div>
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <span class="font-semibold text-gray-900">Export Data</span>
                                        </div>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">CSV</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ROI Section -->
    <div class="py-16 lg:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Measurable Results for Your Club
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Club owners using our platform report significant improvements in efficiency and organization
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="w-20 h-20 bg-orange-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl font-bold text-orange-600">75%</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Time Savings</h3>
                    <p class="text-gray-600">Less time on administrative tasks, more time for coaching and development</p>
                </div>

                <div class="text-center">
                    <div class="w-20 h-20 bg-orange-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl font-bold text-orange-600">90%</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Data Accuracy</h3>
                    <p class="text-gray-600">Elimination of manual errors and lost information with digital records</p>
                </div>

                <div class="text-center">
                    <div class="w-20 h-20 bg-orange-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl font-bold text-orange-600">60%</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Faster Setup</h3>
                    <p class="text-gray-600">Quicker team organization and player registration processes</p>
                </div>

                <div class="text-center">
                    <div class="w-20 h-20 bg-orange-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl font-bold text-orange-600">100%</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Satisfaction</h3>
                    <p class="text-gray-600">Club managers report improved organization and professional appearance</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="gradient-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-20">
            <div class="text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
                    Ready to Transform Your Club Management?
                </h2>
                <p class="text-xl text-orange-100 mb-8 max-w-3xl mx-auto">
                    Join hundreds of sports clubs already using Club Manager to streamline their operations and improve their professional image.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                    <button class="bg-white text-orange-600 font-bold py-4 px-8 rounded-xl shadow-lg hover:bg-orange-50 transition-all duration-200 transform hover:scale-105">
                        Start Free Trial
                    </button>
                    <button class="bg-orange-600 text-white font-bold py-4 px-8 rounded-xl shadow-lg hover:bg-orange-700 transition-all duration-200 border border-orange-400">
                        Schedule Demo
                    </button>
                </div>
                <p class="text-orange-100 text-sm">
                    No credit card required • 30-day free trial • Setup support included
                </p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="flex items-center justify-center mb-6">
                    <div class="w-10 h-10 bg-orange-600 rounded-xl flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold">Club Manager</span>
                </div>
                <p class="text-gray-400 mb-6">
                    The professional solution for sports club management
                </p>
                <div class="flex flex-col sm:flex-row gap-6 justify-center text-sm text-gray-400">
                    <a href="#" class="hover:text-orange-400 transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-orange-400 transition-colors">Terms of Service</a>
                    <a href="#" class="hover:text-orange-400 transition-colors">Support</a>
                    <a href="#" class="hover:text-orange-400 transition-colors">Contact</a>
                </div>
                <div class="mt-8 pt-8 border-t border-gray-800">
                    <p class="text-gray-400 text-sm">
                        © 2024 Club Manager. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>