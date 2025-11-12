<?php 
require_once __DIR__ . '/../../vendor/autoload.php'; // Adjust if needed

class Database {
    private static $instance = null;
    private $client;
    private $db;

    private function __construct() {
        // $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');  
        $dotenv->load();
        
        $this->client = new MongoDB\Client($_ENV['MONGODB_URI']);
        $this->db = $this->client->selectDatabase($_ENV['DB_NAME']);
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getDb() {
        return $this->db;
    }
}
?>