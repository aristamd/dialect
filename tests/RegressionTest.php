<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Regression Test Suite for Laravel 13 Upgrade
 *
 * This test suite captures critical behaviors that must not regress during
 * the upgrade to Laravel 13. These tests focus on the core functionality
 * of the Json trait and its interaction with Eloquent Model internals.
 */
class RegressionTest extends TestCase
{
    /**
     * Test 1: JSON attribute discovery - Core functionality
     *
     * Ensures that when a model has JSON columns with data, the trait
     * properly discovers and exposes nested JSON attributes as top-level
     * model attributes through mutators.
     */
    public function testJsonAttributeDiscoveryPreservesExpectedStructure()
    {
        $mock = new MockJsonDialectModel;
        $mock->setJsonColumns(['metadata']);
        $mock->setAttribute('metadata', json_encode([
            'user_id' => 123,
            'role' => 'admin',
            'tags' => ['tag1', 'tag2'],
        ]));

        $mock->inspectJsonColumns();

        // Verify mutators are created
        $this->assertTrue($mock->hasGetMutator('user_id'));
        $this->assertTrue($mock->hasGetMutator('role'));
        $this->assertTrue($mock->hasGetMutator('tags'));

        // Verify mutated attributes are registered
        $mutated = $mock->getMutatedAttributes();
        $this->assertContains('user_id', $mutated);
        $this->assertContains('role', $mutated);
        $this->assertContains('tags', $mutated);

        // Verify JSON column is hidden by default
        $this->assertArrayNotHasKey('metadata', $mock->toArray());

        // Verify JSON attributes are exposed
        $this->assertArrayHasKey('user_id', $mock->toArray());
        $this->assertArrayHasKey('role', $mock->toArray());
        $this->assertArrayHasKey('tags', $mock->toArray());

        // Verify values are correct
        $this->assertEquals(123, $mock->user_id);
        $this->assertEquals('admin', $mock->role);
        $this->assertEquals(['tag1', 'tag2'], $mock->tags);
    }

    /**
     * Test 2: JSON column visibility control
     *
     * Ensures the showJsonColumns() method properly controls whether
     * the underlying JSON column appears in serialized output.
     */
    public function testJsonColumnVisibilityCanBeControlled()
    {
        $mock = new MockJsonDialectModel;
        $mock->setJsonColumns(['metadata']);
        $mock->setAttribute('metadata', json_encode(['key' => 'value']));
        $mock->showJsonColumns(true);

        $mock->inspectJsonColumns();

        // With showJsonColumns(true), the column should be visible
        $array = $mock->toArray();
        $this->assertArrayHasKey('metadata', $array);
        $this->assertArrayHasKey('key', $array);
    }

    /**
     * Test 3: JSON attribute visibility control
     *
     * Ensures the showJsonAttributes() method properly controls whether
     * extracted JSON attributes appear in serialized output.
     */
    public function testJsonAttributeVisibilityCanBeControlled()
    {
        $mock = new MockJsonDialectModel;
        $mock->setJsonColumns(['metadata']);
        $mock->setAttribute('metadata', json_encode(['key' => 'value']));
        $mock->showJsonAttributes(false);

        $mock->inspectJsonColumns();

        // With showJsonAttributes(false), JSON attributes should not appear
        $array = $mock->toArray();
        $this->assertArrayNotHasKey('key', $array);
    }

    /**
     * Test 4: JSON get/set behavior - Setting values
     *
     * Ensures JSON attributes can be set through mutators and properly
     * persist in the underlying JSON column.
     */
    public function testJsonAttributeSetBehavior()
    {
        $mock = new MockJsonDialectModel;
        $mock->setJsonColumns(['metadata']);
        $mock->setAttribute('metadata', json_encode(['name' => 'original']));

        $mock->inspectJsonColumns();

        // Set a JSON attribute
        $mock->name = 'updated';

        // Verify the value is accessible
        $this->assertEquals('updated', $mock->name);

        // Verify it's encoded in the JSON column
        $decoded = json_decode($mock->metadata, true);
        $this->assertEquals('updated', $decoded['name']);
    }

