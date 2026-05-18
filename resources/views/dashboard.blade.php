<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-2">
                        Welcome, {{ auth()->user()->name }}
                    </h3>
                    <p class="mb-1">
                        Logged in as: <strong>{{ auth()->user()->email }}</strong>
                    </p>
                    <p class="mb-0">
                        Role: <strong>{{ auth()->user()->getRoleNames()->first() ?? 'No role assigned' }}</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
