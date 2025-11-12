<?php
 
require_once __DIR__ . '/../vendor/autoload.php'; // Adjust if needed

// Connect to MongoDB
$client = new MongoDB\Client("mongodb://localhost:27017");
$db = $client->selectDatabase('quiz_game');
$questionsCollection = $db->selectCollection('questions');

// Function to create question documents
function createQuestion($question, $options, $correctAnswer, $difficulty, $time) {
    return [
        'question' => $question,
        'options' => $options,
        'correctAnswer' => $correctAnswer,
        'difficulty' => $difficulty,
        'category' => 'math',
        'createdAt' => new MongoDB\BSON\UTCDateTime(),
        'time' => $time
    ];
}

// All questions
$allQuestions = [];

// Easy questions
$easyQuestions = [
    ['What is 2 + 2?', ['3', '4', '5', '6'], 1, 15],
    ['What is 5 - 3?', ['1', '2', '3', '4'], 1, 15],
    ['What is 3 × 4?', ['10', '11', '12', '13'], 2, 15],
    ['What is 6 ÷ 2?', ['2', '3', '4', '5'], 1, 15],
    ['What is 7 + 1?', ['6', '7', '8', '9'], 2, 15],
    ['What is 9 - 5?', ['2', '3', '4', '5'], 2, 15],
    ['What is 4 × 3?', ['10', '11', '12', '13'], 2, 15],
    ['What is 10 ÷ 2?', ['3', '4', '5', '6'], 2, 15],
    ['What is 8 + 4?', ['10', '11', '12', '13'], 2, 15],
    ['What is 7 - 3?', ['3', '4', '5', '6'], 1, 15],
    ['What is 5 × 2?', ['8', '9', '10', '11'], 2, 15],
    ['What is 12 ÷ 4?', ['2', '3', '4', '5'], 1, 15],
    ['What is 3 + 6?', ['8', '9', '10', '11'], 1, 15],
    ['What is 15 - 8?', ['6', '7', '8', '9'], 1, 15],
    ['What is 5 × 5?', ['20', '25', '30', '35'], 1, 15],
    ['What is 20 ÷ 5?', ['3', '4', '5', '6'], 1, 15],
    ['What is 6 + 2?', ['7', '8', '9', '10'], 1, 15],
    ['What is 18 - 9?', ['8', '9', '10', '11'], 1, 15],
    ['What is 4 × 2?', ['7', '8', '9', '10'], 1, 15]
];

foreach ($easyQuestions as $q) {
    $allQuestions[] = createQuestion($q[0], $q[1], $q[2], 'easy', $q[3]);
}

// Medium questions
$mediumQuestions = [
    ['What is 15 + 27?', ['40', '41', '42', '43'], 2, 10],
    ['What is 78 - 34?', ['44', '45', '46', '47'], 0, 10],
    ['What is 12 × 6?', ['70', '72', '74', '75'], 1, 10],
    ['What is 144 ÷ 12?', ['10', '11', '12', '13'], 2, 10],
    ['What is 56 + 38?', ['93', '94', '95', '96'], 1, 10],
    ['What is 82 - 49?', ['32', '33', '34', '35'], 1, 10],
    ['What is 9 × 8?', ['70', '72', '74', '75'], 1, 10],
    ['What is 81 ÷ 9?', ['8', '9', '10', '11'], 1, 10],
    ['What is 34 + 57?', ['89', '90', '91', '92'], 2, 10],
    ['What is 65 - 28?', ['36', '37', '38', '39'], 1, 10],
    ['What is 11 × 12?', ['130', '131', '132', '133'], 2, 10],
    ['What is 132 ÷ 11?', ['10', '11', '12', '13'], 2, 10],
    ['What is 43 + 28?', ['70', '71', '72', '73'], 1, 10],
    ['What is 98 - 57?', ['40', '41', '42', '43'], 1, 10],
    ['What is 18 × 5?', ['85', '90', '92', '94'], 1, 10],
    ['What is 150 ÷ 25?', ['5', '6', '7', '8'], 1, 10],
    ['What is 64 + 29?', ['92', '93', '94', '95'], 1, 10],
    ['What is 56 - 19?', ['36', '37', '38', '39'], 1, 10],
    ['What is 8 × 15?', ['110', '120', '130', '140'], 1, 10],
    ['What is 90 ÷ 9?', ['8', '9', '10', '11'], 2, 10]
];

foreach ($mediumQuestions as $q) {
    $allQuestions[] = createQuestion($q[0], $q[1], $q[2], 'medium', $q[3]);
}

// Hard questions
$hardQuestions = [
    ['What is 256 + 387?', ['642', '643', '644', '645'], 1, 5],
    ['What is 789 - 345?', ['433', '444', '455', '466'], 1, 5],
    ['What is 56 × 34?', ['1890', '1900', '1904', '1920'], 2, 5],
    ['What is 144 ÷ 12?', ['10', '11', '12', '13'], 2, 5],
    ['What is 125 + 678?', ['802', '803', '804', '805'], 2, 5],
    ['What is 920 - 476?', ['444', '445', '446', '447'], 0, 5],
    ['What is 79 × 8?', ['632', '633', '634', '635'], 0, 5],
    ['What is 432 ÷ 16?', ['25', '26', '27', '28'], 2, 5],
    ['What is 967 + 823?', ['1789', '1790', '1791', '1792'], 1, 5],
    ['What is 520 - 199?', ['320', '321', '322', '323'], 1, 5],
    ['What is 128 × 12?', ['1536', '1546', '1556', '1566'], 0, 5],
    ['What is 144 ÷ 8?', ['16', '17', '18', '19'], 2, 5],
    ['What is 563 + 149?', ['711', '712', '713', '714'], 1, 5],
    ['What is 750 - 382?', ['367', '368', '369', '370'], 1, 5],
    ['What is 23 × 17?', ['391', '392', '393', '394'], 0, 5],
    ['What is 896 ÷ 16?', ['54', '55', '56', '57'], 2, 5],
    ['What is 987 + 654?', ['1639', '1640', '1641', '1642'], 2, 5],
    ['What is 765 - 298?', ['466', '467', '468', '469'], 1, 5],
    ['What is 24 × 36?', ['864', '865', '866', '867'], 0, 5],
    ['What is 987 ÷ 9?', ['108', '109', '110', '111'], 1, 5]
];

foreach ($hardQuestions as $q) {
    $allQuestions[] = createQuestion($q[0], $q[1], $q[2], 'hard', $q[3]);
}

// Insert all questions in batches if you have many
$result = $questionsCollection->insertMany($allQuestions);

printf("Inserted %d documents\n", $result->getInsertedCount());
?>