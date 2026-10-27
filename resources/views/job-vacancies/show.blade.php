<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ $jobVacancy->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="bg-black shadow-lg rounded-lg p-6 max-w-7xl mx-auto">
            <a href="{{ route('dashboard') }}" class="text-blue-400 hover:underline inline-block mb-6">
                &larr; Back to jobs
            </a>

            <div class="flex justify-between items-start gap-4">
                <div>
                    <h3 class="text-white text-2xl font-bold">{{ $jobVacancy->title }}</h3>
                    <p class="text-sm text-white mt-1">
                        {{ $jobVacancy->company?->name }} - {{ $jobVacancy->location }}
                    </p>
                    @if ($jobVacancy->jobCategory)
                        <p class="text-sm text-white/70 mt-1">{{ $jobVacancy->jobCategory->name }}</p>
                    @endif
                    <p class="text-sm text-white mt-1">{{ '$' . number_format($jobVacancy->salary) }} / Year</p>
                    <p class="bg-indigo-500 text-white px-2 py-1 mt-2 rounded-lg w-fit"> {{ $jobVacancy->type }} </p>
                </div>

                <div class="mt-6">
                    <a href="{{ route('job-vacancies.apply', $jobVacancy->id) }}" 
                        class="justify-center bg-gradient-to-r from-indigo-500 to-rose-500 px-4 py-2 text-white rounded-lg transition hover:from-indigo-600 hover:to-rose-600"> Apply Now </a>
                </div>
            </div>

            <div class="grid grid-cols-3 mt-6">
                <div class="col-span-2">
                    <h2 class="text-lg font-bold text-white">Job Description</h2>
                    <p class="text-white whitespace-pre-wrap">{{ $jobVacancy->description }}</p>
                </div>
                <div class="col-span-1">
                    <h2 class="text-lg font-bold text-white">Job Overview</h2>
                    <div class="bg-gray-900 rounded-lg p-6 space-y-4">
                        <div>
                            <p class="text-gray-400">Published On</p>
                            <p class="text-white">{{ $jobVacancy->created_at->format('d M Y') }}</p>
                            {{-- number of days ago not weeks or months (integer numbers only)  --}}
                            <p class="text-white">{{ (int) $jobVacancy->created_at->diffInDays()}} days ago</p> 
                        </div>
                        <div>
                            <p class="text-gray-400">Company</p>
                            <p class="text-white">{{ $jobVacancy->company?->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400">Location</p>
                            <p class="text-white">{{ $jobVacancy->location }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400">Salary</p>
                            <p class="text-white">{{ $jobVacancy->salary }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400">Type</p>
                            <p class="text-white">{{ $jobVacancy->type }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400">Job Category</p>
                            <p class="text-white">{{ $jobVacancy->jobCategory?->name }}</p>
                        </div>
                    </div>
                </div>
            </div>

            
        </div>
    </div>
</x-app-layout>
