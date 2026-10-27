<?php

use App\Http\Requests\ApplyJobRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

function validateApplyJobRequest(array $data): Illuminate\Contracts\Validation\Validator
{
    return Validator::make($data, (new ApplyJobRequest)->rules());
}

test('a saved resume can be selected without uploading another file', function () {
    $validator = validateApplyJobRequest(['resume_option' => 'existing-resume-id']);

    expect($validator->passes())->toBeTrue();
});

test('a resume choice is required', function () {
    $validator = validateApplyJobRequest([]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('resume_option'))->toBeTrue();
});

test('a new resume requires a PDF upload', function () {
    $validator = validateApplyJobRequest(['resume_option' => 'new_resume']);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('resume_file'))->toBeTrue();
});

test('a PDF resume within the upload limit is accepted', function () {
    $validator = validateApplyJobRequest([
        'resume_option' => 'new_resume',
        'resume_file' => UploadedFile::fake()->create('resume.pdf', 5120, 'application/pdf'),
    ]);

    expect($validator->passes())->toBeTrue();
});

test('non-PDF and oversized resumes are rejected', function (UploadedFile $resume) {
    $validator = validateApplyJobRequest([
        'resume_option' => 'new_resume',
        'resume_file' => $resume,
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('resume_file'))->toBeTrue();
})->with([
    'wrong file type' => fn () => UploadedFile::fake()->create('resume.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
    'larger than 5 MB' => fn () => UploadedFile::fake()->create('resume.pdf', 5121, 'application/pdf'),
]);
