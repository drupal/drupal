<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\views\Entity\View;

/**
 * Tests validation of view entities.
 *
 * @group views
 */
class ViewValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views', 'views_test_config'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = View::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $this->entity->save();
  }

  /**
   * @group legacy
   */
  public function testLabelsAreRequired(): void {
    $this->entity->set('label', NULL);
    $this->expectDeprecation('Saving a view without an explicit label is deprecated in drupal:10.2.0 and will raise an error in drupal:11.0.0. See https://www.drupal.org/node/3381669');
    $this->assertSame($this->entity->id(), $this->entity->label());
  }

  /**
   * Tests that the various plugin IDs making up a view display are validated.
   *
   * @param string ...$parents
   *   The array parents of the property of the view's default display which
   *   will be set to `non_existent`.
   *
   * @testWith ["display_plugin"]
   *   ["display_options", "pager", "type"]
   *   ["display_options", "exposed_form", "type"]
   *   ["display_options", "access", "type"]
   *   ["display_options", "style", "type"]
   *   ["display_options", "row", "type"]
   *   ["display_options", "query", "type"]
   *   ["display_options", "cache", "type"]
   *   ["display_options", "header", "non_existent", "plugin_id"]
   *   ["display_options", "footer", "non_existent", "plugin_id"]
   *   ["display_options", "empty", "non_existent", "plugin_id"]
   *   ["display_options", "arguments", "non_existent", "plugin_id"]
   *   ["display_options", "sorts", "non_existent", "plugin_id"]
   *   ["display_options", "fields", "non_existent", "plugin_id"]
   *   ["display_options", "filters", "non_existent", "plugin_id"]
   *   ["display_options", "relationships", "non_existent", "plugin_id"]
   */
  public function testInvalidPluginId(string ...$parents): void {
    // Disable the `broken` handler plugin, which is used as a fallback for
    // non-existent handler plugins. This ensures that when we use an
    // invalid handler plugin ID, we will get the expected validation error.
    // @todo Remove all this when fallback plugin IDs are not longer allowed by
    //   Views' config schema.
    // @see views_test_config.module
    $this->container->get('state')
      ->set('views_test_config_disable_broken_handler', [
        'area',
        'argument',
        'sort',
        'field',
        'filter',
        'relationship',
      ]);
    $this->container->get('plugin.cache_clearer')->clearCachedDefinitions();

    $display = &$this->entity->getDisplay('default');
    NestedArray::setValue($display, $parents, 'non_existent');
    $property_path = 'display.default.' . implode('.', $parents);
    $this->assertValidationErrors([
      $property_path => "The 'non_existent' plugin does not exist.",
    ]);
  }

}
