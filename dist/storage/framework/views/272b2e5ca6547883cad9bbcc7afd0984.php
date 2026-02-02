<?php $__env->startSection('title', 'Templates'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6 flex justify-between items-center">
    <h2 class="text-3xl font-bold text-gray-900">Certificate Templates</h2>
    <a href="<?php echo e(route('templates.create')); ?>" 
       class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium shadow-md transition">
        + Upload New Template
    </a>
</div>

<?php if($templates->isEmpty()): ?>
    <div class="bg-white rounded-lg shadow-md p-16 text-center min-w-[800px]">
        <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-8 text-xl font-medium text-gray-900">No templates yet</h3>
        <p class="mt-4 text-gray-500">Get started by uploading your first certificate template.</p>
        <a href="<?php echo e(route('templates.create')); ?>" 
           class="mt-8 inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-lg font-medium transition">
            Upload Template
        </a>
    </div>
<?php else: ?>
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 min-w-[800px]">
        <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="bg-white rounded-lg shadow-md hover:shadow-xl transition overflow-hidden p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900"><?php echo e($template->name); ?></h3>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full 
                        <?php echo e($template->file_type === 'docx' ? 'bg-blue-100 text-blue-800' : ''); ?>

                        <?php echo e($template->file_type === 'pptx' ? 'bg-orange-100 text-orange-800' : ''); ?>">
                        <?php echo e(strtoupper($template->file_type)); ?>

                    </span>
                </div>

                <div class="space-y-3 text-sm text-gray-600 mb-8">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        <span><?php echo e($template->fields->count()); ?> fields</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span><?php echo e($template->created_at->format('M d, Y')); ?></span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span><?php echo e($template->certificates->count()); ?> generated</span>
                    </div>
                </div>

                <div class="flex gap-3 mb-4">
                    <a href="<?php echo e(route('templates.show', $template)); ?>" 
                       class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-3 rounded text-center font-medium transition">
                        View Details
                    </a>
                    <a href="<?php echo e(route('certificates.create', $template)); ?>" 
                       class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded text-center font-medium transition">
                        Generate
                    </a>
                </div>

                <form action="<?php echo e(route('templates.destroy', $template)); ?>" method="POST"
                      onsubmit="return confirm('Are you sure you want to delete this template?');">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="w-full text-red-600 hover:text-red-800 text-sm font-medium py-2">
                        Delete Template
                    </button>
                </form>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Edwrd\Desktop\HRCert\resources\views/templates/index.blade.php ENDPATH**/ ?>