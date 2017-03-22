<?php

namespace Drupal\Tests\views\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\image\Entity\ImageStyle;
use Drupal\views\Entity\View;

/**
 * Tests integration of views with other modules.
 *
 * @group views
 */
class ViewsConfigDependenciesIntegrationTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'file', 'image', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['entity_test_fields'];

  /**
   * Tests integration with image module.
   */
  public function testImage() {
    /** @var \Drupal\image\ImageStyleInterface $style */
    $style = ImageStyle::create(['name' => 'foo']);
    $style->save();

    // Create a new image field 'bar' to be used in 'entity_test_fields' view.
    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'bar',
      'type' => 'image',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'bar',
    ])->save();

    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load('entity_test_fields');
    $display =& $view->getDisplay('default');

    // Add the 'bar' image field to 'entity_test_fields' view.
    $display['display_options']['fields']['bar'] = [
      'id' => 'bar',
      'field' => 'bar',
      'plugin_id' => 'field',
      'table' => 'entity_test__bar',
      'entity_type' => 'entity_test',
      'entity_field' => 'bar',
      'type' => 'image',
      'settings' => ['image_style' => 'foo', 'image_link' => ''],
    ];
    $view->save();

    $dependencies = $view->getDependencies() + ['config' => []];

    // Checks that style 'foo' is a dependency of view 'entity_test_fields'.
    $this->assertTrue(in_array('image.style.foo', $dependencies['config']));

    // Delete the 'foo' image style.
    $style->delete();

    // Checks that the view has been deleted too.
    $this->assertNull(View::load('entity_test_fields'));
  }

}
