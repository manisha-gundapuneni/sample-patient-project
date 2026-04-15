<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Patient;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SubmissionController extends Controller
{
    public function store(StoreSubmissionRequest $request, Patient $patient): JsonResponse
    {
        $submission = $patient->submissions()->create([
            'instrument_id' => $request->input('instrument_id'),
        ]);

        foreach ($request->input('answers', []) as $answer) {
            $submission->answers()->create([
                'question_id' => $answer['question_id'],
                'answer_scale' => $answer['answer_scale'] ?? null,
                'answer_yes_no' => $answer['answer_yes_no'] ?? null,
                'answer_free_text' => $answer['answer_free_text'] ?? null,
            ]);
        }

        $submission->load('instrument', 'answers.question');

        return response()->json(
            new SubmissionResource($submission),
            201
        );
    }

    public function index(Patient $patient): ResourceCollection
    {
        $submissions = $patient->submissions()
            ->with('instrument', 'answers.question')
            ->orderByDesc('created_at')
            ->paginate(15);

        return SubmissionResource::collection($submissions);
    }

    public function show(Patient $patient, Submission $submission): JsonResponse
    {
        if ($submission->patient_id !== $patient->id) {
            return response()->json(['message' => 'Submission not found.'], 404);
        }

        $submission->load('instrument', 'answers.question');

        return response()->json(new SubmissionResource($submission));
    }

    public function summary(Patient $patient, Request $request): JsonResponse
    {
        $instrumentId = $request->query('instrument_id');

        if (!$instrumentId) {
            return response()->json([
                'message' => 'instrument_id query parameter is required.',
            ], 400);
        }

        $submissions = $patient->submissions()
            ->with('answers.question')
            ->where('instrument_id', $instrumentId)
            ->get();

        if ($submissions->isEmpty()) {
            return response()->json([
                'message' => 'No submissions found for this patient and instrument.',
            ], 404);
        }

        $instrument = $submissions->first()->instrument;
        $questions = $instrument->questions;

        $summary = [
            'patient_id' => $patient->id,
            'instrument_id' => $instrumentId,
            'total_submissions' => $submissions->count(),
            'date_range' => [
                'earliest' => $submissions->min('created_at'),
                'latest' => $submissions->max('created_at'),
            ],
            'question_summaries' => [],
        ];

        foreach ($questions as $question) {
            $answers = $submissions->flatMap(fn ($submission) => 
                $submission->answers->where('question_id', $question->id)
            );

            $summary['question_summaries'][] = match ($question->response_type) {
                'scale_1_5' => [
                    'question_id' => $question->id,
                    'prompt' => $question->prompt,
                    'response_type' => $question->response_type,
                    'average_score' => $answers->count() > 0 
                        ? round($answers->avg('answer_scale'), 2)
                        : null,
                ],
                'yes_no' => [
                    'question_id' => $question->id,
                    'prompt' => $question->prompt,
                    'response_type' => $question->response_type,
                    'yes_percentage' => $answers->count() > 0
                        ? round(($answers->where('answer_yes_no', true)->count() / $answers->count()) * 100, 2)
                        : null,
                ],
                'free_text' => [
                    'question_id' => $question->id,
                    'prompt' => $question->prompt,
                    'response_type' => $question->response_type,
                    'response_count' => $answers->where('answer_free_text', '!=', null)
                        ->where('answer_free_text', '!=', '')
                        ->count(),
                ],
            };
        }

        return response()->json($summary);
    }
}