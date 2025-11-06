@extends('layouts.app')

@section('title', 'Active Sessions')
@section('page-title', 'Active Sessions')
@section('page-description', 'Monitor real-time user sessions across all applications')

@section('content')
<div class="space-y-6" x-data="sessionsPage()">

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Active Now</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($activeSessions->total()) }}</p>
                </div>
                <i class="fas fa-user-check text-green-600 text-2xl"></i>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Idle Sessions</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ number_format($idleSessions ?? 0) }}</p>
                </div>
                <i class="fas fa-clock text-yellow-600 text-2xl"></i>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Today's Logins</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($todayLogins ?? 0) }}</p>
                </div>
                <i class="fas fa-sign-in-alt text-blue-600 text-2xl"></i>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Unique IPs</p>
                    <p class="text-2xl font-bold text-purple-600">{{ number_format($uniqueIps ?? 0) }}</p>
                </div>
                <i class="fas fa-network-wired text-purple-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Filters & Actions -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <select class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Applications</option>
                    @foreach($applications as $app)
                    <option value="{{ $app->id }}">{{ $app->name }}</option>
                    @endforeach
                </select>

                <select class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="active">Active Sessions</option>
                    <option value="idle">Idle Sessions (>5min)</option>
                    <option value="all">All Sessions</option>
                </select>
            </div>

            <div class="flex items-center space-x-3">
                <button
                    @click="refreshSessions"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                >
                    <i class="fas fa-sync-alt mr-2"></i> Refresh
                </button>
                <button
                    @click="closeIdleSessions"
                    class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition"
                >
                    <i class="fas fa-user-times mr-2"></i> Close Idle Sessions
                </button>
            </div>
        </div>
    </div>

    <!-- Sessions Grid View -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse($activeSessions as $session)
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
            <!-- Header -->
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        {{ $session->app_name }}
                    </span>
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        <span class="text-xs text-green-600 font-medium">Active</span>
                    </div>
                </div>
                <h3 class="font-semibold text-gray-800 truncate">{{ $session->user_name ?? $session->user_email }}</h3>
                <p class="text-xs text-gray-500 truncate">{{ $session->user_email }}</p>
            </div>

            <!-- Body -->
            <div class="p-4 space-y-3">
                <div class="flex items-center text-sm">
                    <i class="fas fa-globe text-gray-400 w-5"></i>
                    <span class="text-gray-700">{{ $session->ip_address }}</span>
                </div>

                <div class="flex items-center text-sm">
                    <i class="fas fa-clock text-gray-400 w-5"></i>
                    <span class="text-gray-700">
                        Logged in {{ $session->login_at->diffForHumans() }}
                    </span>
                </div>

                <div class="flex items-center text-sm">
                    <i class="fas fa-history text-gray-400 w-5"></i>
                    <span class="text-gray-700">
                        Last activity {{ $session->last_activity->diffForHumans() }}
                    </span>
                </div>

                <div class="flex items-center text-sm">
                    <i class="fas fa-hourglass-half text-gray-400 w-5"></i>
                    <span class="text-gray-700">
                        Idle: <span class="{{ $session->idle_minutes > 10 ? 'text-orange-600 font-medium' : '' }}">
                            {{ $session->idle_minutes }} minutes
                        </span>
                    </span>
                </div>

                <!-- User Agent -->
                <div class="pt-2 border-t border-gray-100">
                    <p class="text-xs text-gray-500 truncate" title="{{ $session->user_agent }}">
                        <i class="fas fa-desktop text-gray-400 mr-1"></i>
                        {{ Str::limit($session->user_agent, 50) }}
                    </p>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="p-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
                <button
                    @click="viewSessionDetail({{ $session->id }})"
                    class="text-blue-600 hover:text-blue-800 text-sm"
                >
                    <i class="fas fa-info-circle mr-1"></i> Details
                </button>
                <button
                    @click="forceLogout({{ $session->id }})"
                    class="text-red-600 hover:text-red-800 text-sm"
                >
                    <i class="fas fa-sign-out-alt mr-1"></i> Force Logout
                </button>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <i class="fas fa-user-slash text-6xl text-gray-400 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No Active Sessions</h3>
                <p class="text-gray-500">There are no active user sessions at the moment</p>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($activeSessions->hasPages())
    <div class="bg-white rounded-lg shadow p-4">
        {{ $activeSessions->links() }}
    </div>
    @endif

    <!-- Session Detail Modal -->
    <div
        x-show="showDetailModal"
        x-cloak
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click.self="showDetailModal = false"
    >
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Session Details</h3>
                    <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">User Information</h4>
                        <div class="space-y-2">
                            <div>
                                <label class="text-xs text-gray-500">Name</label>
                                <p class="text-sm text-gray-900">John Doe</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Email</label>
                                <p class="text-sm text-gray-900">john@example.com</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">User ID</label>
                                <p class="text-sm text-gray-900">123</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Session Information</h4>
                        <div class="space-y-2">
                            <div>
                                <label class="text-xs text-gray-500">IP Address</label>
                                <p class="text-sm text-gray-900">192.168.1.100</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Login Time</label>
                                <p class="text-sm text-gray-900">2 hours ago</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Last Activity</label>
                                <p class="text-sm text-gray-900">5 minutes ago</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Browser & Device</h4>
                        <p class="text-xs text-gray-600 bg-gray-50 p-3 rounded">
                            Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
                        </p>
                    </div>
                    <div class="col-span-2">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Recent Activities</h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <div class="text-xs bg-gray-50 p-2 rounded">
                                <span class="text-gray-500">10:30 AM</span> -
                                <span class="text-gray-700">Viewed Dashboard</span>
                            </div>
                            <div class="text-xs bg-gray-50 p-2 rounded">
                                <span class="text-gray-500">10:25 AM</span> -
                                <span class="text-gray-700">Updated Profile</span>
                            </div>
                            <div class="text-xs bg-gray-50 p-2 rounded">
                                <span class="text-gray-500">10:20 AM</span> -
                                <span class="text-gray-700">Login Successful</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 flex justify-between">
                <button
                    @click="forceLogout(selectedSessionId)"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition"
                >
                    <i class="fas fa-sign-out-alt mr-2"></i> Force Logout
                </button>
                <button
                    @click="showDetailModal = false"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function sessionsPage() {
    return {
        showDetailModal: false,
        selectedSessionId: null,

        viewSessionDetail(id) {
            this.selectedSessionId = id;
            this.showDetailModal = true;
            // TODO: Fetch session detail via AJAX
        },

        forceLogout(id) {
            if (confirm('Are you sure you want to force logout this session?')) {
                // TODO: Implement force logout via AJAX
                console.log('Force logout session:', id);
            }
        },

        refreshSessions() {
            location.reload();
        },

        closeIdleSessions() {
            if (confirm('Close all idle sessions (>15 minutes)?')) {
                // TODO: Implement close idle sessions via AJAX
                console.log('Closing idle sessions...');
            }
        }
    }
}

// Auto refresh every 30 seconds
setInterval(() => {
    location.reload();
}, 30000);
</script>
@endpush