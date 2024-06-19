<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests discovery of field type categories provided by modules.
 *
 * @group field
 */
class FieldTypeCategoryDiscoveryTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'field_plugins_test',
  ];

  /**
   * Tests custom field type categories created by modules.
   */
  public function testFieldTypeCategories(): void {
    $category = \Drupal::service('plugin.manager.field.field_type_category')->createInstance('test_category');
    $expected = [
      'Test category',
      'This is a test field type category.',
      -10,
      ['field_plugins_test/test_library'],
    ];

    $this->assertSame($expected, [
      (string) $category->getLabel(),
      (string) $category->getDescription(),
      $category->getWeight(),
      $category->getLibraries(),
    ]);
  }

}
