<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use App\Traits\HasSortableColumns;
use PHPUnit\Framework\TestCase;

class HasSortableColumnsTest extends TestCase
{
    private object $traitInstance;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an anonymous class that uses the trait
        // Classes using HasSortableColumns must define sortField and sortDirection properties
        $this->traitInstance = new class {
            use HasSortableColumns;

            public string $sortField = 'created_at';
            public string $sortDirection = 'desc';

            protected function allowedSortColumns(): array
            {
                return ['id', 'name', 'email', 'created_at', 'updated_at'];
            }
        };
    }

    public function test_sort_by_accepts_valid_column(): void
    {
        $this->traitInstance->sortBy('name');
        
        $this->assertEquals('name', $this->traitInstance->sortField);
        $this->assertEquals('asc', $this->traitInstance->sortDirection);
    }

    public function test_sort_by_rejects_invalid_column(): void
    {
        $originalField = $this->traitInstance->sortField;
        $originalDirection = $this->traitInstance->sortDirection;
        
        // Try to sort by SQL injection payload
        $this->traitInstance->sortBy('name desc, (select sleep(5))--');
        
        // Values should remain unchanged
        $this->assertEquals($originalField, $this->traitInstance->sortField);
        $this->assertEquals($originalDirection, $this->traitInstance->sortDirection);
    }

    public function test_sort_by_rejects_column_not_in_allowed_list(): void
    {
        $originalField = $this->traitInstance->sortField;
        
        $this->traitInstance->sortBy('password_hash');
        
        $this->assertEquals($originalField, $this->traitInstance->sortField);
    }

    public function test_toggle_direction_on_same_column(): void
    {
        $this->traitInstance->sortField = 'name';
        $this->traitInstance->sortDirection = 'asc';
        
        $this->traitInstance->sortBy('name');
        
        $this->assertEquals('name', $this->traitInstance->sortField);
        $this->assertEquals('desc', $this->traitInstance->sortDirection);
    }

    public function test_get_sort_field_returns_sanitized_field(): void
    {
        $this->traitInstance->sortField = 'name desc, (select 1)--';
        
        // Should return default since field is invalid
        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('getSortField');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->traitInstance);
        
        $this->assertEquals('created_at', $result);
    }

    public function test_get_sort_field_returns_valid_field(): void
    {
        $this->traitInstance->sortField = 'name';
        
        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('getSortField');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->traitInstance);
        
        $this->assertEquals('name', $result);
    }

    public function test_get_sort_direction_returns_sanitized_direction(): void
    {
        $this->traitInstance->sortDirection = 'asc; DROP TABLE users;--';
        
        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('getSortDirection');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->traitInstance);
        
        $this->assertEquals('desc', $result);
    }

    public function test_get_sort_direction_accepts_asc(): void
    {
        $this->traitInstance->sortDirection = 'asc';
        
        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('getSortDirection');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->traitInstance);
        
        $this->assertEquals('asc', $result);
    }

    public function test_get_sort_direction_accepts_desc(): void
    {
        $this->traitInstance->sortDirection = 'desc';
        
        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('getSortDirection');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->traitInstance);
        
        $this->assertEquals('desc', $result);
    }

    public function test_get_sort_direction_is_case_insensitive(): void
    {
        $this->traitInstance->sortDirection = 'ASC';
        
        $reflection = new \ReflectionClass($this->traitInstance);
        $method = $reflection->getMethod('getSortDirection');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->traitInstance);
        
        $this->assertEquals('asc', $result);
    }
}
