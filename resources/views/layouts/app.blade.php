<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Activity Hub') }} - @yield('title', 'Dashboard')</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-100" x-data="{ sidebarOpen: true }">
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside
            class="bg-gray-900 text-white transition-all duration-300"
            :class="sidebarOpen ? 'w-64' : 'w-20'"
        >
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="flex items-center justify-between p-4 border-b border-gray-800">
                    <div class="flex items-center space-x-3" x-show="sidebarOpen" x-cloak>
                        <div class="bg-blue-600 w-10 h-10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-white"></i>
                        </div>
                        <span class="font-bold text-lg">Activity Hub</span>
                    </div>
                    <div x-show="!sidebarOpen" class="mx-auto">
                        <div class="bg-blue-600 w-10 h-10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-white"></i>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 overflow-y-auto py-4">
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition {{ request()->routeIs('dashboard') ? 'bg-gray-800 text-white border-l-4 border-blue-600' : '' }}">
                        <i class="fas fa-home w-6"></i>
                        <span class="ml-3" x-show="sidebarOpen" x-cloak>Dashboard</span>
                    </a>

                    <a href="{{ route('activities.index') }}"
                       class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition {{ request()->routeIs('activities.*') ? 'bg-gray-800 text-white border-l-4 border-blue-600' : '' }}">
                        <i class="fas fa-list w-6"></i>
                        <span class="ml-3" x-show="sidebarOpen" x-cloak>Activities</span>
                    </a>

                    <a href="{{ route('sessions.index') }}"
                       class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition {{ request()->routeIs('sessions.*') ? 'bg-gray-800 text-white border-l-4 border-blue-600' : '' }}">
                        <i class="fas fa-users w-6"></i>
                        <span class="ml-3" x-show="sidebarOpen" x-cloak>Active Sessions</span>
                    </a>

                    {{-- <a href="{{ route('security.index') }}"
                       class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition {{ request()->routeIs('security.*') ? 'bg-gray-800 text-white border-l-4 border-blue-600' : '' }}">
                        <i class="fas fa-shield-alt w-6"></i>
                        <span class="ml-3" x-show="sidebarOpen" x-cloak>Security Logs</span>
                    </a> --}}

                    <a href="{{ route('ip-management.index') }}"
                       class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition {{ request()->routeIs('ip-management.*') ? 'bg-gray-800 text-white border-l-4 border-blue-600' : '' }}">
                        <i class="fas fa-network-wired w-6"></i>
                        <span class="ml-3" x-show="sidebarOpen" x-cloak>IP Management</span>
                    </a>

                    {{-- <a href="{{ route('data-changes.index') }}"
                       class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition {{ request()->routeIs('data-changes.*') ? 'bg-gray-800 text-white border-l-4 border-blue-600' : '' }}">
                        <i class="fas fa-history w-6"></i>
                        <span class="ml-3" x-show="sidebarOpen" x-cloak>Data Changes</span>
                    </a> --}}

                    {{-- <a href="{{ route('alerts.index') }}"
                       class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition {{ request()->routeIs('alerts.*') ? 'bg-gray-800 text-white border-l-4 border-blue-600' : '' }}">
                        <i class="fas fa-bell w-6"></i>
                        <span class="ml-3" x-show="sidebarOpen" x-cloak>Alerts</span>
                    </a> --}}

                    {{-- <a href="{{ route('statistics.index') }}"
                       class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition {{ request()->routeIs('statistics.*') ? 'bg-gray-800 text-white border-l-4 border-blue-600' : '' }}">
                        <i class="fas fa-chart-bar w-6"></i>
                        <span class="ml-3" x-show="sidebarOpen" x-cloak>Statistics</span>
                    </a> --}}

                    {{-- <a href="{{ route('applications.index') }}"
                       class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition {{ request()->routeIs('applications.*') ? 'bg-gray-800 text-white border-l-4 border-blue-600' : '' }}">
                        <i class="fas fa-cube w-6"></i>
                        <span class="ml-3" x-show="sidebarOpen" x-cloak>Applications</span>
                    </a> --}}
                </nav>

                <!-- Sidebar Toggle -->
                <div class="p-4 border-t border-gray-800">
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="w-full flex items-center justify-center px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg transition"
                    >
                        <i class="fas" :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">@yield('page-title', 'Dashboard')</h1>
                        <p class="text-sm text-gray-600">@yield('page-description', 'Monitor your applications')</p>
                    </div>

                    <div class="flex items-center space-x-4">
                        <!-- Refresh Button -->
                        <button
                            onclick="location.reload()"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition"
                        >
                            <i class="fas fa-sync-alt"></i>
                        </button>

                        <!-- Time Display -->
                        <div class="text-sm text-gray-600">
                            <i class="fas fa-clock mr-2"></i>
                            <span id="current-time"></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-gray-100 p-6">
                @if(session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('current-time').textContent = timeString;
        }
        updateTime();
        setInterval(updateTime, 1000);
    </script>

    @stack('scripts')
</body>
</html>