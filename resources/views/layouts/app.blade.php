<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Certificate Generator')</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js for interactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <!-- Paper Logo SVG -->
                    <svg class="w-8 h-8 text-indigo-600" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Paper document -->
                        <rect x="6" y="2" width="20" height="28" rx="2" stroke="currentColor" stroke-width="2" fill="currentColor" fill-opacity="0.1"/>
                        <!-- Lines on paper -->
                        <line x1="10" y1="10" x2="22" y2="10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="10" y1="16" x2="22" y2="16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <line x1="10" y1="22" x2="18" y2="22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <h1 class="text-2xl font-bold text-indigo-600">AutoCert</h1>
                </div>
                <div class="hidden sm:flex sm:space-x-8">
                    <a href="{{ route('templates.index') }}" 
                       class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Templates
                    </a>
                    <a href="{{ route('certificates.index') }}" 
                       class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Certificates
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages - Fixed Lower Right -->

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-auto relative">
        <!-- Flash Messages - Fixed Lower Right -->
        @if(session('success'))
            <div class="absolute bottom-20 right-4 mb-4" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 10000)" @click="show = false">
                <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded shadow-lg cursor-pointer hover:shadow-xl transition">
                    <p class="text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="absolute bottom-20 right-4 mb-4">
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded shadow-lg">
                    <p class="text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="absolute bottom-20 right-4 mb-4">
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded shadow-lg">
                    <ul class="list-disc list-inside text-red-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-gray-500 text-sm">
                Certificate Generator &copy; {{ date('Y') }} | Built with Laravel
            </p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
