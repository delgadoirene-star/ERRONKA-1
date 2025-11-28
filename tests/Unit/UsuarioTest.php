<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../model/usuario.php';

class UsuarioTest extends TestCase
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
        if (!self::$conn) {
            $this->markTestSkipped('Test database connection not available');
        }
    }
    
    protected function tearDown(): void
    {
        if (self::$conn) {
            cleanTestDatabase(self::$conn);
        }
    }
    
    public function testUsuarioSortu(): void
    {
        $usuario = new \Usuario(
            'Jon',
            'Doe',
            '87654321B',
            'jon.doe@zabala.eus',
            'jondoe',
            'SecurePass123!@#'
        );
        
        $result = $usuario->sortu(self::$conn);
        
        $this->assertTrue($result, 'Usuario sortu behar zen');
        $this->assertGreaterThan(0, $usuario->getId(), 'Usuario ID-a balio positiboa izan behar da');
    }
    
    public function testUsuarioGetters(): void
    {
        $usuario = new \Usuario(
            'Maria',
            'Garcia',
            '11111111C',
            'maria.garcia@zabala.eus',
            'mariagarcia',
            'Pass1234!@#'
        );
        
        $this->assertEquals('Maria', $usuario->getIzena());
        $this->assertEquals('Garcia', $usuario->getAbizena());
        $this->assertEquals('11111111C', $usuario->getNan());
        $this->assertEquals('maria.garcia@zabala.eus', $usuario->getEmail());
        $this->assertEquals('mariagarcia', $usuario->getUser());
        $this->assertEquals('langilea', $usuario->getRol());
        $this->assertEquals('Maria Garcia', $usuario->getNombreCompleto());
    }
    
    public function testLortuEmailAgatik(): void
    {
        // Sortu test usuario bat
        $userId = seedTestUser(self::$conn, [
            'email' => 'find.test@zabala.eus',
            'user' => 'findtest'
        ]);
        
        $usuario = \Usuario::lortuEmailAgatik(self::$conn, 'find.test@zabala.eus');
        
        $this->assertNotNull($usuario, 'Usuario-a aurkitu behar zen');
        $this->assertEquals('find.test@zabala.eus', $usuario['email']);
        $this->assertEquals($userId, $usuario['id']);
    }
    
    public function testLortuEmailAgatikEzDago(): void
    {
        $usuario = \Usuario::lortuEmailAgatik(self::$conn, 'ez.dago@zabala.eus');
        
        $this->assertNull($usuario, 'Ez zen usuario-rik aurkitu behar');
    }
    
    public function testLortuIdAgatik(): void
    {
        $userId = seedTestUser(self::$conn, [
            'izena' => 'TestById',
            'email' => 'byid@zabala.eus',
            'user' => 'byiduser'
        ]);
        
        $usuario = \Usuario::lortuIdAgatik(self::$conn, $userId);
        
        $this->assertNotNull($usuario);
        $this->assertEquals($userId, $usuario['id']);
        $this->assertEquals('TestById', $usuario['izena']);
    }
    
    public function testLortuEmailEdoNANegatik(): void
    {
        seedTestUser(self::$conn, [
            'email' => 'unique@zabala.eus',
            'nan' => '99999999Z',
            'user' => 'uniqueuser'
        ]);
        
        // Email bidez bilatu
        $usuarioByEmail = \Usuario::lortuEmailEdoNANegatik(
            self::$conn,
            'unique@zabala.eus',
            '00000000X'
        );
        $this->assertNotNull($usuarioByEmail);
        $this->assertEquals('unique@zabala.eus', $usuarioByEmail['email']);
        
        // NAN bidez bilatu
        $usuarioByNan = \Usuario::lortuEmailEdoNANegatik(
            self::$conn,
            'other@zabala.eus',
            '99999999Z'
        );
        $this->assertNotNull($usuarioByNan);
        $this->assertEquals('99999999Z', $usuarioByNan['nan']);
    }
    
    public function testAldatuPasahitza(): void
    {
        $usuario = new \Usuario(
            'Password',
            'Changer',
            '22222222D',
            'passchange@zabala.eus',
            'passchange',
            'OldPass123!@#'
        );
        $usuario->sortu(self::$conn);
        
        $result = $usuario->aldatuPasahitza(self::$conn, 'NewPass456!@#');
        
        $this->assertTrue($result, 'Pasahitza aldatu behar zen');
        
        // Egiaztatu pasahitz berria ondo gorde dela
        $updated = \Usuario::lortuIdAgatik(self::$conn, $usuario->getId());
        $this->assertTrue(
            password_verify('NewPass456!@#', $updated['password']),
            'Pasahitz berria ez dator bat'
        );
    }
    
    public function testSetRol(): void
    {
        $usuario = new \Usuario(
            'Admin',
            'Test',
            '33333333E',
            'admin@zabala.eus',
            'admintest',
            'Admin123!@#'
        );
        
        $usuario->setRol('admin');
        $this->assertEquals('admin', $usuario->getRol());
    }
}
