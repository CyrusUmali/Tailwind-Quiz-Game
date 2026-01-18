<?php
require_once __DIR__ . '/server/config/db.php';

$db = Database::getInstance()->getDb();

$db->test->insertOne([
    'status' => 'working'
]);

echo "MongoDB Atlas connection OK";
