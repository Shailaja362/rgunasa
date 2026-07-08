<?php


if (!function_exists('getQuestions')) {
    function getQuestions($type = 'nonTechnical')
    {
        $questions = [
            'nonTechnical' => [
                [
                    'key' => 'overall_experience',
                    'question' => 'How exciting was the overall event experience?',
                    'rating' => 0,
                ],
                [
                    'key' => 'engagement',
                    'question' => 'How engaging were the activities or sessions?',
                    'rating' => 0,
                ],
                [
                    'key' => 'organization',
                    'question' => 'How well was the event organized?',
                    'rating' => 0,
                ],
                [
                    'key' => 'coordination',
                    'question' => 'How interactive and friendly were the coordinators?',
                    'rating' => 0,
                ],
                [
                    'key' => 'recommendation',
                    'question' => 'How likely are you to attend similar events again?',
                    'rating' => 0,
                ],
            ],

            'technical' => [
                [
                    'key' => 'understanding',
                    'question' => 'How well did you understand the topics in the session?',
                    'rating' => 0,
                ],
                [
                    'key' => 'helpfulness',
                    'question' => 'How helpful were the examples and exercises?',
                    'rating' => 0,
                ],
                [
                    'key' => 'explanation',
                    'question' => "How would you rate the instructor's explanation of the topics?",
                    'rating' => 0,
                ],
                [
                    'key' => 'pace',
                    'question' => 'How would you describe the pace of the session?',
                    'rating' => 0,
                ],
                [
                    'key' => 'satisfaction',
                    'question' => 'How satisfied are you with the session overall?',
                    'rating' => 0,
                ],
                [
                    'key' => 'rating',
                    'question' => 'How would you rate this event?',
                    'rating' => 0,
                ],
            ],
        ];

        return $questions[$type] ?? [];
    }
}