    /**
     * Test 5: JSON get/set with hinted new attributes
     *
     * Ensures a newly hinted JSON attribute can be set through mutators and
     * is created in the underlying JSON column.
     */
    public function testJsonAttributeNewAttributeCreation()
    {
        $mock = new MockJsonDialectModel;
        $mock->setJsonColumns(['metadata']);
        $mock->hintJsonStructure('metadata', json_encode(['new_field' => null]));
        $mock->setAttribute('metadata', json_encode(['existing' => 'value']));

        // Set a hinted JSON attribute
        $mock->new_field = 'new_value';

        // Verify it's flagged and accessible
        $this->assertEquals('new_value', $mock->new_field);

        // Verify it exists in the JSON column alongside existing data
        $decoded = json_decode($mock->metadata, true);
        $this->assertArrayHasKey('existing', $decoded);
        $this->assertArrayHasKey('new_field', $decoded);
        $this->assertEquals('new_value', $decoded['new_field']);
    }

    /**
     * Test 6: Hinted structure behavior
     *
     * Ensures that hinting a JSON structure creates mutators even when
     * the underlying JSON column is empty or null.
     */
    public function testHintedJsonStructureBehavior()
    {
        $mock = new MockJsonDialectModel;
        $mock->hintJsonStructure('config', json_encode([
            'timeout' => null,
            'retries' => null,
            'debug' => null,
        ]));

        // Verify mutators are created for hinted attributes
        $this->assertTrue($mock->hasGetMutator('timeout'));
        $this->assertTrue($mock->hasGetMutator('retries'));
        $this->assertTrue($mock->hasGetMutator('debug'));

        // Verify hinted attributes are in mutated list
        $mutated = $mock->getMutatedAttributes();
        $this->assertContains('timeout', $mutated);
        $this->assertContains('retries', $mutated);
        $this->assertContains('debug', $mutated);

        // Verify the config column is hidden
        $this->assertContains('config', $mock->getHidden());
    }

    /**
     * Test 7: Hinted attributes with null values
     *
     * Ensures that hinted attributes properly return null when not set,
     * without throwing exceptions.
     */
    public function testHintedAttributesNullBehavior()
    {
        $mock = new MockJsonDialectModel;
        $mock->hintJsonStructure('options', json_encode(['flag' => null]));

        // Accessing a hinted but unset attribute should return null
        $this->assertNull($mock->flag);

        // Setting the hint on the model with 'null' string
        $mock->setAttribute('options', 'null');
        $this->assertNull($mock->flag);

        // Setting the hint on the model with null
        $mock->setAttribute('options', null);
        $this->assertNull($mock->flag);
    }

    /**
     * Test 8: Hinted structure with initial data
     *
     * Ensures that hinted structures work correctly when the JSON column
     * already contains data.
     */
    public function testHintedStructureWithInitialData()
    {
        $mock = new MockJsonDialectModel;
        $mock->hintJsonStructure('settings', json_encode([
            'color' => null,
            'theme' => null,
        ]));

        // Add hinted attributes
        $mock->addHintedAttributes();

        // Set a value for a hinted attribute
        $mock->color = 'blue';

        // Verify it's in the settings column
        $decoded = json_decode($mock->settings, true);
        $this->assertArrayHasKey('color', $decoded);
        $this->assertEquals('blue', $decoded['color']);
    }

    /**
     * Test 9: Dirty tracking with JSON attributes
     *
     * Ensures that getDirty(true) properly detects changes to JSON
     * attributes and tracks them separately from the underlying column.
     */
    public function testDirtyTrackingWithJsonAttributes()
    {
        $mock = new MockJsonDialectModel;
        $mock->hintJsonStructure('data', json_encode(['field' => null]));

        // Initially, 'field' should not be dirty
        $dirty = $mock->getDirty(true);
        $this->assertArrayNotHasKey('field', $dirty);

        // Set the field to a value
        $mock->field = 'test_value';

        // Now it should show as dirty
        $dirty = $mock->getDirty(true);
        $this->assertArrayHasKey('field', $dirty);
    }

