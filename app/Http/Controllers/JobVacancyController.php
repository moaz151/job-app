<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobVacancy;
use App\Models\Resume;
use OpenAI\Laravel\Facades\OpenAI;
use App\Http\Requests\ApplyJobRequest;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Auth;
use App\Services\ResumeAnalysisService;
use RuntimeException;


class JobVacancyController extends Controller
{

    protected $resumeAnalysisService;

    public function __construct(ResumeAnalysisService $resumeAnalysisService)
    {
        $this->resumeAnalysisService = $resumeAnalysisService;
    }
    public function show(string $id)
    {
        $jobVacancy = JobVacancy::findOrFail($id);

        return view("job-vacancies.show", compact("jobVacancy"));
    }

    public function apply(string $id)
    {
        $jobVacancy = JobVacancy::findOrFail($id);
        $resumes = Auth::user()->Resume()->latest()->get();

        // $resumes = Resume::query()
        //     ->where('user_id', Auth::id())
        //     ->latest()
        //     ->get();

        return view("job-vacancies.apply", compact('jobVacancy', 'resumes'));
    }

    public function processApplication(ApplyJobRequest $request, string $id)
    {
        if ($request->input('resume_option') === 'new_resume')
        {
            $resumeId = null;
            $extractedInfo = null;

            $file = $request->file('resume_file');
            $extension = $file->getClientOriginalExtension();
            $originalFileName = $file->getClientOriginalName();
            $fileName = 'resume_' . time() . '.' . $extension;

            // Store in Laravel Cloud
            $path = $file->storeAs('resumes', $fileName, 'cloud');
            if (!is_string($path) || $path === '') {
                throw new RuntimeException('Unable to store the uploaded resume.');
            }

            //Extract information from resume.
            $extractedInfo = $this->resumeAnalysisService->extractResumeInformation($path); //fileUrl

            $resume = Resume::create([
                'file_name' => $originalFileName,
                'file_url' => $path,
                'user_id' =>Auth::id(),
                'contact_details' => json_encode([
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                ]),
                // Will be created by GPTchat
                'summary' => $extractedInfo['summary'],
                'skills' => $extractedInfo['skills'],
                'experience' => $extractedInfo['experience'],
                'education' => $extractedInfo['education'],
            ]);

            $resumeId = $resume->id;
        } else {
            $resumeId = $request->input('resume_option');
            $resume = Resume::findOrFail($resumeId);


            $extractedInfo = [
                'summary' => $resume->summary,
                'skills' => $resume->skills,
                'experience' => $resume->experience,
                'education' => $resume->education,
            ];
        }

        $evaluationResult = $this->resumeAnalysisService->analyzeResume(JobVacancy::findOrFail($id), $extractedInfo);
        //Use the $extractedInfo to evaluate the job application
        JobApplication::create([
            'status' => 'pending',
            'job_vacancy_id' => $id,
            'resume_id' => $resumeId,
            'user_id' => Auth::id(),
            'aiGeneratedScore' => $evaluationResult['aiGeneratedScore'] ?? 0,
            'aiGeneratedFeedback' => $evaluationResult['aiGeneratedFeedback'] ?? '',
        ]);

        
        return redirect()->route('job-applications.index', $id)->with('success','Application Submitted successfully!');
    }
}
