<?php

declare(strict_types=1);

namespace Drupal\Tests\options\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\options\OptionsAllowedValuesInterface;
use Drupal\options\Plugin\Field\FieldType\ListItemBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the caching of options allowed values.
 */
#[Group('options')]
#[RunTestsInSeparateProcesses]
class OptionsAllowedValuesTest extends OptionsFieldUnitTestBase {

  /**
   * Tests that each field is cached in its own item with its own cache tags.
   */
  public function testCachedPerField(): void {
    $service = $this->container->get(OptionsAllowedValuesInterface::class);
    $cache = $this->container->get('cache.memory');

    // Create a second list field on the same entity type.
    $second_storage = FieldStorageConfig::create([
      'field_name' => 'test_options_2',
      'entity_type' => 'entity_test',
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => [
        'allowed_values' => [10 => 'Ten', 20 => 'Twenty'],
      ],
    ]);
    $second_storage->save();

    // Starting from a cold cache, request the allowed values of both fields.
    $this->assertSame([1 => 'One', 2 => 'Two', 3 => 'Three'], $service->getAllowedValues($this->fieldStorage));
    $this->assertSame([10 => 'Ten', 20 => 'Twenty'], $service->getAllowedValues($second_storage));

    // Each field is cached in a separate item tagged with the cache tags of
    // its own field storage.
    $first_item = $cache->get('options.allowed_values:entity_test:test_options:any');
    $second_item = $cache->get('options.allowed_values:entity_test:test_options_2:any');
    $this->assertSame([1 => 'One', 2 => 'Two', 3 => 'Three'], $first_item->data);
    $this->assertSame([10 => 'Ten', 20 => 'Twenty'], $second_item->data);
    $this->assertContains('config:field.storage.entity_test.test_options', $first_item->tags);
    $this->assertContains('config:field.storage.entity_test.test_options_2', $second_item->tags);

    // Updating the first field storage invalidates its cached allowed values,
    // even though the allowed values of another field were cached later. The
    // second field stays cached.
    $this->fieldStorage->setSetting('allowed_values', [1 => 'First', 2 => 'Second'])->save();
    $this->assertFalse($cache->get('options.allowed_values:entity_test:test_options:any'));
    $this->assertNotFalse($cache->get('options.allowed_values:entity_test:test_options_2:any'));
    $this->assertSame([1 => 'First', 2 => 'Second'], $service->getAllowedValues($this->fieldStorage));
    $this->assertSame([10 => 'Ten', 20 => 'Twenty'], $service->getAllowedValues($second_storage));
  }

  /**
   * Tests that the field storage form submit resets the cached values.
   */
  public function testSubmitFieldStorageUpdate(): void {
    $service = $this->container->get(OptionsAllowedValuesInterface::class);

    $this->assertSame([1 => 'One', 2 => 'Two', 3 => 'Three'], $service->getAllowedValues($this->fieldStorage));

    // Change the setting without saving: the cached values are still returned.
    $this->fieldStorage->setSetting('allowed_values', [1 => 'First']);
    $this->assertSame([1 => 'One', 2 => 'Two', 3 => 'Three'], $service->getAllowedValues($this->fieldStorage));

    // The field storage form submit handler resets all cached allowed values.
    ListItemBase::submitFieldStorageUpdate();
    $this->assertSame([1 => 'First'], $service->getAllowedValues($this->fieldStorage));
  }

}
