<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../model/langilea.php';

class LangileaTest extends TestCase
{
    private static $conn;
    private static $testUserId;
    
    public static function setUpBeforeClass(): void
    {
        self::$conn = getTestConnection();
        if (self::$conn) {
            cleanTestDatabase(self::$conn);
            // Create a test user for langilea tests
            self::$testUserId = seedTestUser(self::$conn, [
                'email' => 'langilea.test@zabala.eus',
                'user' => 'langilatest'
            ]);
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
            // Clean only langilea table, keep usuario
            self::$conn->query("DELETE FROM langilea");
        }
    }
    
    public function testLangileaConstructor(): void
    {
        $langilea = new \Langilea(
            self::$testUserId,
            'Informatika',
            'Garatzailea',
            '2024-01-01',
            35000.00,
            '666777888',
            'foto.jpg'
        );
        
        $this->assertEquals(self::$testUserId, $langilea->getUsuarioId());
        $this->assertEquals('Informatika', $langilea->getDepartamendua());
        $this->assertEquals('Garatzailea', $langilea->getPozisio());
        $this->assertEquals('2024-01-01', $langilea->getDataKontratazio());
        $this->assertEquals(35000.00, $langilea->getSoldata());
    }
    
    public function testLangileaCreate(): void
    {
        $data = [
            'usuario_id' => self::$testUserId,
            'departamendua' => 'HR',
            'pozisio' => 'Manager',
            'data_kontratazio' => '2024-01-15',
            'soldata' => 45000,
            'telefonoa' => '611222333',
            'foto' => ''
        ];
        
        $result = \Langilea::create(self::$conn, $data);
        
        $this->assertTrue($result, 'Langilea sortu behar zen');
        
        // Verify in database
        $stmt = self::$conn->prepare("SELECT * FROM langilea WHERE usuario_id = ?");
        $stmt->bind_param("i", self::$testUserId);
        $stmt->execute();
        $langilea = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $this->assertNotNull($langilea);
        $this->assertEquals('HR', $langilea['departamendua']);
        $this->assertEquals('Manager', $langilea['pozisio']);
    }
    
    public function testLangileaAll(): void
    {
        // Create multiple langilea records
        $userId1 = seedTestUser(self::$conn, [
            'email' => 'langilea1@zabala.eus',
            'user' => 'lang1',
            'nan' => '11111111A'
        ]);
        
        $userId2 = seedTestUser(self::$conn, [
            'email' => 'langilea2@zabala.eus',
            'user' => 'lang2',
            'nan' => '22222222B'
        ]);
        
        \Langilea::create(self::$conn, [
            'usuario_id' => $userId1,
            'departamendua' => 'IT',
            'pozisio' => 'Developer',
            'data_kontratazio' => '2024-01-01',
            'soldata' => 30000,
            'telefonoa' => '',
            'foto' => ''
        ]);
        
        \Langilea::create(self::$conn, [
            'usuario_id' => $userId2,
            'departamendua' => 'Sales',
            'pozisio' => 'Agent',
            'data_kontratazio' => '2024-02-01',
            'soldata' => 28000,
            'telefonoa' => '',
            'foto' => ''
        ]);
        
        $langileak = \Langilea::all(self::$conn);
        
        $this->assertCount(2, $langileak);
        $this->assertIsArray($langileak);
    }
    
    public function testLangileaFind(): void
    {
        $data = [
            'usuario_id' => self::$testUserId,
            'departamendua' => 'Finance',
            'pozisio' => 'Accountant',
            'data_kontratazio' => '2024-03-01',
            'soldata' => 32000,
            'telefonoa' => '622333444',
            'foto' => ''
        ];
        
        \Langilea::create(self::$conn, $data);
        
        // Get the created langilea ID
        $stmt = self::$conn->prepare("SELECT id FROM langilea WHERE usuario_id = ?");
        $stmt->bind_param("i", self::$testUserId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $langileaId = $result['id'];
        $stmt->close();
        
        $langilea = \Langilea::find(self::$conn, $langileaId);
        
        $this->assertNotNull($langilea);
        $this->assertEquals('Finance', $langilea['departamendua']);
        $this->assertEquals('Accountant', $langilea['pozisio']);
        $this->assertEquals(32000, $langilea['soldata']);
    }
    
    public function testLangileaUpdate(): void
    {
        $data = [
            'usuario_id' => self::$testUserId,
            'departamendua' => 'Marketing',
            'pozisio' => 'Junior',
            'data_kontratazio' => '2024-04-01',
            'soldata' => 25000,
            'telefonoa' => '633444555',
            'foto' => ''
        ];
        
        \Langilea::create(self::$conn, $data);
        
        // Get ID
        $stmt = self::$conn->prepare("SELECT id FROM langilea WHERE usuario_id = ?");
        $stmt->bind_param("i", self::$testUserId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $langileaId = $result['id'];
        $stmt->close();
        
        // Update
        $updateData = [
            'departamendua' => 'Marketing',
            'pozisio' => 'Senior',
            'data_kontratazio' => '2024-04-01',
            'soldata' => 40000,
            'telefonoa' => '633444555',
            'foto' => 'new_foto.jpg'
        ];
        
        $result = \Langilea::update(self::$conn, $langileaId, $updateData);
        
        $this->assertTrue($result);
        
        // Verify update
        $updated = \Langilea::find(self::$conn, $langileaId);
        $this->assertEquals('Senior', $updated['pozisio']);
        $this->assertEquals(40000, $updated['soldata']);
        $this->assertEquals('new_foto.jpg', $updated['foto']);
    }
    
    public function testLangileaDelete(): void
    {
        $data = [
            'usuario_id' => self::$testUserId,
            'departamendua' => 'Temp',
            'pozisio' => 'Temp Worker',
            'data_kontratazio' => '2024-05-01',
            'soldata' => 20000,
            'telefonoa' => '',
            'foto' => ''
        ];
        
        \Langilea::create(self::$conn, $data);
        
        // Get ID
        $stmt = self::$conn->prepare("SELECT id FROM langilea WHERE usuario_id = ?");
        $stmt->bind_param("i", self::$testUserId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $langileaId = $result['id'];
        $stmt->close();
        
        // Delete
        $deleteResult = \Langilea::delete(self::$conn, $langileaId);
        
        $this->assertTrue($deleteResult);
        
        // Verify deletion
        $notFound = \Langilea::find(self::$conn, $langileaId);
        $this->assertNull($notFound);
    }
    
    public function testLangileaSortu(): void
    {
        $langilea = new \Langilea(
            self::$testUserId,
            'Administration',
            'Secretary'
        );
        
        $result = $langilea->sortu(self::$conn);
        
        $this->assertTrue($result);
        $this->assertGreaterThan(0, $langilea->getId());
    }
    
    public function testLangileaSetters(): void
    {
        $langilea = new \Langilea(self::$testUserId);
        
        $langilea->setDepartamendua('New Dept');
        $langilea->setPozisio('New Position');
        $langilea->setSoldata(50000);
        
        $this->assertEquals('New Dept', $langilea->getDepartamendua());
        $this->assertEquals('New Position', $langilea->getPozisio());
        $this->assertEquals(50000, $langilea->getSoldata());
    }
}
