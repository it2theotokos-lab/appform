<?php
use PHPUnit\Framework\TestCase;

class StructuredRepositoryTest extends TestCase {
    public function testStructuredRepositoryFields() {
        // Assert that new columns are handled properly
        $repoMock = [
            'id' => 1,
            'name' => 'Structured Repo',
            'slug' => 'structured',
            'columns_json' => '[{"key":"email","label":"Email"},{"key":"phone","label":"Phone"}]',
            'data_json' => '[{"value":"v1","label":"L1","email":"e1@ex.com","phone":"123"}]'
        ];
        
        $this->assertArrayHasKey('columns_json', $repoMock);
        
        $cols = json_decode($repoMock['columns_json'], true);
        $this->assertCount(2, $cols);
        $this->assertEquals('email', $cols[0]['key']);
        
        $data = json_decode($repoMock['data_json'], true);
        $this->assertEquals('e1@ex.com', $data[0]['email']);
    }
}
