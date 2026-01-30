<?php

namespace Waavi\Translation\Test\Cache;

use Illuminate\Cache\ArrayStore;
use Waavi\Translation\Cache\SimpleRepository;
use Waavi\Translation\Test\TestCase;

class SimpleRepositoryTest extends TestCase
{
    protected $repo;

    public function setUp(): void
    {
        parent::setUp();
        $this->repo = new SimpleRepository(new ArrayStore, 'translation');
    }

    /**
     * @test
     */
    public function test_has_with_no_entry()
    {
        $this->assertFalse($this->repo->has('en', 'group', '*'));
    }

    /**
     * @test
     */
    public function test_has_returns_true_if_entry()
    {
        $this->repo->put('en', 'group', '*', ['key' => 'value'], 60);
        $this->assertTrue($this->repo->has('en', 'group', '*'));
    }

    /**
     * @test
     */
    public function test_get_returns_null_if_empty()
    {
        $this->assertNull($this->repo->get('en', 'group', '*'));
    }

    /**
     * @test
     */
    public function test_get_return_content_if_hit()
    {
        $this->repo->put('en', 'group', '*', ['key' => 'value'], 60);
        $this->assertEquals(['key' => 'value'], $this->repo->get('en', 'group', '*'));
    }

    /**
     * @test
     */
    public function test_flush_removes_only_specific_entry()
    {
        $this->repo->put('en', 'messages', '*', ['hello' => 'Hello'], 60);
        $this->repo->put('es', 'messages', '*', ['hello' => 'Hola'], 60);

        // Flush only the English entry
        $this->repo->flush('en', 'messages', '*');

        // English should be gone
        $this->assertNull($this->repo->get('en', 'messages', '*'));
        // Spanish should still exist
        $this->assertEquals(['hello' => 'Hola'], $this->repo->get('es', 'messages', '*'));
    }

    /**
     * @test
     */
    public function test_flush_all_removes_all_entries()
    {
        $this->repo->put('en', 'messages', '*', ['hello' => 'Hello'], 60);
        $this->repo->put('es', 'messages', '*', ['hello' => 'Hola'], 60);
        $this->repo->put('nl', 'validation', '*', ['required' => 'Verplicht'], 60);

        // Flush all translation cache
        $this->repo->flushAll();

        // All entries should be gone
        $this->assertNull($this->repo->get('en', 'messages', '*'));
        $this->assertNull($this->repo->get('es', 'messages', '*'));
        $this->assertNull($this->repo->get('nl', 'validation', '*'));
    }

    /**
     * @test
     */
    public function test_registry_tracks_keys()
    {
        $this->repo->put('en', 'messages', '*', ['hello' => 'Hello'], 60);
        $this->repo->put('es', 'messages', '*', ['hello' => 'Hola'], 60);

        // After flushing all, adding new entries should work correctly
        $this->repo->flushAll();

        $this->repo->put('nl', 'messages', '*', ['hello' => 'Hallo'], 60);
        $this->assertEquals(['hello' => 'Hallo'], $this->repo->get('nl', 'messages', '*'));
    }
}
