

<?php $__env->startSection('title', 'Batch Generation Results'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="<?php echo e(route('certificates.create', $template)); ?>" class="text-indigo-600 hover:text-indigo-800 font-medium">
                ← Back to Certificate Generation
            </a>
            <h2 class="text-3xl font-bold text-gray-900 mt-2">Batch Generation Results</h2>
        </div>
        <a href="<?php echo e(route('certificates.index')); ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition">
            View All Certificates
        </a>
    </div>

    <?php if(session('success')): ?>
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-8 min-w-[800px]">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">
            Template: <?php echo e($template->name); ?>

        </h3>

        <?php if(count($results) > 0): ?>
            <!-- Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm font-medium text-gray-500">Total Processed</div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo e(count($results)); ?></div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm font-medium text-green-700">Successful</div>
                    <div class="text-2xl font-bold text-green-900">
                        <?php echo e(collect($results)->where('success', true)->count()); ?>

                    </div>
                </div>
                <div class="bg-red-50 p-4 rounded-lg">
                    <div class="text-sm font-medium text-red-700">Failed</div>
                    <div class="text-2xl font-bold text-red-900">
                        <?php echo e(collect($results)->where('success', false)->count()); ?>

                    </div>
                </div>
            </div>

            <!-- Results Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                #
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Recipient
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php $__currentLoopData = $results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $result): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo e($index + 1); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo e($result['recipient'] ?? 'Unknown'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if($result['success']): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            ✓ Success
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            ✗ Failed
                                        </span>
                                        <?php if(isset($result['error'])): ?>
                                            <div class="mt-1 text-xs text-red-600">
                                                <?php echo e($result['error']); ?>

                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if($result['success'] && isset($result['certificate_id'])): ?>
                                        <a href="<?php echo e(route('certificates.show', $result['certificate_id'])); ?>" 
                                           class="text-indigo-600 hover:text-indigo-900">
                                            View
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex gap-4">
                <?php if(collect($results)->where('success', true)->count() > 0): ?>
                    <form action="<?php echo e(route('certificates.export-zip')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <?php $__currentLoopData = collect($results)->where('success', true); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $result): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if(isset($result['certificate_id'])): ?>
                                <input type="hidden" name="certificate_ids[]" value="<?php echo e($result['certificate_id']); ?>">
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                            📦 Download All as ZIP
                        </button>
                    </form>
                <?php endif; ?>

                <a href="<?php echo e(route('certificates.create', $template)); ?>" 
                   class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition">
                    Generate More Certificates
                </a>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-600">No results to display</p>
                <a href="<?php echo e(route('certificates.create', $template)); ?>" 
                   class="mt-4 inline-block px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition">
                    Generate Certificates
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Edwrd\Desktop\HRCert\resources\views/certificates/batch-results.blade.php ENDPATH**/ ?>