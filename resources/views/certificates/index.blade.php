@extends('layouts.app')

@section('title', 'Certificates')

@section('content')
<div class="mb-10 flex justify-between items-center min-w-[800px]">
    <h2 class="text-3xl font-bold text-gray-900">Generated Certificates</h2>
</div>

@if($certificates->isEmpty())
    <div class="bg-white rounded-lg shadow-md p-16 text-center min-w-[800px] mt-8">
        <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-4 text-xl font-medium text-gray-900">No certificates generated yet</h3>
        <p class="mt-2 text-gray-500">Start by selecting a template and generating certificates.</p>
        <a href="{{ route('templates.index') }}" 
           class="mt-6 inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium transition">
            View Templates
        </a>
    </div>
@else
    <div class="bg-white rounded-lg shadow-md overflow-hidden min-w-[800px]" x-data="{ deleteId: null }">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Template</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generated</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($certificates as $certificate)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $certificate->recipient_name ?? 'N/A' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $certificate->template->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $certificate->status === 'generated' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $certificate->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $certificate->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ ucfirst($certificate->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $certificate->generated_at ? $certificate->generated_at->format('M d, Y H:i') : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('certificates.show', $certificate) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                <button @click="deleteId = {{ $certificate->id }}" type="button" class="text-red-600 hover:text-red-900">Delete</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Delete Confirmation Modal -->
        <div x-show="deleteId" 
             x-cloak
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" 
             @click.self="deleteId = null">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 text-center mb-2">Delete Certificate?</h3>
                <p class="text-gray-600 text-center text-sm mb-6">This action cannot be undone. The certificate will be permanently deleted.</p>
                <div class="flex gap-3">
                    <button @click="deleteId = null" 
                            type="button"
                            class="flex-1 px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium transition">
                        Cancel
                    </button>
                    <form x-bind:action="'{{ route('certificates.destroy', '') }}/' + deleteId" 
                          method="POST" 
                          class="flex-1"
                          @submit="if (!deleteId || deleteId <= 0) { $event.preventDefault(); alert('Invalid certificate ID'); deleteId = null; }">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                x-bind:disabled="!deleteId"
                                class="w-full px-4 py-2 text-white bg-red-600 hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed rounded-lg font-medium transition">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6">
        {{ $certificates->links() }}
    </div>
@endif
@endsection
