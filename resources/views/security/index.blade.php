@extends('layouts.app')

@section('title', 'Security Logs')
@section('page-title', 'Security Logs')
@section('page-description', 'Monitor security events and threats across all applications')

@section('content')
<div class="space-y-6" x-data="securityPage()">

    <!-- Alert Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Critical</p>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($criticalCount ?? 0) }}</p>
                </div>
                <i class="fas fa-exclamation-circle text-red-600 text-2xl"></i>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">High</p>
                    <p class="text-2xl font-bold text-orange-600">{{ number_format($highCount ?? 0) }}</p>
                </div>
                <i class="fas fa-exclamation-triangle text-orange-600 text-2xl"></i>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Medium</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ number_format($mediumCount ?? 0) }}</p>
                </div>
                <i class="fas fa-exclamation text-yellow-600 text-2xl"></i>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Resolved</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($resolvedCount ?? 0) }}</p>
                </div>
                <i class="fas fa-check-circle text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Application</label>
                <select class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Applications</option>
                    @foreach($applications as $app)
                    <option value="{{ $app->id }}">{{ $app->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Event Type</label>
                <select class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Events</option>
                    <option value="ddos_attempt">DDoS Attempt</option>
                    <option value="brute_force">Brute Force</option>
                    <option value="suspicious_activity">Suspicious Activity</option>
                    <option value="blocked_ip">Blocked IP</option>
                    <option value="throttle_limit">Throttle Limit</option>
                    <option value="unauthorized_access">Unauthorized Access</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Severity</label>
                <select class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Severities</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="unresolved">Unresolved</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">IP Address</label>
                <input
                    type="text"
                    placeholder="Search by IP..."
                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                >
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

    <!-- Security Events Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Application</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($securityLogs as $log)
                    <tr class="hover:bg-gray-50 {{ !$log->is_resolved && $log->severity === 'critical' ? 'bg-red-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $log->created_at->format('H:i:s') }}</div>
                            <div class="text-xs text-gray-500">{{ $log->created_at->format('d M Y') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $log->app_name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @php
                                    $icons = [
                                        'ddos_attempt' => 'fa-bomb',
                                        'brute_force' => 'fa-user-lock',
                                        'suspicious_activity' => 'fa-eye',
                                        'blocked_ip' => 'fa-ban',
                                        'throttle_limit' => 'fa-tachometer-alt',
                                        'unauthorized_access' => 'fa-lock'
                                    ];
                                @endphp
                                <i class="fas {{ $icons[$log->event_type] ?? 'fa-exclamation-triangle' }} text-gray-400 mr-2"></i>
                                <span class="text-sm text-gray-900">{{ str_replace('_', ' ', ucwords($log->event_type)) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $log->severity === 'critical' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $log->severity === 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                                {{ $log->severity === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $log->severity === 'low' ? 'bg-green-100 text-green-800' : '' }}">
                                <i class="fas fa-circle text-xs mr-1"></i>
                                {{ ucfirst($log->severity) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-mono text-gray-900">{{ $log->ip_address }}</span>
                            <button
                                @click="blockIp('{{ $log->ip_address }}')"
                                class="ml-2 text-red-600 hover:text-red-800"
                                title="Block this IP"
                            >
                                <i class="fas fa-ban text-xs"></i>
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->user_email)
                                <div class="text-sm text-gray-900">{{ $log->user_email }}</div>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-semibold text-gray-900">{{ $log->request_count }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->is_resolved)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1"></i> Resolved
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-exclamation-circle mr-1"></i> Open
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center space-x-2">
                                <button
                                    @click="viewDetail({{ $log->id }})"
                                    class="text-blue-600 hover:text-blue-800"
                                    title="View Details"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if(!$log->is_resolved)
                                <button
                                    @click="resolveEvent({{ $log->id }})"
                                    class="text-green-600 hover:text-green-800"
                                    title="Mark as Resolved"
                                >
                                    <i class="fas fa-check"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-shield-alt text-5xl mb-4 text-gray-400"></i>
                            <p class="text-lg">No security events found</p>
                            <p class="text-sm">Your applications are secure!</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-gray-50 px-6 py-4">
            {{ $securityLogs->links() }}
        </div>
    </div>

    <!-- Detail Modal -->
    <div
        x-show="showDetailModal"
        x-cloak
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click.self="showDetailModal = false"
    >
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Security Event Details</h3>
                    <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Event Type</label>
                        <p class="mt-1 text-sm text-gray-900">Brute Force Attack</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Severity</label>
                        <p class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Critical
                            </span>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">IP Address</label>
                        <p class="mt-1 text-sm font-mono text-gray-900">192.168.1.100</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Request Count</label>
                        <p class="mt-1 text-sm text-gray-900">25 attempts</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">URL</label>
                        <p class="mt-1 text-sm text-gray-900 break-all">/login</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Method</label>
                        <p class="mt-1 text-sm text-gray-900">POST</p>
                    </div>
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-700">User Agent</label>
                        <p class="mt-1 text-xs text-gray-900 break-all bg-gray-50 p-3 rounded">
                            Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
                        </p>
                    </div>
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-700">Additional Data</label>
                        <pre class="mt-1 p-3 bg-gray-50 rounded text-xs overflow-x-auto">{{ json_encode(['failed_attempts' => 25, 'time_window' => '5 minutes'], JSON_PRETTY_PRINT) }}</pre>
                    </div>
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-700">Notes</label>
                        <textarea
                            class="mt-1 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            rows="3"
                            placeholder="Add notes about this security event..."
                        ></textarea>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 flex justify-between">
                <div class="space-x-2">
                    <button
                        @click="blockIp('192.168.1.100')"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition"
                    >
                        <i class="fas fa-ban mr-2"></i> Block IP
                    </button>
                    <button
                        @click="resolveEvent(selectedEventId)"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition"
                    >
                        <i class="fas fa-check mr-2"></i> Resolve
                    </button>
                </div>
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
function securityPage() {
    return {
        showDetailModal: false,
        selectedEventId: null,

        viewDetail(id) {
            this.selectedEventId = id;
            this.showDetailModal = true;
            // TODO: Fetch event detail via AJAX
        },

        resolveEvent(id) {
            if (confirm('Mark this security event as resolved?')) {
                // TODO: Implement resolve via AJAX
                console.log('Resolving event:', id);
            }
        },

        blockIp(ip) {
            if (confirm(`Block IP address ${ip}?`)) {
                // TODO: Implement IP blocking via AJAX
                console.log('Blocking IP:', ip);
            }
        }
    }
}
</script>
@endpush