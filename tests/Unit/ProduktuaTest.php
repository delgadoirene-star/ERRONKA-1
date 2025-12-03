<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../model/produktua.php';

class ProduktuaTest extends TestCase
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
            self::$conn->query("DELETE FROM produktua");
        }
    }
    
    public function testProduktuaCreate(): void
    {
        $data = [
            'izena' => 'Test Produktua',
            'deskripzioa' => 'Test deskripzioa',
            'kategoria' => 'Test Kategoria',
            'prezioa' => 19.99,
            'stock' => 100,
            'stock_minimo' => 10,
            'irudia' => ''
        ];
        
        $result = \Produktua::create(self::$conn, $data);
        
        $this->assertTrue($result, 'Produktua sortu behar zen');
        
        // Verify in database
        $stmt = self::$conn->prepare("SELECT * FROM produktua WHERE izena = ?");
        $stmt->bind_param("s", $data['izena']);
        $stmt->execute();
        $produktua = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $this->assertNotNull($produktua);
        $this->assertEquals('Test Produktua', $produktua['izena']);
        $this->assertEquals(19.99, $produktua['prezioa']);
        $this->assertEquals(100, $produktua['stock']);
    }
    
    public function testProduktuaAll(): void
    {
        // Create multiple products
        \Produktua::create(self::$conn, [
            'izena' => 'Produktu 1',
            'deskripzioa' => 'Desc 1',
            'kategoria' => 'Cat 1',
            'prezioa' => 10.00,
            'stock' => 50,
            'stock_minimo' => 5,
            'irudia' => ''
        ]);
        
        \Produktua::create(self::$conn, [
            'izena' => 'Produktu 2',
            'deskripzioa' => 'Desc 2',
            'kategoria' => 'Cat 2',
            'prezioa' => 20.00,
            'stock' => 30,
            'stock_minimo' => 3,
            'irudia' => ''
        ]);
        
        $produktuak = \Produktua::all(self::$conn);
        
        $this->assertCount(2, $produktuak);
        $this->assertIsArray($produktuak);
    }
    
    public function testProduktuaFind(): void
    {
        \Produktua::create(self::$conn, [
            'izena' => 'Findable Product',
            'deskripzioa' => 'Can be found',
            'kategoria' => 'Findable',
            'prezioa' => 15.50,
            'stock' => 25,
            'stock_minimo' => 2,
            'irudia' => ''
        ]);
        
        // Get the created product ID
        $stmt = self::$conn->prepare("SELECT id FROM produktua WHERE izena = ?");
        $izena = 'Findable Product';
        $stmt->bind_param("s", $izena);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $produktuId = $result['id'];
        $stmt->close();
        
        $produktua = \Produktua::find(self::$conn, $produktuId);
        
        $this->assertNotNull($produktua);
        $this->assertEquals('Findable Product', $produktua['izena']);
        $this->assertEquals(15.50, $produktua['prezioa']);
    }
    
    public function testProduktuaUpdate(): void
    {
        \Produktua::create(self::$conn, [
            'izena' => 'Update Test',
            'deskripzioa' => 'Original',
            'kategoria' => 'Original Cat',
            'prezioa' => 25.00,
            'stock' => 40,
            'stock_minimo' => 4,
            'irudia' => ''
        ]);
        
        // Get ID
        $stmt = self::$conn->prepare("SELECT id FROM produktua WHERE izena = ?");
        $izena = 'Update Test';
        $stmt->bind_param("s", $izena);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $produktuId = $result['id'];
        $stmt->close();
        
        // Update
        $updateData = [
            'izena' => 'Update Test',
            'deskripzioa' => 'Updated Description',
            'kategoria' => 'Updated Cat',
            'prezioa' => 30.00,
            'stock' => 60,
            'stock_minimo' => 6,
            'irudia' => ''
        ];
        
        $result = \Produktua::update(self::$conn, $produktuId, $updateData);
        
        $this->assertTrue($result);
        
        // Verify
        $updated = \Produktua::find(self::$conn, $produktuId);
        $this->assertEquals('Updated Description', $updated['deskripzioa']);
        $this->assertEquals(30.00, $updated['prezioa']);
        $this->assertEquals(60, $updated['stock']);
    }
    
    public function testProduktuaDelete(): void
    {
        \Produktua::create(self::$conn, [
            'izena' => 'Delete Test',
            'deskripzioa' => 'Will be deleted',
            'kategoria' => 'Temp',
            'prezioa' => 5.00,
            'stock' => 10,
            'stock_minimo' => 1,
            'irudia' => ''
        ]);
        
        // Get ID
        $stmt = self::$conn->prepare("SELECT id FROM produktua WHERE izena = ?");
        $izena = 'Delete Test';
        $stmt->bind_param("s", $izena);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $produktuId = $result['id'];
        $stmt->close();
        
        // Delete
        $deleteResult = \Produktua::delete(self::$conn, $produktuId);
        
        $this->assertTrue($deleteResult);
        
        // Verify deletion
        $notFound = \Produktua::find(self::$conn, $produktuId);
        $this->assertNull($notFound);
    }
    
    public function testProduktuaStockControl(): void
    {
        \Produktua::create(self::$conn, [
            'izena' => 'Stock Test',
            'deskripzioa' => 'Testing stock',
            'kategoria' => 'Stock',
            'prezioa' => 12.00,
            'stock' => 15,
            'stock_minimo' => 10,
            'irudia' => ''
        ]);
        
        $stmt = self::$conn->prepare("SELECT * FROM produktua WHERE izena = ?");
        $izena = 'Stock Test';
        $stmt->bind_param("s", $izena);
        $stmt->execute();
        $produktua = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Verify stock is above minimum
        $this->assertGreaterThan($produktua['stock_minimo'], $produktua['stock']);
        
        // Update to low stock
        \Produktua::update(self::$conn, $produktua['id'], [
            'izena' => 'Stock Test',
            'deskripzioa' => 'Testing stock',
            'kategoria' => 'Stock',
            'prezioa' => 12.00,
            'stock' => 5,
            'stock_minimo' => 10,
            'irudia' => ''
        ]);
        
        $updated = \Produktua::find(self::$conn, $produktua['id']);
        
        // Verify stock is now below minimum
        $this->assertLessThan($updated['stock_minimo'], $updated['stock']);
    }
}
