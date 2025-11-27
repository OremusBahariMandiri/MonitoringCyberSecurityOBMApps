@extends('layouts.app')

@section('title', 'Activities')
@section('page-title', 'Activities Log')
@section('page-description', 'Track all user activities across applications')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('content')
<div class="space-y-6" x-data="activitiesPage()">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if(session('error') || isset($error))
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-800">{{ session('error') ?? $error ?? 'An error occurred' }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Filters -->
    <form method="GET" action="{{ route('activities.index') }}" class="bg-white rounded-lg shadow p-6" id="filterForm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Application Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Application</label>
                <select name="application_id" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Applications</option>
                    @foreach($applications as $app)
                    <option value="{{ $app->id }}" {{ request('application_id') == $app->id ? 'selected' : '' }}>
                        {{ $app->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Activity Type Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Activity Type</label>
                <select name="activity_type" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="page_visit" {{ request('activity_type') == 'page_visit' ? 'selected' : '' }}>Page Visit</option>
                    <option value="auth" {{ request('activity_type') == 'auth' ? 'selected' : '' }}>Authentication</option>
                    <option value="data_change" {{ request('activity_type') == 'data_change' ? 'selected' : '' }}>Data Change</option>
                    <option value="api_call" {{ request('activity_type') == 'api_call' ? 'selected' : '' }}>API Call</option>
                    <option value="system" {{ request('activity_type') == 'system' ? 'selected' : '' }}>System</option>
                </select>
            </div>

            <!-- Date Range -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                <select name="date_range" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        @change="toggleCustomDate($event.target.value)">
                    <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ request('date_range') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="7days" {{ request('date_range', '7days') == '7days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30days" {{ request('date_range') == '30days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="custom" {{ request('date_range') == 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>

            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <div class="relative">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search activities..."
                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 pl-10"
                    >
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
            </div>
        </div>

        <!-- Custom Date Range (Hidden by default) -->
        <div x-show="showCustomDate"
             x-cloak
             class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-200">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                <input
                    type="text"
                    name="custom_date_from"
                    value="{{ request('custom_date_from') }}"
                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 custom-date"
                    placeholder="Select start date"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                <input
                    type="text"
                    name="custom_date_to"
                    value="{{ request('custom_date_to') }}"
                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 custom-date"
                    placeholder="Select end date"
                >
            </div>
        </div>

        <!-- Advanced Filters (Collapsible) -->
        <div class="mt-4">
            <button type="button" @click="showAdvanced = !showAdvanced"
                    class="flex items-center text-sm text-gray-600 hover:text-gray-800">
                <i :class="showAdvanced ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="mr-2"></i>
                Advanced Filters
            </button>
        </div>

        <div x-show="showAdvanced" x-cloak class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-200">
            <!-- User Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">User Email</label>
                <input
                    type="text"
                    name="user_email"
                    value="{{ request('user_email') }}"
                    placeholder="user@example.com"
                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <!-- IP Address -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">IP Address</label>
                <input
                    type="text"
                    name="ip_address"
                    value="{{ request('ip_address') }}"
                    placeholder="192.168.1.1"
                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <!-- HTTP Method -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">HTTP Method</label>
                <select name="method" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Methods</option>
                    <option value="GET" {{ request('method') == 'GET' ? 'selected' : '' }}>GET</option>
                    <option value="POST" {{ request('method') == 'POST' ? 'selected' : '' }}>POST</option>
                    <option value="PUT" {{ request('method') == 'PUT' ? 'selected' : '' }}>PUT</option>
                    <option value="PATCH" {{ request('method') == 'PATCH' ? 'selected' : '' }}>PATCH</option>
                    <option value="DELETE" {{ request('method') == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                </select>
            </div>

            <!-- Status Code -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status Code</label>
                <select name="status_code" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="200" {{ request('status_code') == '200' ? 'selected' : '' }}>200 - Success</option>
                    <option value="400" {{ request('status_code') == '400' ? 'selected' : '' }}>400 - Bad Request</option>
                    <option value="401" {{ request('status_code') == '401' ? 'selected' : '' }}>401 - Unauthorized</option>
                    <option value="403" {{ request('status_code') == '403' ? 'selected' : '' }}>403 - Forbidden</option>
                    <option value="404" {{ request('status_code') == '404' ? 'selected' : '' }}>404 - Not Found</option>
                    <option value="500" {{ request('status_code') == '500' ? 'selected' : '' }}>500 - Server Error</option>
                </select>
            </div>
        </div>

        <div class="mt-4 flex justify-between">
            <div class="flex space-x-3">
                <a href="{{ route('activities.index') }}"
                   class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition">
                    <i class="fas fa-redo mr-2"></i> Reset
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
            </div>
            <div class="flex space-x-3">
                {{-- <button type="button" @click="exportData()"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    <i class="fas fa-download mr-2"></i> Export CSV
                </button> --}}
                <button type="button" @click="refreshData()"
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                    <i class="fas fa-sync mr-2"></i> Refresh
                </button>
            </div>
        </div>
    </form>

    <!-- Stats Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Activities</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($totalActivities ?? 0) }}</p>
                </div>
                <i class="fas fa-list text-blue-600 text-2xl"></i>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Success Rate</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($successRate ?? 0, 1) }}%</p>
                </div>
                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Unique Users</p>
                    <p class="text-2xl font-bold text-purple-600">{{ number_format($uniqueUsers ?? 0) }}</p>
                </div>
                <i class="fas fa-users text-purple-600 text-2xl"></i>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Error Rate</p>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($errorRate ?? 0, 1) }}%</p>
                </div>
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Activities Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}"
                               class="group inline-flex items-center hover:text-gray-700">
                                Time
                                <i class="fas fa-sort ml-1 text-gray-400 group-hover:text-gray-600"></i>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Application</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($activities as $activity)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $activity->created_at->format('H:i:s') }}</div>
                            <div class="text-xs text-gray-500">{{ $activity->created_at->format('d M Y') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $activity->application->name ?? $activity->app_name ?? 'Unknown' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $activity->user_name ?? $activity->user_email ?? $activity->user_id ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-500">{{ $activity->user_email ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $activity->activity_name ?? 'Unknown Activity' }}</div>
                            <div class="text-xs text-gray-500">{{ $activity->activity_type ?? 'unknown' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $activity->ip_address ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($activity->method)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium
                                {{ $activity->method === 'GET' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $activity->method === 'POST' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $activity->method === 'PUT' || $activity->method === 'PATCH' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $activity->method === 'DELETE' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ $activity->method }}
                            </span>
                            @else
                            <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($activity->status_code)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $activity->status_code >= 200 && $activity->status_code < 300 ? 'bg-green-100 text-green-800' : '' }}
                                {{ $activity->status_code >= 400 ? 'bg-red-100 text-red-800' : '' }}">
                                {{ $activity->status_code }}
                            </span>
                            @else
                            <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            <a href="{{ route('activities.show', $activity->id) }}"
                               class="text-blue-600 hover:text-blue-800"
                               title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button
                                onclick="deleteActivity({{ $activity->id }})"
                                class="text-red-600 hover:text-red-800"
                                title="Delete Activity"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-5xl mb-4 text-gray-400"></i>
                            <p class="text-lg">No activities found</p>
                            <p class="text-sm">Try adjusting your filters</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($activities && method_exists($activities, 'links'))
        <div class="bg-gray-50 px-6 py-4">
            {{ $activities->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Confirm Delete</h3>
                <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="text-sm text-gray-600 mb-6">Are you sure you want to delete this activity? This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button onclick="closeDeleteModal()"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition">
                    Cancel
                </button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
function activitiesPage() {
    return {
        showCustomDate: {{ request('date_range') === 'custom' ? 'true' : 'false' }},
        showAdvanced: {{ request()->hasAny(['user_email', 'ip_address', 'method', 'status_code']) ? 'true' : 'false' }},

        init() {
            // Initialize date pickers
            flatpickr('.custom-date', {
                dateFormat: 'Y-m-d',
                allowInput: true,
                locale: {
                    firstDayOfWeek: 1
                }
            });
        },

        toggleCustomDate(value) {
            this.showCustomDate = value === 'custom';
        },



        refreshData() {
            window.location.reload();
        }
    }
}

// Delete functions
function deleteActivity(activityId) {
    document.getElementById('deleteForm').action = `/activities/${activityId}`;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
}

// Auto-submit form on select change for better UX
document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('select[name="date_range"]');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            if (this.value !== 'custom') {
                // Auto-submit form for non-custom date ranges
                setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 100);
            }
        });
    });
});
</script>
@endpush