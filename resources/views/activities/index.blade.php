@extends('layouts.app')

@section('title', 'Activities')
@section('page-title', 'Activities Log')
@section('page-description', 'Track all user activities across applications')

@section('content')
<div class="space-y-6" x-data="activitiesPage()">

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Application Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Application</label>
                <select class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Applications</option>
                    @foreach($applications as $app)
                    <option value="{{ $app->id }}">{{ $app->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Activity Type Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Activity Type</label>
                <select class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="user_action">User Action</option>
                    <option value="system">System</option>
                    <option value="api_call">API Call</option>
                    <option value="auth">Authentication</option>
                </select>
            </div>

            <!-- Date Range -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                <select class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="7days" selected>Last 7 Days</option>
                    <option value="30days">Last 30 Days</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>

            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <div class="relative">
                    <input
                        type="text"
                        placeholder="Search activities..."
                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 pl-10"
                    >
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
            </div>
        </div>

        <div class="mt-4 flex justify-end space-x-3">
            <button class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition">
                <i class="fas fa-redo mr-2"></i> Reset
            </button>
            <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="fas fa-filter mr-2"></i> Apply Filters
            </button>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Activities</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($activities->total()) }}</p>
                </div>
                <i class="fas fa-list text-blue-600 text-2xl"></i>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Success Rate</p>
                    <p class="text-2xl font-bold text-green-600">98.5%</p>
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
                    <p class="text-2xl font-bold text-red-600">1.5%</p>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
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
                                {{ $activity->app_name }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $activity->user_id ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-500">{{ $activity->user_email ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $activity->activity_name }}</div>
                            <div class="text-xs text-gray-500">{{ $activity->activity_type }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $activity->ip_address }}
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
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button
                                @click="showDetail({{ $activity->id }})"
                                class="text-blue-600 hover:text-blue-800"
                                title="View Details"
                            >
                                <i class="fas fa-eye"></i>
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
        <div class="bg-gray-50 px-6 py-4">
            {{ $activities->links() }}
        </div>
    </div>

    <!-- Detail Modal -->
    <div
        x-show="showDetailModal"
        x-cloak
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click.self="showDetailModal = false"
    >
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Activity Details</h3>
                    <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Activity Name</label>
                        <p class="mt-1 text-sm text-gray-900">Create New Post</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Description</label>
                        <p class="mt-1 text-sm text-gray-900">User created a new blog post</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">URL</label>
                        <p class="mt-1 text-sm text-gray-900 break-all">/api/posts</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">User Agent</label>
                        <p class="mt-1 text-sm text-gray-900 break-all">Mozilla/5.0 (Windows NT 10.0; Win64; x64)</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Additional Data</label>
                        <pre class="mt-1 p-3 bg-gray-50 rounded text-xs overflow-x-auto">{{ json_encode(['post_id' => 123, 'title' => 'Example Post'], JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 flex justify-end">
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
function activitiesPage() {
    return {
        showDetailModal: false,
        selectedActivity: null,

        showDetail(id) {
            this.selectedActivity = id;
            this.showDetailModal = true;
            // TODO: Fetch activity detail via AJAX
        }
    }
}
</script>
@endpush