<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Instrument;
use Tests\TestCase;

class SubmissionApiTest extends TestCase
{
    private Patient $patient;
    private Instrument $instrument;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::create([
            'name' => 'Test Patient',
            'date_of_birth' => '1980-01-01',
            'mrn' => 'TEST-MRN-003',
        ]);

        $this->instrument = Instrument::create([
            'title' => 'Test Instrument',
            'description' => 'A test instrument',
        ]);

        $this->instrument->questions()->createMany([
            [
                'prompt' => 'Scale question?',
                'response_type' => 'scale_1_5',
                'order' => 1,
            ],
            [
                'prompt' => 'Yes/No question?',
                'response_type' => 'yes_no',
                'order' => 2,
            ],
        ]);
    }

    public function test_submit_instrument(): void
    {
        $response = $this->postJson("/api/patients/{$this->patient->id}/submissions", [
            'instrument_id' => $this->instrument->id,
            'answers' => [
                [
                    'question_id' => $this->instrument->questions[0]->id,
                    'answer_scale' => 4,
                ],
                [
                    'question_id' => $this->instrument->questions[1]->id,
                    'answer_yes_no' => true,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'patient_id', 'instrument_id', 'answers']
            ]);
    }

    public function test_get_patient_submissions(): void
    {
        // Create a submission
        $this->patient->submissions()->create([
            'instrument_id' => $this->instrument->id,
        ]);

        $response = $this->getJson("/api/patients/{$this->patient->id}/submissions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'patient_id', 'instrument_id']
                ]
            ]);
    }

    public function test_get_patient_summary(): void
    {
        // Create multiple submissions
        for ($i = 0; $i < 2; $i++) {
            $submission = $this->patient->submissions()->create([
                'instrument_id' => $this->instrument->id,
            ]);

            $submission->answers()->createMany([
                [
                    'question_id' => $this->instrument->questions[0]->id,
                    'answer_scale' => 3 + $i,
                ],
                [
                    'question_id' => $this->instrument->questions[1]->id,
                    'answer_yes_no' => true,
                ],
            ]);
        }

        $response = $this->getJson(
            "/api/patients/{$this->patient->id}/summary?instrument_id={$this->instrument->id}"
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'patient_id',
                'instrument_id',
                'total_submissions',
                'date_range',
                'question_summaries',
            ]);
    }
}