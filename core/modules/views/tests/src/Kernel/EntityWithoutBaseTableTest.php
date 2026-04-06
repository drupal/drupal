<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\entity_test\EntityTestHelper;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\views\FieldViewsDataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests field views data in an edge case scenario.
 *
 * Tests the field views data case when:
 * - The entity type doesn't have a data table.
 * - A configurable field storage is translatable.
 * - It has at least two bundles exposing the field with different
 *   translatability settings.
 */
#[CoversClass(FieldViewsDataProvider::class)]
#[Group('views')]
#[RunTestsInSeparateProcesses]
class EntityWithoutBaseTableTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'entity_test',
    'user',
    'views',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // This entity type doesn't have a data table.
    $this->installEntitySchema('entity_test_label');

    EntityTestHelper::createBundle(bundle: 'bundle_with_translatable_field', entity_type: 'entity_test_label');
    EntityTestHelper::createBundle(bundle: 'bundle_with_untranslatable_field', entity_type: 'entity_test_label');

    FieldStorageConfig::create([
      'entity_type' => 'entity_test_label',
      'type' => 'string',
      'field_name' => 'string_field',
      'translatable' => TRUE,
    ])->save();
    // This field instance is translatable.
    FieldConfig::create([
      'entity_type' => 'entity_test_label',
      'bundle' => 'bundle_with_translatable_field',
      'field_name' => 'string_field',
      'translatable' => TRUE,
    ])->save();
    // This field instance is not translatable.
    FieldConfig::create([
      'entity_type' => 'entity_test_label',
      'bundle' => 'bundle_with_untranslatable_field',
      'field_name' => 'string_field',
      'translatable' => FALSE,
    ])->save();
  }

  /**
   * Tests that the entity without a data table doesn't emit deprecation notice.
   *
   * @legacy-covers ::defaultFieldImplementation
   */
  public function testEntityWithoutDataTable(): void {
    $entity_type = $this->container->get('entity_type.manager')->getDefinition('entity_test_label');
    $this->assertNull($entity_type->getDataTable());
    $this->assertNotNull($entity_type->getBaseTable());
    $views_data = $this->container->get('views.views_data')->getAll();
    $this->assertArrayHasKey($entity_type->getBaseTable(), $views_data);
  }

}
