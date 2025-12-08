<?php

class PasswordUtils {
    
    public static function validate($password) {
        if (strlen($password) < 8) {
            return "Password must be at least 8 characters long";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            return "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            return "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return "Password must contain at least one number";
        }
        
        return null;
    }
    
    public static function hash($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public static function verify($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>