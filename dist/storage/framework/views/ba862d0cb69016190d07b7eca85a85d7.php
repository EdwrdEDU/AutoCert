<?php $__env->startSection('title', 'Upload Template'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-2xl mx-auto min-w-[800px]">
    <div class="mb-6">
        <a href="<?php echo e(route('templates.index')); ?>" class="text-indigo-600 hover:text-indigo-800 font-medium">
            ← Back to Templates
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">Upload New Template</h2>

        <form action="<?php echo e(route('templates.store')); ?>" method="POST" enctype="multipart/form-data" x-data="fileUpload()">
            <?php echo csrf_field(); ?>

            <!-- Template Name -->
            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Template Name <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="<?php echo e(old('name')); ?>"
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       placeholder="e.g., Seminar Completion Certificate">
                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <!-- File Upload -->
            <div class="mb-6">
                <label for="file" class="block text-sm font-medium text-gray-700 mb-2">
                    Certificate Template File <span class="text-red-500">*</span>
                </label>
                
                <!-- Upload Area -->
                <div x-show="!fileName" 
                     class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-500 transition"
                     @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="handleDrop($event)"
                     :class="{ 'border-indigo-500 bg-indigo-50': isDragging }">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="file" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500">
                                <span>Upload a file</span>
                                <input id="file" 
                                       name="file" 
                                       type="file" 
                                       class="sr-only" 
                                       required 
                                       accept=".docx,.pptx"
                                       @change="handleFileSelect($event)">
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">DOCX or PPTX up to 10MB</p>
                    </div>
                </div>

                <!-- File Selected Display -->
                <div x-show="fileName" 
                     x-cloak
                     class="mt-1 border-2 border-green-300 bg-green-50 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <!-- File Icon -->
                            <div class="flex-shrink-0">
                                <svg class="h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            
                            <!-- File Info -->
                            <div>
                                <p class="text-sm font-medium text-gray-900" x-text="fileName"></p>
                                <p class="text-sm text-gray-500">
                                    <span x-text="fileSize"></span>
                                    <span class="mx-2">•</span>
                                    <span class="uppercase font-semibold" 
                                          :class="{
                                              'text-red-600': fileType === 'pdf',
                                              'text-blue-600': fileType === 'docx',
                                              'text-orange-600': fileType === 'pptx'
                                          }"
                                          x-text="fileType"></span>
                                </p>
                            </div>
                        </div>

                        <!-- Remove Button -->
                        <button type="button" 
                                @click="removeFile()"
                                class="text-red-600 hover:text-red-800 p-2">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Success Indicator -->
                    <div class="mt-4 flex items-center text-green-700">
                        <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm font-medium">File ready to upload</span>
                    </div>
                </div>

                <?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Template Tips</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Use placeholders in the format <code class="bg-blue-100 px-1 rounded">&#123;&#123; FieldName &#125;&#125;</code></li>
                                <li>The system will automatically detect and replace these placeholders</li>
                                <li>Example: <code class="bg-blue-100 px-1 rounded">&#123;&#123; Full Name &#125;&#125;</code>, <code class="bg-blue-100 px-1 rounded">&#123;&#123; Date &#125;&#125;</code>, <code class="bg-blue-100 px-1 rounded">&#123;&#123; Certificate Type &#125;&#125;</code></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end gap-4">
                <a href="<?php echo e(route('templates.index')); ?>" 
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium shadow-md transition">
                    Upload & Analyze Template
                </button>
            </div>
        </form>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function fileUpload() {
    return {
        fileName: '',
        fileSize: '',
        fileType: '',
        isDragging: false,
        
        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.processFile(file);
            }
        },
        
        handleDrop(event) {
            this.isDragging = false;
            const file = event.dataTransfer.files[0];
            if (file) {
                // Set the file to the input element
                const input = document.getElementById('file');
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                input.files = dataTransfer.files;
                
                this.processFile(file);
            }
        },
        
        processFile(file) {
            this.fileName = file.name;
            this.fileSize = this.formatFileSize(file.size);
            this.fileType = file.name.split('.').pop().toLowerCase();
            
            // Validate file type
            const allowedTypes = ['pdf', 'docx', 'pptx'];
            if (!allowedTypes.includes(this.fileType)) {
                alert('Please upload only PDF, DOCX, or PPTX files.');
                this.removeFile();
                return;
            }
            
            // Validate file size (10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB.');
                this.removeFile();
                return;
            }
        },
        
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },
        
        removeFile() {
            this.fileName = '';
            this.fileSize = '';
            this.fileType = '';
            document.getElementById('file').value = '';
        }
    };
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Edwrd\Desktop\HRCert\resources\views/templates/create.blade.php ENDPATH**/ ?>