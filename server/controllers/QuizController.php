<?php
require_once __DIR__ . '/../config/db.php';

class QuizController
{
    private $questions;
    private $quizResults;
    private $userAnswers;

    public function __construct()
    {
        $db = Database::getInstance()->getDb();
        $this->questions = $db->questions;
        $this->quizResults = $db->quizResults;
        $this->userAnswers = $db->user_answers; // Add the new collection
    }

    public function getQuestions($difficulty, $count)
    {
        $questions = $this->questions->aggregate([
            ['$match' => ['difficulty' => $difficulty]],
            ['$sample' => ['size' => (int)$count]]
        ])->toArray();

        if (empty($questions)) {
            throw new Exception('No questions found for this difficulty level');
        }

        return array_map(function ($q) {
            return [
                'id' => (string)$q->_id,
                'question' => $q->question,
                'options' => $q->options,
                'correctAnswer' => $q->correctAnswer
            ];
        }, $questions);
    }

    public function saveUserAnswer($userId, $data)
    {
        $result = $this->userAnswers->insertOne([
            'userId' => new MongoDB\BSON\ObjectId($userId),
            'sessionId' => $data['sessionId'],
            'questionId' => new MongoDB\BSON\ObjectId($data['questionId']),
            'questionNumber' => (int)$data['questionNumber'],
            'totalQuestions' => (int)$data['totalQuestions'],
            'userAnswer' => (int)$data['userAnswer'],
            'isCorrect' => (bool)$data['isCorrect'],
            'difficulty' => $data['difficulty'],
            'timeLeft' => (int)$data['timeLeft'], // Total remaining quiz time (in seconds)
            'timestamp' => new MongoDB\BSON\UTCDateTime()
        ]);

        return (string)$result->getInsertedId();
    }

    public function getLeaderboard($difficulty = null, $limit = 10)
    {
        $pipeline = [];
    
        // Filter by difficulty if provided
        if ($difficulty && in_array($difficulty, ['easy', 'medium', 'hard'])) {
            $pipeline[] = ['$match' => ['difficulty' => $difficulty]];
        }
    
        // Sort by score descending first to get the highest scores
        $pipeline[] = ['$sort' => ['score' => -1]];
    
        // Join with users collection to get username
        $pipeline[] = [
            '$lookup' => [
                'from' => 'users',
                'localField' => 'userId',
                'foreignField' => '_id',
                'as' => 'user'
            ]
        ];
    
        $pipeline[] = ['$unwind' => '$user'];
    
        // Project the fields we want (including sessionId)
        $pipeline[] = [
            '$project' => [
                'sessionId' => 1,
                'userId' => 1,
                'username' => '$user.username',
                'score' => 1,
                'correctAnswers' => 1,
                'totalQuestions' => 1,
                'difficulty' => 1,
                'timestamp' => 1
            ]
        ];
    
        // Limit the number of results
        $pipeline[] = ['$limit' => $limit];
    
        return $this->quizResults->aggregate($pipeline)->toArray();
    }


    public function saveResult($data)
    {

        // Validate required fields
        $requiredFields = ['userId', 'score', 'correctAnswers', 'totalQuestions', 'difficulty'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: $field");
            }
        }

        $document = [
            'userId' => new MongoDB\BSON\ObjectId($data['userId']),
            'sessionId' => $data['sessionId'],
            'score' => (int) $data['score'],
            'correctAnswers' => (int) $data['correctAnswers'],
            'totalQuestions' => (int) $data['totalQuestions'],
            'difficulty' => $data['difficulty'],
            'timestamp' => new MongoDB\BSON\UTCDateTime()
        ];

        $this->quizResults->insertOne($document);
    }


    public function getUserAnswers($userId, $sessionId = null)
    {
        $filter = ['userId' => new MongoDB\BSON\ObjectId($userId)];

        if ($sessionId) {
            $filter['sessionId'] = $sessionId;
        }

        $cursor = $this->userAnswers->find($filter, [
            'sort' => ['timestamp' => -1],
            'limit' => 50
        ]);

        $responses = iterator_to_array($cursor);

        if (empty($responses)) {
            return [
                'responses' => [],
                'totalQuestions' => 0,
                'correctAnswers' => 0,
                'difficulty' => 'medium'
            ];
        }

        // Calculate stats for a specific session
        if ($sessionId) {
            $totalQuestions = $responses[0]['totalQuestions'] ?? count($responses);
            $correctAnswers = 0;
            $difficulty = $responses[0]['difficulty'] ?? 'medium';

            foreach ($responses as $response) {
                if (!empty($response['isCorrect'])) {
                    $correctAnswers++;
                }
            }

            return [
                'responses' => $responses,
                'totalQuestions' => $totalQuestions,
                'correctAnswers' => $correctAnswers,
                'difficulty' => $difficulty
            ];
        }

        // Return basic history if no sessionId
        return [
            'responses' => $responses,
            'totalQuestions' => count($responses),
            'correctAnswers' => 0,
            'difficulty' => 'mixed'
        ];
    }
}
