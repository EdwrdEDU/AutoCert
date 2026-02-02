<?php $__env->startSection('title', $template->name); ?>

<?php $__env->startSection('content'); ?>
<div x-data="{ showConfirmModal: false, confirmAction: null }">
    <div class="mb-6">
        <a href="<?php echo e(route('templates.index')); ?>" class="text-indigo-600 hover:text-indigo-800 font-medium">
            ← Back to Templates
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-8 pb-6 mb-2">
        <div class="flex justify-between items-start mb-8 gap-6">
            <div class="flex-1">
                <h2 class="text-3xl font-bold text-gray-900 mb-3"><?php echo e($template->name); ?></h2>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                        <?php echo e($template->file_type === 'pdf' ? 'bg-red-100 text-red-800' : ''); ?>

                        <?php echo e($template->file_type === 'docx' ? 'bg-blue-100 text-blue-800' : ''); ?>

                        <?php echo e($template->file_type === 'pptx' ? 'bg-orange-100 text-orange-800' : ''); ?>">
                        <?php echo e(strtoupper($template->file_type)); ?>

                    </span>
                    <span class="text-gray-500 text-sm">Uploaded <?php echo e($template->created_at->format('M d, Y')); ?></span>
                </div>
            </div>
            <div class="flex gap-3 flex-shrink-0">
                <form action="<?php echo e(route('templates.reanalyze', $template)); ?>" method="POST" class="inline" id="reanalyzeForm">
                    <?php echo csrf_field(); ?>
                    <button type="button" 
                            @click="showConfirmModal = true; confirmAction = () => document.getElementById('reanalyzeForm').submit()"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded font-medium transition">
                        🔄 Re-analyze
                    </button>
                </form>
                <a href="<?php echo e(route('certificates.create', $template)); ?>" 
               class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded font-medium transition">
                Generate Certificates
            </a>
        </div>
    </div>
</div>

<!-- Template Fields -->
<div class="bg-white rounded-lg shadow-md p-8">
    <h3 class="text-2xl font-bold text-gray-900 mb-6">Editable Fields (<?php echo e($template->fields->count()); ?>)</h3>

    <?php if($template->fields->isEmpty()): ?>
        <div class="text-center py-12">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="mt-4 text-gray-500">No fields detected. Try re-analyzing the template or ensure it contains placeholders.</p>
        </div>
    <?php else: ?>
        <form action="<?php echo e(route('templates.update-fields', $template)); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            <div class="space-y-4">
                <?php $__currentLoopData = $template->fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition">
                        <input type="hidden" name="fields[<?php echo e($index); ?>][id]" value="<?php echo e($field->id); ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Field Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Field Name</label>
                                <input type="text" 
                                       value="<?php echo e($field->field_name); ?>" 
                                       disabled
                                       class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-50 text-gray-600">
                                <p class="mt-1 text-xs text-gray-500">Placeholder: <code class="bg-gray-100 px-1 rounded"><?php echo e($field->placeholder); ?></code></p>
                            </div>

                            <!-- Field Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <select name="fields[<?php echo e($index); ?>][field_type]" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500">
                                    <option value="text" <?php echo e($field->field_type === 'text' ? 'selected' : ''); ?>>Text</option>
                                    <option value="date" <?php echo e($field->field_type === 'date' ? 'selected' : ''); ?>>Date</option>
                                    <option value="auto_id" <?php echo e($field->field_type === 'auto_id' ? 'selected' : ''); ?>>Auto ID</option>
                                    <option value="number" <?php echo e($field->field_type === 'number' ? 'selected' : ''); ?>>Number</option>
                                </select>
                            </div>

                            <!-- Required -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Required</label>
                                <div class="flex items-center h-10">
                                    <input type="hidden" name="fields[<?php echo e($index); ?>][is_required]" value="0">
                                    <input type="checkbox" 
                                           name="fields[<?php echo e($index); ?>][is_required]" 
                                           value="1"
                                           <?php echo e($field->is_required ? 'checked' : ''); ?>

                                           class="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-600">Required field</span>
                                </div>
                            </div>

                            <!-- Default Value -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Default Value</label>
                                <input type="text" 
                                       name="fields[<?php echo e($index); ?>][default_value]" 
                                       value="<?php echo e($field->default_value); ?>"
                                       placeholder="Optional default"
                                       class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" 
                        class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium shadow-md transition">
                    Save Field Settings
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- Generated Certificates -->
<div class="bg-white rounded-lg shadow-md p-8 mt-6">
    <h3 class="text-2xl font-bold text-gray-900 mb-4">Generated Certificates</h3>
    <p class="text-gray-600">
        Total generated: <span class="font-semibold"><?php echo e($template->certificates->count()); ?></span>
    </p>
    <?php if($template->certificates->count() > 0): ?>
        <a href="<?php echo e(route('certificates.index', ['template_id' => $template->id])); ?>" 
           class="mt-4 inline-block text-indigo-600 hover:text-indigo-800 font-medium">
            View all certificates →
        </a>
    <?php endif; ?>
</div>

    <!-- Confirmation Modal -->
    <div x-show="showConfirmModal" 
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         x-cloak>
        <div class="bg-white rounded-lg shadow-2xl p-8 max-w-md w-full mx-4">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full">
                <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 5.5H2a1.5 1.5 0 00-1.5 1.5v6a1.5 1.5 0 001.5 1.5h16a1.5 1.5 0 001.5-1.5V7a1.5 1.5 0 00-1.5-1.5zm-7 9a2 2 0 110-4 2 2 0 010 4z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 text-center mb-2">Re-analyze Template</h3>
            <p class="text-gray-600 text-center mb-6">
                This will re-scan the template and reset all field settings. Continue?
            </p>
            <div class="flex gap-3">
                <button @click="showConfirmModal = false; confirmAction = null"
                        class="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded font-medium transition">
                    Cancel
                </button>
                <button @click="if(confirmAction) confirmAction(); showConfirmModal = false"
                        class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded font-medium transition">
                    Continue
                </button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Edwrd\Desktop\HRCert\resources\views/templates/show.blade.php ENDPATH**/ ?>