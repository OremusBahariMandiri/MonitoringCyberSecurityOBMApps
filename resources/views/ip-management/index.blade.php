@extends('layouts.app')

@section('title', 'IP Management')
@section('page-title', 'IP Management')
@section('page-description', 'Manage IP whitelist, blacklist, and watch list')

@section('content')
<div class="space-y-6" x-data="ipManagementPage()">

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Whitelisted</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($whitelistCount ?? 0) }}</p>
                </div>
                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Blacklisted</p>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($blacklistCount ?? 0) }}</p>
                </div>
                <i class="fas fa-ban text-red-600 text-2xl"></i>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Watch List</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ number_format($watchCount ?? 0) }}</p>
                </div>
                <i class="fas fa-eye text-yellow-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Filters & Add Button -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <select class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Applications</option>
                    <option value="global">Global (All Apps)</option>
                    @foreach($applications as $app)
                    <option value="{{ $app->id }}">{{ $app->name }}</option>
                    @endforeach
                </select>

                <select class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="whitelist">Whitelist</option>
                    <option value="blacklist">Blacklist</option>
                    <option value="watch">Watch List</option>
                </select>

                <select class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="active">Active Only</option>
                    <option value="expired">Expired</option>
                    <option value="all">All Status</option>
                </select>
            </div>

            <button
                @click="showAddModal = true"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
            >
                <i class="fas fa-plus mr-2"></i> Add IP
            </button>
        </div>
    </div>

    <!-- IP List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Application</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($ipManagement as $ip)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-mono font-semibold text-gray-900">{{ $ip->ip_address }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $ip->type === 'whitelist' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $ip->type === 'blacklist' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $ip->type === 'watch' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                <i class="fas
                                    {{ $ip->type === 'whitelist' ? 'fa-check-circle' : '' }}
                                    {{ $ip->type === 'blacklist' ? 'fa-ban' : '' }}
                                    {{ $ip->type === 'watch' ? 'fa-eye' : '' }}
                                    mr-1"></i>
                                {{ ucfirst($ip->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($ip->application_id)
                                <span class="text-sm text-gray-900">{{ $ip->application->name ?? '-' }}</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    <i class="fas fa-globe mr-1"></i> Global
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-900 max-w-xs truncate" title="{{ $ip->reason }}">
                                {{ $ip->reason ?? '-' }}
                            </p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($ip->expires_at)
                                <div class="text-sm text-gray-900">{{ $ip->expires_at->format('d M Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $ip->expires_at->diffForHumans() }}</div>
                            @else
                                <span class="text-sm text-gray-500">Never</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($ip->is_active && (!$ip->expires_at || $ip->expires_at->isFuture()))
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-circle text-green-500 text-xs mr-1"></i> Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <i class="fas fa-circle text-gray-500 text-xs mr-1"></i> Inactive
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center space-x-2">
                                <button
                                    @click="editIp({{ $ip->id }})"
                                    class="text-blue-600 hover:text-blue-800"
                                    title="Edit"
                                >
                                    <i class="fas fa-edit"></i>
                                </button>
                                @if($ip->is_active)
                                <button
                                    @click="deactivateIp({{ $ip->id }})"
                                    class="text-orange-600 hover:text-orange-800"
                                    title="Deactivate"
                                >
                                    <i class="fas fa-pause-circle"></i>
                                </button>
                                @else
                                <button
                                    @click="activateIp({{ $ip->id }})"
                                    class="text-green-600 hover:text-green-800"
                                    title="Activate"
                                >
                                    <i class="fas fa-play-circle"></i>
                                </button>
                                @endif
                                <button
                                    @click="deleteIp({{ $ip->id }})"
                                    class="text-red-600 hover:text-red-800"
                                    title="Delete"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-network-wired text-5xl mb-4 text-gray-400"></i>
                            <p class="text-lg">No IP addresses found</p>
                            <p class="text-sm">Add an IP address to get started</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-gray-50 px-6 py-4">
            {{ $ipManagement->links() }}
        </div>
    </div>

    <!-- Add/Edit IP Modal -->
    <div
        x-show="showAddModal"
        x-cloak
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click.self="showAddModal = false"
    >
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold" x-text="editMode ? 'Edit IP Address' : 'Add IP Address'"></h3>
                    <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <form @submit.prevent="saveIp" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">IP Address *</label>
                        <input
                            type="text"
                            x-model="formData.ip_address"
                            placeholder="192.168.1.100"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            required
                        >
                        <p class="mt-1 text-xs text-gray-500">Enter IPv4 or IPv6 address</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                        <select
                            x-model="formData.type"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            required
                        >
                            <option value="">Select Type</option>
                            <option value="whitelist">Whitelist</option>
                            <option value="blacklist">Blacklist</option>
                            <option value="watch">Watch List</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Application</label>
                        <select
                            x-model="formData.application_id"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">Global (All Applications)</option>
                            @foreach($applications as $app)
                            <option value="{{ $app->id }}">{{ $app->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Leave empty to apply to all applications</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
                        <textarea
                            x-model="formData.reason"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            rows="3"
                            placeholder="Why is this IP being added?"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Expires At</label>
                        <input
                            type="datetime-local"
                            x-model="formData.expires_at"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                        <p class="mt-1 text-xs text-gray-500">Leave empty for permanent</p>
                    </div>

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            x-model="formData.is_active"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            id="is_active"
                        >
                        <label for="is_active" class="ml-2 text-sm text-gray-700">Active</label>
                    </div>
                </form>
            </div>
            <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                <button
                    @click="showAddModal = false"
                    type="button"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition"
                >
                    Cancel
                </button>
                <button
                    @click="saveIp"
                    type="button"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                >
                    <i class="fas fa-save mr-2"></i>
                    <span x-text="editMode ? 'Update' : 'Add IP'"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function ipManagementPage() {
    return {
        showAddModal: false,
        editMode: false,
        formData: {
            ip_address: '',
            type: '',
            application_id: '',
            reason: '',
            expires_at: '',
            is_active: true
        },

        editIp(id) {
            this.editMode = true;
            this.showAddModal = true;
            // TODO: Fetch IP data via AJAX
            console.log('Editing IP:', id);
        },

        saveIp() {
            // TODO: Implement save via AJAX
            console.log('Saving IP:', this.formData);
            this.showAddModal = false;
        },

        deactivateIp(id) {
            if (confirm('Deactivate this IP address?')) {
                // TODO: Implement deactivate via AJAX
                console.log('Deactivating IP:', id);
            }
        },

        activateIp(id) {
            if (confirm('Activate this IP address?')) {
                // TODO: Implement activate via AJAX
                console.log('Activating IP:', id);
            }
        },

        deleteIp(id) {
            if (confirm('Delete this IP address? This action cannot be undone.')) {
                // TODO: Implement delete via AJAX
                console.log('Deleting IP:', id);
            }
        }
    }
}
</script>
@endpush