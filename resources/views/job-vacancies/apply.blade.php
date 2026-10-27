<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            {{ $jobVacancy->title }} | Apply for Job
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="bg-black shadow-lg rounded-lg p-6 max-w-7xl mx-auto">
            <a href="{{ route('dashboard') }}" class="text-blue-400 hover:underline inline-block mb-6">
                &larr; Back to jobs Details
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
            </div>
            <hr class="border-0 h-px bg-gray-600 m-6">

            <form action="{{ route('job-vacancies.process-application', $jobVacancy->id) }}" method="POST"
                enctype="multipart/form-data" class="space-y-6">
                @csrf

                {{-- For errors --}}
                @if ($errors->any())
                    <div class="bg-red-500 text-white p-4 rounded-lg mt-2">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                {{-- Resume selection --}}
                <div>
                    <h3 class="text-lg font-bold text-white">Choose Your Resume</h3>
                    
                    <div class="mb-6">
                        <x-input-label for="resume" value="Select from your existing resumes:" />
                        {{-- List of resumes --}}
                        <div class="space-y-4">
                            @forelse ($resumes as $resume)
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="resume_option" id="existing_{{ $resume->id }}" value="{{ $resume->id }}"
                                        @error('resume_option') class="border-red-500" @else class="border-gray-600 @enderror" />
                                    <x-input-label for="existing_{{ $resume->id }}" class="text-white cursor-pointer">
                                        {{ $resume->file_name }}
                                        <span class="text-gray-500"> Last Updated: ({{ $resume->created_at->format('M d, Y') }})</span>
                                    </x-input-label>
                                </div>
                            @empty
                                <p class="text-gray-400 text-sm">You have no saved resumes yet. Upload one below.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                {{-- Upload a new resume --}}
                <div x-data="{ fileName: '', hasError: {{ $errors->has('resume_file') ? 'true' : 'false' }} }">
                    <div class="flex items-centers gap-2">
                        <input x-ref="newResumeRadio" type="radio" name="resume_option" id="new_resume" value="new_resume"
                                            @error('resume_option') class="border-red-500" @else class="border-gray-600 @enderror" />
                        <x-input-label class="text-white cursor-pointer" for="new_resume" value="Upload a new resume:" />
                    </div>
                    <div class="flex items-center">
                        <div class="flex-1">
                            <label for="new_resume_file" class="block text-white cursor-pointer">
                                <div class="border-2 border-dashed border-gray-600 rounded-lg p-4 hover:border-blue-500 transition"
                                :class="{ 'border-blue-500': fileName, 'border-red-500': hasError }">
                                    <input @change="fileName = $event.target.files[0].name; $refs.newResumeRadio.checked = true" type="file" name="resume_file" id="new_resume_file" class="hidden" accept=".pdf" />
                                    <div class="text-center">
                                        <template x-if="!fileName">
                                            <p class="text-gray-400">Click to upload or drag and drop your PDF (Max 5MB)</p>
                                        </template>

                                        <template x-if="fileName">
                                            <div>
                                                <p x-text="fileName" class="mt-2 text-blue-400"></p>
                                                <p class="text-gray-400 text-sm mt-1">Click to Change File</p>
                                            </div>
                                        </template>
                                    </div>

                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- submit button --}}
                <div class="mt-6">
                    <x-primary-button class="w-full">
                        Submit Application
                    </x-primary-button>
                </div>
            </form>

            
        </div>
    </div>
</x-app-layout>
