<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Spatie\PdfToText\Pdf;
use OpenAI\Laravel\Facades\OpenAI;

class ResumeAnalysisService
{
    public function extractResumeInformation(string $storagePath) {
        try {
            // Extract raw text fron the resume pdf file (read pdf file, and get the text)
            $rawText =  $this->extractTextFromPdf($storagePath);
            Log::debug("Successfully extracted text from pdf file: " . strlen($rawText) . " characters");
            // Use OpenAI API to organize the text into a structured format
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are a percise resume parser. Extract information exactly as it appers in the resume without
                                    adding any interpretation or additional information. The output should be in JSON format."
                    ],
                    [
                        'role' => 'user',
                        'content' => "parse the following resume content and extract the information as a JSON Object with the exact keys: summary, skills, experience, education. Here is the resume content: . {$rawText} . Return an empty string for key that if not found in the resume."
                    ]
                ],
                'response_format' => [
                    'type' => 'json_object'
                ],
                'temperature' => 0.2, // Set temperature to 0 for deterministic output // 0.2 to allow some flexibility in the output
            ]);
            // Output: summary, skills, experience, education -> JSON
            $result = $response->choices[0]->message->content;
            Log::debug('OpenAI response' . $result);

            $parsedResult = json_decode($result, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse OpenAI response as JSON: ' . json_last_error_msg());
                throw new \RuntimeException('Failed to parse OpenAI response as JSON: ' . json_last_error_msg());
            }
            // validate that the parsed result contains the expected keys
            $requiredKeys = ['summary', 'skills', 'experience', 'education'];
            $missingKeys = array_diff($requiredKeys, array_keys($parsedResult));
            if(!empty($missingKeys)) {
                Log::error('Missing Required keys in OpenAI response: ' . implode(', ', $missingKeys));
                throw new \RuntimeException('Missing Required keys in OpenAI response: ' . implode(', ', $missingKeys));
            }
            // Return the Json object

            return [
                'summary' => $this->normalizeResumeField($parsedResult['summary'] ?? ''),
                'skills' => $this->normalizeResumeField($parsedResult['skills'] ?? ''),
                'experience' => $this->normalizeResumeField($parsedResult['experience'] ?? ''),
                'education' => $this->normalizeResumeField($parsedResult['education'] ?? ''),
            ];
        } catch (\Throwable $e) {
            Log::error('Error extracting resume information: ' . $e->getMessage());
            return [
                'summary' => '',
                'skills' => '',
                'experience' => '',
                'education' => ''
            ];
        }
    }

    private function normalizeResumeField(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return '';
    }

    public function analyzeResume($jobVacancy, $resumeData): array {
        try{
            $jobDetails = json_encode([
                'job_title' => $jobVacancy->title,
                'job_description' => $jobVacancy->description,
                'job_location' => $jobVacancy->location,
                'job_type' => $jobVacancy->type,
                'job-salary' => $jobVacancy->salary,
            ]);

            $resumeDetails = json_encode($resumeData);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are an expert HR professional and job recruiter. you are given a job vacancy and a resume.
                                        Your task is to analyze the resume and determine if the candidate is a good fit for the job.
                                        output should be in JSON format.
                                        provide a score from 0 to 100 for the candidate's suitability for the job, and a detailed feedback.
                                        Response should only be JSON that has the following keys: 'aiGeneratedScore', 'aiGeneratedFeedback'.
                                        Aigenerate feedback should be detailed and specific to he job and the candidate's resume."
                    ],
                    [
                        'role' => 'user',
                        'content' => "please  evaluate this job application. Job Details: {$jobDetails} Resume Details: {$resumeDetails}"
                    ]
                ],
                'response_format' => [
                    'type' => 'json_object'
                ],
                'temperature' => 0.1, // Set temperature to 0 for deterministic output
            ]);
            $result = $response->choices[0]->message->content;
            Log::debug('OpenAI evaluation response: ' . $result);

            $parsedResult = json_decode($result, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse OpenAI evaluation response as JSON: ' . json_last_error_msg());
                throw new \RuntimeException('Failed to parse OpenAI evaluation response as JSON: ' . json_last_error_msg());
            }

            if(!isset($parsedResult['aiGeneratedScore']) || !isset($parsedResult['aiGeneratedFeedback'])) {
                Log::error('Missing required keys in OpenAI evaluation response');
                throw new \RuntimeException('Missing required keys in OpenAI evaluation response');
            }

            return $parsedResult;


        } catch (\Throwable $e) {
            Log::error('Error analyzing resume: ' . $e->getMessage());
            return [
                'aiGeneratedScore' => 0,
                'aiGeneratedFeedback' => '' // Error analyzing resume: ' . $e->getMessage() 
            ];
        }
    }

    private function extractTextFromPdf(string $storagePath): string {
        // Reading the file from the cloud to local disk storage in temp file
        // spatie PDF Extract text
        if (!Storage::disk('cloud')->exists($storagePath)) {
            throw new \Exception("File not found in storage");
        }

        $pdfContent = Storage::disk('cloud')->get($storagePath);
        if (!$pdfContent) {
            throw new \Exception("Failed to read the PDF file");
        }

        // Check if pdf-to-text is installed.
        $pdfToTextPath = ['/opt/homebrew/bin/pdftotext', '/usr/bin/pdftotext', '/usr/local/bin/pdftotext'];
        $pdfToTextAvailable = false;
        foreach ($pdfToTextPath as $path) {
            if (file_exists($path)) {
                $pdfToTextAvailable = true;
                break;
            }
        }
        if (!$pdfToTextAvailable) {
            throw new \Exception("pdf-to-text is not installed on the server");
        }

        $tempaFile = tempnam(sys_get_temp_dir(), 'resume');
        if ($tempaFile === false) {
            throw new \Exception("Failed to create a temporary file for the PDF");
        }

        try {
            if (file_put_contents($tempaFile, $pdfContent) === false) {
                throw new \Exception("Failed to write the PDF to a temporary file");
            }

            // Extract text from the PDF file using pdftotext.
            $instance = new Pdf();
            $instance->setPdf($tempaFile);
            return $instance->text();
        } finally {
            if (file_exists($tempaFile)) {
                unlink($tempaFile);
            }
        }
    }
}