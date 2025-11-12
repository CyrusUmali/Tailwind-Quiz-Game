<?php
require_once __DIR__ . '/../config/db.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController {
    private $users;
    
    public function __construct() {
        $db = Database::getInstance()->getDb();
        $this->users = $db->users;
    }
    
    public function register($data) {
        
        
       
    $required = ['email', 'username', 'password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field '$field' is required", 422);
        }
    }
        
        // Email validation
        if (!preg_match('/^.+@.+\..+$/', $data['email'])) {
            throw new Exception("Invalid email format", 422);
        }
        
        // Check if email already exists
        $existingUser = $this->users->findOne(['email' => $data['email']]);
        if ($existingUser) {
            throw new Exception('Email already registered', 409); // 409 Conflict
        }
        
        // Check if username already exists
        $existingUsername = $this->users->findOne(['username' => $data['username']]);
        if ($existingUsername) {
            throw new Exception('Username already taken', 409);
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Create new user
        $newUser = [
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => $hashedPassword,
            'createdAt' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $insertResult = $this->users->insertOne($newUser);
        
        if ($insertResult->getInsertedCount() !== 1) {
            throw new Exception('Registration failed', 500);
        }
        
        return [
            'success' => true,
            'message' => 'Registration successful',
            'user' => [
                'id' => (string)$insertResult->getInsertedId(),
                'email' => $data['email'],
                'username' => $data['username']
            ]
        ];
    }
 
    
    
    public function login($data) {
        // Find user by email
        $user = $this->users->findOne(['email' => $data['email']]);
        
        if (!$user || !password_verify($data['password'], $user->password)) {
            throw new Exception('Invalid credentials');
        }
        
        // Generate simple token (in production, use JWT)
        $token = bin2hex(random_bytes(32));
        
        return [
            'success' => true,
            'token' => $token,
            'userId' => (string)$user->_id,
            'username' => $user->username
        ];
    }
}
?>