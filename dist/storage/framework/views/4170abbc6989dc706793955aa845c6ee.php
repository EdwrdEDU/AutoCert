

<?php $__env->startSection('title', 'Certificate Details'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6 min-w-[800px]">
    <div class="flex items-center justify-between">
        <div>
            <a href="<?php echo e(route('certificates.index')); ?>" class="text-indigo-600 hover:text-indigo-800 font-medium">
                ← Back to Certificates
            </a>
            <h2 class="text-3xl font-bold text-gray-900 mt-2">Certificate Details</h2>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Template Info -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Template</label>
                <p class="text-lg text-gray-900"><?php echo e($certificate->template->name); ?></p>
                <p class="text-sm text-gray-500">
                    Type: <span class="font-medium"><?php echo e(strtoupper($certificate->template->file_type)); ?></span>
                </p>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <div>
                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                        <?php echo e($certificate->status === 'generated' ? 'bg-green-100 text-green-800' : ''); ?>

                        <?php echo e($certificate->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ''); ?>

                        <?php echo e($certificate->status === 'failed' ? 'bg-red-100 text-red-800' : ''); ?>">
                        <?php echo e(ucfirst($certificate->status)); ?>

                    </span>
                </div>
            </div>

            <!-- Generated Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Generated At</label>
                <p class="text-lg text-gray-900">
                    <?php echo e($certificate->generated_at ? $certificate->generated_at->format('M d, Y g:i A') : '-'); ?>

                </p>
            </div>

            <!-- File -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">File</label>
                <p class="text-lg text-gray-900">
                    <?php if($certificate->isGenerated()): ?>
                        <span class="font-medium">
                            <?php echo e(basename($certificate->generated_pdf_path)); ?>

                        </span>
                        <span class="text-sm text-gray-500 ml-2">
                            (<?php echo e(strtoupper(pathinfo($certificate->generated_pdf_path, PATHINFO_EXTENSION))); ?>)
                        </span>
                    <?php else: ?>
                        <span class="text-gray-500">Not yet generated</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Recipient Data -->
        <?php if($certificate->recipient_data): ?>
            <div class="border-t border-gray-200 pt-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Certificate Data</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php $__currentLoopData = $certificate->recipient_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo e($key); ?></label>
                            <p class="text-gray-900 break-words"><?php echo e($value); ?></p>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Actions -->
        <?php if($certificate->isGenerated()): ?>
            <div class="border-t border-gray-200 pt-6 flex gap-4" x-data="{ showDeleteModal: false }">
                <a href="<?php echo e(route('certificates.download', $certificate)); ?>" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition">
                    ⬇️ Download Certificate
                </a>
                <button @click="showDeleteModal = true" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                    🗑️ Delete Certificate
                </button>

                <!-- Delete Confirmation Modal -->
                <div x-show="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showDeleteModal = false">
                    <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 text-center mb-2">Delete Certificate?</h3>
                        <p class="text-gray-600 text-center text-sm mb-6">This action cannot be undone. The certificate will be permanently deleted.</p>
                        <div class="flex gap-3">
                            <button @click="showDeleteModal = false" class="flex-1 px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium transition">
                                Cancel
                            </button>
                            <form action="<?php echo e(route('certificates.destroy', $certificate)); ?>" method="POST" class="flex-1">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="w-full px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-lg font-medium transition">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="border-t border-gray-200 pt-6 flex gap-4" x-data="{ showDeleteModal: false }">
                <button @click="showDeleteModal = true" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                    🗑️ Delete Certificate
                </button>

                <!-- Delete Confirmation Modal -->
                <div x-show="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showDeleteModal = false">
                    <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 text-center mb-2">Delete Certificate?</h3>
                        <p class="text-gray-600 text-center text-sm mb-6">This action cannot be undone. The certificate will be permanently deleted.</p>
                        <div class="flex gap-3">
                            <button @click="showDeleteModal = false" class="flex-1 px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium transition">
                                Cancel
                            </button>
                            <form action="<?php echo e(route('certificates.destroy', $certificate)); ?>" method="POST" class="flex-1">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="w-full px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-lg font-medium transition">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Edwrd\Desktop\HRCert\resources\views/certificates/show.blade.php ENDPATH**/ ?>