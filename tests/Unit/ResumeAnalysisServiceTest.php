<?php

use App\Services\ResumeAnalysisService;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;
use Tests\TestCase;

uses(TestCase::class);

function fakeResumeAnalysisResponse(string $content): CreateResponse
{
    return CreateResponse::fake([
        'choices' => [[
            'index' => 0,
            'message' => ['role' => 'assistant', 'content' => $content],
            'finish_reason' => 'stop',
        ]],
    ]);
}

function resumeAnalysisJob(): object
{
    return (object) [
        'title' => 'Software Engineer',
        'description' => 'Build and maintain web applications.',
        'location' => 'Remote',
        'type' => 'full-time',
        'salary' => '100000.00',
    ];
}

test('resume analysis returns the score and feedback from the AI response', function () {
    $fake = OpenAI::fake([
        fakeResumeAnalysisResponse(json_encode([
            'aiGeneratedScore' => 92,
            'aiGeneratedFeedback' => 'Strong match.',
        ])),
    ]);

    expect(app(ResumeAnalysisService::class)->analyzeResume(resumeAnalysisJob(), [
        'summary' => 'Experienced developer',
        'skills' => 'PHP',
        'experience' => 'Five years',
        'education' => 'Computer science',
    ]))->toBe([
        'aiGeneratedScore' => 92,
        'aiGeneratedFeedback' => 'Strong match.',
    ]);

    $fake->chat()->assertSent(function (string $method, array $arguments): bool {
        expect($method)->toBe('create')
            ->and($arguments['model'])->toBe('gpt-4o-mini')
            ->and($arguments['response_format']['type'])->toBe('json_object')
            ->and($arguments['messages'][1]['content'])->toContain('Software Engineer')
            ->and($arguments['messages'][1]['content'])->toContain('"skills":"PHP"');

        return true;
    });
});

test('resume analysis returns a safe fallback when the AI response is invalid JSON', function () {
    OpenAI::fake([fakeResumeAnalysisResponse('{invalid-json')]);

    expect(app(ResumeAnalysisService::class)->analyzeResume(resumeAnalysisJob(), []))->toBe([
        'aiGeneratedScore' => 0,
        'aiGeneratedFeedback' => '',
    ]);
});

test('resume analysis returns a safe fallback when required response fields are missing', function () {
    OpenAI::fake([fakeResumeAnalysisResponse(json_encode(['aiGeneratedScore' => 75]))]);

    expect(app(ResumeAnalysisService::class)->analyzeResume(resumeAnalysisJob(), []))->toBe([
        'aiGeneratedScore' => 0,
        'aiGeneratedFeedback' => '',
    ]);
});

test('resume analysis returns a safe fallback when the AI request throws', function () {
    OpenAI::fake([new RuntimeException('AI unavailable')]);

    expect(app(ResumeAnalysisService::class)->analyzeResume(resumeAnalysisJob(), []))->toBe([
        'aiGeneratedScore' => 0,
        'aiGeneratedFeedback' => '',
    ]);
});
