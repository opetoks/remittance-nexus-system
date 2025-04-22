
<?php
require_once 'config/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Register a new user
    public function register($data) {
        // Prepare query
        $this->db->query('INSERT INTO users (username, password, full_name, role, email, phone) VALUES (:username, :password, :full_name, :role, :email, :phone)');
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Bind values
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':password', $data['password']);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':role', $data['role']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);
        
        // Execute
        if($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }
    
    // Login user
    public function login($email, $password) {
        // Prepare query
        $this->db->query('SELECT * FROM users WHERE email = :email AND status = "active"');
        
        // Bind value
        $this->db->bind(':email', $email);
        
        // Get single record
        $user = $this->db->single();
        
        if($user) {
            // Verify password
            $hashed_password = $user['password'];
            if(password_verify($password, $hashed_password)) {
                return $user;
            }
        }
        
        return false;
    }
    
    // Find user by username
    public function findUserByUsername($username) {
        // Prepare query
        $this->db->query('SELECT * FROM users WHERE username = :username');
        
        // Bind value
        $this->db->bind(':username', $username);
        
        // Get single record
        $user = $this->db->single();
        
        // Check if user exists
        if($this->db->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    // Find user by email
    public function findUserByEmail($email) {
        // Prepare query
        $this->db->query('SELECT * FROM users WHERE email = :email');
        
        // Bind value
        $this->db->bind(':email', $email);
        
        // Get single record
        $user = $this->db->single();
        
        // Check if user exists
        if($this->db->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    // Get user by ID
    public function getUserById($id) {
        // Prepare query
        $this->db->query('SELECT * FROM users WHERE id = :id');
        
        // Bind value
        $this->db->bind(':id', $id);
        
        // Get single record
        return $this->db->single();
    }
    
    // Get all users
    public function getUsers() {
        // Prepare query
        $this->db->query('SELECT * FROM users ORDER BY full_name ASC');
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Get users by role
    public function getUsersByRole($role) {
        // Prepare query
        $this->db->query('SELECT * FROM users WHERE role = :role AND status = "active" ORDER BY full_name ASC');
        
        // Bind value
        $this->db->bind(':role', $role);
        
        // Get result set
        return $this->db->resultSet();
    }
    
    // Update user
    public function updateUser($data) {
        // Prepare query
        $this->db->query('UPDATE users SET full_name = :full_name, email = :email, phone = :phone, role = :role, status = :status WHERE id = :id');
        
        // Bind values
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':role', $data['role']);
        $this->db->bind(':status', $data['status']);
        
        // Execute
        if($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }
    
    // Change password
    public function changePassword($id, $new_password) {
        // Hash password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Prepare query
        $this->db->query('UPDATE users SET password = :password WHERE id = :id');
        
        // Bind values
        $this->db->bind(':id', $id);
        $this->db->bind(':password', $hashed_password);
        
        // Execute
        if($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }
}
?>