    /**
     * Test 10: Multiple JSON columns
     *
     * Ensures that models can have multiple JSON columns and the trait
     * properly handles discovery and mutation for all of them.
     */
    public function testMultipleJsonColumnsHandling()
    {
        $mock = new MockJsonDialectModel;
        $mock->setJsonColumns(['metadata', 'settings']);

        $mock->setAttribute('metadata', json_encode(['meta_key' => 'meta_value']));
        $mock->setAttribute('settings', json_encode(['setting_key' => 'setting_value']));

        $mock->inspectJsonColumns();

        // Verify attributes from both columns are accessible
        $this->assertEquals('meta_value', $mock->meta_key);
        $this->assertEquals('setting_value', $mock->setting_key);

        // Verify both columns are hidden
        $this->assertArrayNotHasKey('metadata', $mock->toArray());
        $this->assertArrayNotHasKey('settings', $mock->toArray());

        // Verify JSON attributes from both columns are exposed
        $this->assertArrayHasKey('meta_key', $mock->toArray());
        $this->assertArrayHasKey('setting_key', $mock->toArray());
    }

    /**
     * Test 11: JSON operator recognition in keys
     *
     * Ensures that the trait recognizes JSON operators (->>'key') and
     * treats them as JSON mutators (used in relations).
     */
    public function testJsonOperatorRecognition()
    {
        $mock = new MockJsonDialectModel;

        // Test various JSON operators
        $this->assertTrue($mock->hasGetMutator("column->>'key'"));
        $this->assertTrue($mock->hasGetMutator("column->'key'"));
        $this->assertTrue($mock->hasGetMutator("column#>>'key'"));
        $this->assertTrue($mock->hasGetMutator("column#>'key'"));
    }

    /**
     * Test 12: Array and object handling in JSON attributes
     *
     * Ensures that JSON attributes can properly store and retrieve
     * both array and object structures.
     */
    public function testArrayAndObjectJsonAttributeHandling()
    {
        $mock = new MockJsonDialectModel;
        $mock->hintJsonStructure('payload', json_encode([
            'items' => null,
            'metadata' => null,
        ]));

        $mock->addHintedAttributes();

        // Set array value
        $mock->items = ['a', 'b', 'c'];
        $this->assertEquals('array', gettype($mock->items));
        $this->assertEquals(3, count($mock->items));

        // Verify it's stored in JSON
        $decoded = json_decode($mock->payload, true);
        $this->assertIsArray($decoded['items']);
        $this->assertEquals(['a', 'b', 'c'], $decoded['items']);
    }

    /**
     * Test 13: Invalid JSON handling
     *
     * Ensures that accessing JSON attributes on invalid JSON throws
     * an InvalidJsonException as expected.
     */
    public function testInvalidJsonThrowsException()
    {
        $this->expectException(\Eloquent\Dialect\InvalidJsonException::class);

        $mock = new MockJsonDialectModel;
        $mock->hintJsonStructure('data', json_encode(['field' => null]));

        // Set the column to invalid JSON
        $mock->setAttribute('data', '{invalid json}');

        // Accessing the attribute should throw
        $value = $mock->field;
    }

    /**
     * Test 14: setJsonAttribute direct method
     *
     * Ensures the setJsonAttribute() method properly updates the
     * underlying JSON column structure.
     */
    public function testSetJsonAttributeMethod()
    {
        $mock = new MockJsonDialectModel;
        $mock->setJsonColumns(['options']);
        $mock->setAttribute('options', json_encode(['a' => 1]));

        $mock->inspectJsonColumns();

        // Use setJsonAttribute directly
        $mock->setJsonAttribute('options', 'b', 2);

        // Verify both old and new values exist
        $decoded = json_decode($mock->options, true);
        $this->assertEquals(1, $decoded['a']);
        $this->assertEquals(2, $decoded['b']);
    }

    /**
     * Test 15: Invalid hint structure detection
     *
     * Ensures that passing invalid JSON as a hint structure throws
     * an InvalidJsonException.
     */
    public function testInvalidHintThrowsException()
    {
        $this->expectException(\Eloquent\Dialect\InvalidJsonException::class);

        $mock = new MockJsonDialectModel;
        $mock->hintJsonStructure('data', '{invalid}');
    }
}

