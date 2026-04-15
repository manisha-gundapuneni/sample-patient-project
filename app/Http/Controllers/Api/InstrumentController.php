<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstrumentRequest;
use App\Http\Resources\InstrumentResource;
use App\Models\Instrument;
use Illuminate\Http\JsonResponse;

class InstrumentController extends Controller
{
    public function store(StoreInstrumentRequest $request): JsonResponse
    {
        $instrument = Instrument::create([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
        ]);

        $questionOrder = 0;
        foreach ($request->input('questions', []) as $question) {
            $instrument->questions()->create([
                'prompt' => $question['prompt'],
                'response_type' => $question['response_type'],
                'order' => $question['order'] ?? $questionOrder++,
            ]);
        }

        $instrument->load('questions');

        return response()->json(
            new InstrumentResource($instrument),
            201
        );
    }
}