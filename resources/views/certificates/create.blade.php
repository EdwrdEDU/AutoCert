@extends('layouts.app')

@section('title', 'Generate Certificates')

@section('content')
<div x-data="certificateGenerator({{ json_encode(session('imported_recipients', [])) }})" class="space-y-6">
    <div class="mb-6">
        <a href="{{ route('templates.show', $template) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
            ← Back to Template
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-8 min-w-[800px]">
        <h2 class="text-3xl font-bold text-gray-900 mb-2">Generate Certificates</h2>
        <p class="text-gray-600 mb-6">Template: <span class="font-semibold">{{ $template->name }}</span></p>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="text-red-800 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'single'" 
                        :class="activeTab === 'single' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Single Certificate
                </button>
                <button @click="activeTab = 'batch'" 
                        :class="activeTab === 'batch' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Batch Generation
                </button>
            </nav>
        </div>

        <!-- Single Certificate Tab -->
        <div x-show="activeTab === 'single'" x-cloak>
            <form @submit.prevent="generateSingle">
                <div class="space-y-6 mb-6">
                    @foreach($template->fields->where('field_type', '!=', 'auto_id') as $field)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ $field->field_name }}
                                @if($field->is_required)
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>
                            
                            @if($field->field_type === 'date')
                                <input type="date" 
                                       x-model="singleData['{{ $field->field_name }}']"
                                       {{ $field->is_required ? 'required' : '' }}
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            @else
                                <input type="text" 
                                       x-model="singleData['{{ $field->field_name }}']"
                                       {{ $field->is_required ? 'required' : '' }}
                                       placeholder="{{ $field->default_value ?? 'Enter ' . $field->field_name }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="flex gap-4">
                    <button type="submit" 
                            class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                        Generate Certificate
                    </button>
                </div>
            </form>
        </div>

        <!-- Batch Generation Tab -->
        <div x-show="activeTab === 'batch'" x-cloak>
            <!-- CSV/Excel Import -->
            <div class="mb-6">
                <form action="{{ route('certificates.import-csv', $template) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                        <input type="file" name="csv_file" accept=".csv,.xlsx,.xls" class="mb-4" required>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                            📥 Import from CSV/Excel
                        </button>
                        <p class="mt-2 text-sm text-gray-500">
                            Upload CSV or Excel file with headers: {{ $template->fields->where('field_type', '!=', 'auto_id')->pluck('field_name')->join(', ') }}
                        </p>
                    </div>
                </form>
            </div>

            <!-- Manual Entry Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            @foreach($template->fields->where('field_type', '!=', 'auto_id') as $field)
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    {{ $field->field_name }}
                                    @if($field->is_required)<span class="text-red-500">*</span>@endif
                                </th>
                            @endforeach
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(recipient, index) in batchData" :key="index">
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900" x-text="index + 1"></td>
                                @foreach($template->fields->where('field_type', '!=', 'auto_id') as $field)
                                    <td class="px-4 py-3">
                                        <input type="{{ $field->field_type === 'date' ? 'date' : 'text' }}"
                                               x-model="recipient['{{ $field->field_name }}']"
                                               class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-indigo-500"
                                               placeholder="{{ $field->field_name }}">
                                    </td>
                                @endforeach
                                <td class="px-4 py-3">
                                    <button @click="removeRecipient(index)" 
                                            class="text-red-600 hover:text-red-800">
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex gap-4">
                <button @click="addRecipient" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded font-medium transition">
                    + Add Row
                </button>
                <button @click="generateBatch" 
                        class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                    Generate All Certificates
                </button>
            </div>
        </div>
    </div>


                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function certificateGenerator(importedRecipients = []) {
    return {
        activeTab: importedRecipients.length > 0 ? 'batch' : 'single',
        singleData: {},
        batchData: importedRecipients.length > 0 ? importedRecipients : [{}],

        addRecipient() {
            this.batchData.push({});
        },

        removeRecipient(index) {
            this.batchData.splice(index, 1);
        },

        async generateSingle() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("certificates.generate-single", $template) }}';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(csrfInput);
            
            for (const [key, value] of Object.entries(this.singleData)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `data[${key}]`;
                input.value = value;
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
        },

        async generateBatch() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("certificates.generate-batch", $template) }}';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(csrfInput);
            
            this.batchData.forEach((recipient, index) => {
                for (const [key, value] of Object.entries(recipient)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `recipients[${index}][${key}]`;
                    input.value = value;
                    form.appendChild(input);
                }
            });
            
            document.body.appendChild(form);
            form.submit();
        }
    };
}
</script>
@endpush
@endsection
