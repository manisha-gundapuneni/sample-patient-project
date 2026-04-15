<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\Instrument;
use App\Models\Submission;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample patients
        $patient1 = Patient::create([
            'name' => 'John Doe',
            'date_of_birth' => '1980-05-15',
            'mrn' => 'MRN-001-2026',
        ]);

        $patient2 = Patient::create([
            'name' => 'Jane Smith',
            'date_of_birth' => '1990-08-22',
            'mrn' => 'MRN-002-2026',
        ]);

        // Create sample instruments
        $diabetesInstrument = Instrument::create([
            'title' => 'Diabetes Quality of Life Survey',
            'description' => 'Measures quality of life impacts of diabetes management',
        ]);

        $diabetesInstrument->questions()->createMany([
            [
                'prompt' => 'How satisfied are you with your current diabetes management?',
                'response_type' => 'scale_1_5',
                'order' => 1,
            ],
            [
                'prompt' => 'Have you experienced any hypoglycemic episodes this week?',
                'response_type' => 'yes_no',
                'order' => 2,
            ],
            [
                'prompt' => 'Please describe any side effects from your medication.',
                'response_type' => 'free_text',
                'order' => 3,
            ],
            [
                'prompt' => 'How would you rate your energy levels?',
                'response_type' => 'scale_1_5',
                'order' => 4,
            ],
        ]);

        $cancerInstrument = Instrument::create([
            'title' => 'Cancer Treatment Side Effects Scale',
            'description' => 'Tracks side effects and symptoms related to cancer treatment',
        ]);

        $cancerInstrument->questions()->createMany([
            [
                'prompt' => 'How severe is your nausea?',
                'response_type' => 'scale_1_5',
                'order' => 1,
            ],
            [
                'prompt' => 'Have you experienced hair loss?',
                'response_type' => 'yes_no',
                'order' => 2,
            ],
            [
                'prompt' => 'What symptoms are you experiencing today?',
                'response_type' => 'free_text',
                'order' => 3,
            ],
        ]);

        // Create sample submissions
        for ($i = 0; $i < 3; $i++) {
            $submission = $patient1->submissions()->create([
                'instrument_id' => $diabetesInstrument->id,
            ]);

            $submission->answers()->createMany([
                [
                    'question_id' => $diabetesInstrument->questions[0]->id,
                    'answer_scale' => rand(1, 5),
                ],
                [
                    'question_id' => $diabetesInstrument->questions[1]->id,
                    'answer_yes_no' => (bool) rand(0, 1),
                ],
                [
                    'question_id' => $diabetesInstrument->questions[2]->id,
                    'answer_free_text' => 'No significant side effects.',
                ],
                [
                    'question_id' => $diabetesInstrument->questions[3]->id,
                    'answer_scale' => rand(1, 5),
                ],
            ]);
        }

        for ($i = 0; $i < 2; $i++) {
            $submission = $patient2->submissions()->create([
                'instrument_id' => $cancerInstrument->id,
            ]);

            $submission->answers()->createMany([
                [
                    'question_id' => $cancerInstrument->questions[0]->id,
                    'answer_scale' => rand(1, 5),
                ],
                [
                    'question_id' => $cancerInstrument->questions[1]->id,
                    'answer_yes_no' => (bool) rand(0, 1),
                ],
                [
                    'question_id' => $cancerInstrument->questions[2]->id,
                    'answer_free_text' => 'Experiencing mild fatigue.',
                ],
            ]);
        }
    }
}