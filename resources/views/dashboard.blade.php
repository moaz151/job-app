<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="bg-black shadow-lg rounded-lg p-6 max-w-7xl mx-auto">
            <h3 class="text-white text-2xl font-bold mb-6">
                {{ __('Welcome Back,') }} {{ auth()->user()->name }}!
            </h3>
            <!-- search & Filters -->
            <div class="flex items-center justify-between">
                {{-- search Bar --}}
                <form action="{{ route('dashboard') }}" method="GET" class="flex items-center justify-center w-1/4">
                    <input type="text" name="search" value="{{ request('search') }}" class="w-full p-2 rounded-l-lg bg-gray-800 text-white" placeholder="Search for a job">
                    <button type="submit" 
                        class="bg-indigo-500 text-white px-4 py-2 rounded-r-lg border border-indigo-500">Search</button>
                        @if (request('filter'))
                            <input type="hidden" name="filter" value="{{ request('filter') }}">
                        @endif
                        
                        @if(request('search'))
                            <a href="{{ route('dashboard', ['filter' => request('filter')]) }}" 
                                class="text-white p-2 rounded-lg">clear</a>
                        @endif
                </form>

                {{-- Filters --}}
                <div class="flex space-x-2">
                    <a href="{{ route('dashboard', ['filter' => 'full-time', 'search' => request('search')]) }}" 
                        class="bg-indigo-500 text-white px-4 py-2 rounded-lg">Full-Time</a>
                    <a href="{{ route('dashboard', ['filter' => 'part-time', 'search' => request('search')]) }}" 
                        class="bg-indigo-500 text-white px-4 py-2 rounded-lg">Part-Time</a>
                    <a href="{{ route('dashboard', ['filter' => 'remote', 'search' => request('search')]) }}"
                         class="bg-indigo-500 text-white px-4 py-2 rounded-lg">Remote</a>
                    <a href="{{ route('dashboard', ['filter' => 'hybrid', 'search' => request('search')]) }}" 
                        class="bg-indigo-500 text-white px-4 py-2 rounded-lg">Hybrid</a>
                    <a href="{{ route('dashboard', ['filter' => 'contract', 'search' => request('search')]) }}"
                         class="bg-indigo-500 text-white px-4 py-2 rounded-lg">Contract</a>

                    @if(request('filter'))
                        <a href="{{ route('dashboard', ['search' => request('search')]) }}" 
                            class="text-white p-2 rounded-lg">clear</a>
                    @endif
                </div>
            </div>
            {{-- Jobs List --}}
            <div class="space-y-4 mt-6">
                @forelse ($jobs as $job)
                    <div class="border-b border-white/10 pb-4 flex justify-between items-center">
                        {{-- Job Item --}}
                        <div>
                            <a href="{{ route('job-vacancies.show', $job->id) }}"
                                class="text-lg font-semibold text-blue-400 hover:underline">{{ $job->title }}</a>
                            <p class="text-sm text-white">{{ $job->company->name }} - {{ $job->location }}</p>
                            <p class="text-sm text-white">{{ '$' . number_format($job->salary) }} / Year</p>
                        </div>
                        <span class="bg-blue-500 text-white p-2 rounded-lg">{{ $job->type }}</span>
                    </div>
                @empty
                    <p class="text-white text-2xl font-bold"> No Jobs Found! </p>
                @endforelse
            </div>
            <div class="mt-6">
                {{ $jobs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>

