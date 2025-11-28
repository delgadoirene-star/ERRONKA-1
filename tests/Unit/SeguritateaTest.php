<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../model/seguritatea.php';

class SeguritateaTest extends TestCase
{
    private static $conn;
    
    public static function setUpBeforeClass(): void
    {
        self::$conn = getTestConnection();
        if (self::$conn) {
            cleanTestDatabase(self::$conn);
        }
    }
    
    protected function setUp(): void
    {
        // Clean session for each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }
    
    protected function tearDown(): void
    {
        $_SESSION = [];
    }
    
    public function testHasieratuSesioa(): void
    {
        \Seguritatea::hasieratuSesioa();
        
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $this->assertArrayHasKey('last_activity', $_SESSION);
    }
    
    public function testGenerateCSRFToken(): void
    {
        \Seguritatea::hasieratuSesioa();
        
        $token = \Seguritatea::generateCSRFToken();
        
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
        $this->assertEquals($token, $_SESSION['csrf_token']);
        $this->assertArrayHasKey('csrf_token_time', $_SESSION);
    }
    
    public function testVerifyCSRFTokenValid(): void
    {
        \Seguritatea::hasieratuSesioa();
        
        $token = \Seguritatea::generateCSRFToken();
        $result = \Seguritatea::verifyCSRFToken($token);
        
        $this->assertTrue($result, 'Token baliozkoa izan behar da');
    }
    
    public function testVerifyCSRFTokenInvalid(): void
    {
        \Seguritatea::hasieratuSesioa();
        
        \Seguritatea::generateCSRFToken();
        $result = \Seguritatea::verifyCSRFToken('invalid_token');
        
        $this->assertFalse($result, 'Token baliogabea izan behar da');
    }
    
    public function testVerifyCSRFTokenExpired(): void
    {
        \Seguritatea::hasieratuSesioa();
        
        $token = \Seguritatea::generateCSRFToken();
        
        // Simulate expired token (older than CSRF_TOKEN_LIFETIME)
        $_SESSION['csrf_token_time'] = time() - 3700; // 1 hour ago
        
        $result = \Seguritatea::verifyCSRFToken($token);
        
        $this->assertFalse($result, 'Token iraungita egon behar da');
    }
    
    public function testBalioztaPasahitzaValid(): void
    {
        $validPasswords = [
            'Test1234!@#',
            'SecureP@ss99',
            'MyP@ssw0rd!',
            'Abc123!@#Xyz'
        ];
        
        foreach ($validPasswords as $password) {
            $this->assertTrue(
                \Seguritatea::balioztaPasahitza($password),
                "Pasahitza baliozkoa izan behar da: $password"
            );
        }
    }
    
    public function testBalioztaPasahitzaInvalid(): void
    {
        $invalidPasswords = [
            'short1!',           // Too short
            'nouppercase123!',   // No uppercase
            'NOLOWERCASE123!',   // No lowercase
            'NoDigits!@#',       // No digits
            'NoSpecial123',      // No special chars
            'Test1234'           // No special chars
        ];
        
        foreach ($invalidPasswords as $password) {
            $this->assertFalse(
                \Seguritatea::balioztaPasahitza($password),
                "Pasahitza baliogabea izan behar da: $password"
            );
        }
    }
    
    public function testEgiaztaLoginIntentoa(): void
    {
        \Seguritatea::hasieratuSesioa();
        
        $email = 'test@zabala.eus';
        
        // First 5 attempts should be allowed
        for ($i = 1; $i <= 5; $i++) {
            $result = \Seguritatea::egiaztaLoginIntentoa($email);
            $this->assertTrue($result, "Saiakera $i baimenduta egon behar da");
        }
        
        // 6th attempt should be blocked
        $result = \Seguritatea::egiaztaLoginIntentoa($email);
        $this->assertFalse($result, '6. saiakera blokeatuta egon behar da');
    }
    
    public function testZurituLoginIntentoak(): void
    {
        \Seguritatea::hasieratuSesioa();
        
        $email = 'reset@zabala.eus';
        
        // Make some failed attempts
        \Seguritatea::egiaztaLoginIntentoa($email);
        \Seguritatea::egiaztaLoginIntentoa($email);
        
        // Reset attempts
        \Seguritatea::zuritu_login_intentoak($email);
        
        // Should be able to try again
        $result = \Seguritatea::egiaztaLoginIntentoa($email);
        $this->assertTrue($result, 'Saiakerak berrezarri ondoren, berriz ahalegindu ahal da');
    }
    
    public function testEgiaztaRateLimit(): void
    {
        \Seguritatea::hasieratuSesioa();
        
        $action = 'test_action';
        $identifier = 'test@zabala.eus';
        
        // First 5 attempts allowed
        for ($i = 1; $i <= 5; $i++) {
            $result = \Seguritatea::egiaztaRateLimit($action, $identifier, 5);
            $this->assertTrue($result);
        }
        
        // 6th should be blocked
        $result = \Seguritatea::egiaztaRateLimit($action, $identifier, 5);
        $this->assertFalse($result);
    }
    
    public function testEgiaztautentifikazioValid(): void
    {
        if (!self::$conn) {
            $this->markTestSkipped('DB connection not available');
        }
        
        // Create test user
        $password = 'Test12345!@#';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = self::$conn->prepare(
            "INSERT INTO usuario (izena, abizena, nan, email, user, password, rol, aktibo) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
        );
        $izena = 'Auth';
        $abizena = 'Test';
        $nan = '44444444F';
        $email = 'auth@zabala.eus';
        $user = 'authtest';
        $rol = 'langilea';
        
        $stmt->bind_param("sssssss", $izena, $abizena, $nan, $email, $user, $hash, $rol);
        $stmt->execute();
        $stmt->close();
        
        // Test authentication
        $result = \Seguritatea::egiaztautentifikazioa(self::$conn, $email, $password);
        
        $this->assertNotFalse($result);
        $this->assertIsArray($result);
        $this->assertEquals($email, $result['email']);
    }
    
    public function testEgiaztautentifikazioInvalidPassword(): void
    {
        if (!self::$conn) {
            $this->markTestSkipped('DB connection not available');
        }
        
        // Create test user
        $hash = password_hash('CorrectPass123!@#', PASSWORD_DEFAULT);
        
        $stmt = self::$conn->prepare(
            "INSERT INTO usuario (izena, abizena, nan, email, user, password, rol, aktibo) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
        );
        $izena = 'Wrong';
        $abizena = 'Pass';
        $nan = '55555555G';
        $email = 'wrongpass@zabala.eus';
        $user = 'wrongpass';
        $rol = 'langilea';
        
        $stmt->bind_param("sssssss", $izena, $abizena, $nan, $email, $user, $hash, $rol);
        $stmt->execute();
        $stmt->close();
        
        // Test with wrong password
        $result = \Seguritatea::egiaztautentifikazioa(self::$conn, $email, 'WrongPass123!@#');
        
        $this->assertFalse($result);
    }
    
    public function testLogSeguritatea(): void
    {
        if (!self::$conn) {
            $this->markTestSkipped('DB connection not available');
        }
        
        \Seguritatea::logSeguritatea(
            self::$conn,
            'TEST_EVENT',
            'test@zabala.eus',
            null
        );
        
        // Verify log was created
        $result = self::$conn->query(
            "SELECT * FROM seguritatea_loga WHERE event_type = 'TEST_EVENT' ORDER BY id DESC LIMIT 1"
        );
        
        $log = $result->fetch_assoc();
        
        $this->assertNotNull($log);
        $this->assertEquals('TEST_EVENT', $log['event_type']);
        $this->assertEquals('test@zabala.eus', $log['event_scope']);
    }
}
