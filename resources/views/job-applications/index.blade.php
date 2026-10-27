<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ __('My Applications') }}
        </h2>
    </x-slot>
    {{-- Validate session --}}
    @if(session('success'))
        <div class="w-full bg-indigo-600 text-white p-4 rounded-md mb-2" role="alert">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <div class="py-12">
        <div class="bg-black shadow-lg rounded-lg p-6 max-w-7xl mx-auto space-y-4">
            @forelse($jobApplications as $application)
                <div class="bg-gray-800 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold text-white">{{ $application->jobVacancy->title }}</h3>
                    <p class="text-sm">{{ $application->jobVacancy->Company->name }}</p>
                    <p class="text-sm"> {{ $application->jobVacancy->location }}</p>

                    <div class="flex items-center justify-between">
                        <p class="text-sm"> {{ $application->created_at->format('M d, Y') }}</p>
                        <p class="px-3 py-1 bg-indigo-600 text-white rounded-md"> {{ $application->jobVacancy->type }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span>Applied With: {{ $application->resume?->file_name ?? 'No Resume Attached' }}</span>
                        @if (filled($application->resume?->file_url))
                            <a href="{{ Storage::disk('cloud')->url($application->resume->file_url) }}"
                                class="text-indigo-500 hover:text-indigo-600" target="_blank" rel="noopener noreferrer">View Resume</a>
                        @endif
                    </div>

                    <div class="flex flex-start flex-col gap-2 mt-4">
                        <div class="flex items-center gap-2">
                        @php
                            $statusClass = match($application->status) {
                                'pending' => 'bg-yellow-500',
                                'accepted' => 'bg-green-500',
                                'rejected' => 'bg-red-500',
                            };
                        @endphp
                            <p class="text-sm w-fit rounded-md {{ $statusClass }} text-white p-2">Status: {{ $application->status }}</p>
                            <p class="text-sm text-white p-2 rounded-md bg-indigo-600 w-fit">Score: {{ $application->aiGeneratedScore ?? 'N/A' }}</p>
                        </div>
                        <h4 class="text-md font-bold">AI Feedback:</h4>
                        <p class="text-sm"> {{ $application->aiGeneratedFeedback ?? 'N/A' }}</p>
                    </div>
                </div>
            @empty
                <p class="text-white">No Applications Found!</p>
            @endforelse

        </div>

        <div class="mt-4">
            {{ $jobApplications->links() }}
        </div>
    </div>
</x-app-layout>