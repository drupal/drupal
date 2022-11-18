<?php

namespace Drupal\Tests\views\Kernel;

use Drupal\Core\Config\FileStorage;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\Tests\responsive_image\Functional\ViewsIntegrationTest;
use Drupal\views\ViewsConfigUpdater;

/**
 * @coversDefaultClass \Drupal\views\ViewsConfigUpdater
 *
 * @group Views
 * @group legacy
 */
class ViewsConfigUpdaterTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views_config_entity_test',
    'entity_test',
    'breakpoint',
    'field',
    'file',
    'image',
    'responsive_image',
    'responsive_image_test_module',
  ];

  /**
   * @covers ::needsResponsiveImageLazyLoadFieldUpdate
   */
  public function testNeedsResponsiveImageLazyLoadFieldUpdate(): void {
    $config_updater = $this->container
      ->get('class_resolver')
      ->getInstanceFromDefinition(ViewsConfigUpdater::class);
    assert($config_updater instanceof ViewsConfigUpdater);

    FieldStorageConfig::create([
      'field_name' => 'user_picture',
      'entity_type' => 'user',
      'type' => 'image',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'user',
      'field_name' => 'user_picture',
      'file_directory' => 'pictures/[date:custom:Y]-[date:custom:m]',
      'bundle' => 'user',
    ])->save();

    // Create a responsive image style.
    ResponsiveImageStyle::create([
      'id' => ViewsIntegrationTest::RESPONSIVE_IMAGE_STYLE_ID,
      'label' => 'Foo',
      'breakpoint_group' => 'responsive_image_test_module',
    ]);
    // Create an image field to be used with a responsive image formatter.
    FieldStorageConfig::create([
      'type' => 'image',
      'entity_type' => 'entity_test',
      'field_name' => 'bar',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'bar',
    ])->save();

    $test_view = $this->loadTestView('views.view.test_responsive_images');
    $needs_update = $config_updater->needsResponsiveImageLazyLoadFieldUpdate($test_view);
    $test_view->save();
    $this->assertTrue($needs_update);

    $default_display = $test_view->getDisplay('default');
    self::assertEquals('eager', $default_display['display_options']['fields']['bar']['settings']['image_loading']['attribute']);
  }

  /**
   * Loads a test view.
   *
   * @param string $view_id
   *   The view config ID.
   *
   * @return \Drupal\views\ViewEntityInterface
   *   A view entity object.
   */
  protected function loadTestView($view_id) {
    // We just instantiate the test view from the raw configuration, as it may
    // not be possible to save it, due to its faulty schema.
    $config_dir = $this->getModulePath('views') . '/tests/fixtures/update';
    $file_storage = new FileStorage($config_dir);
    $values = $file_storage->read($view_id);
    /** @var \Drupal\views\ViewEntityInterface $test_view */
    $test_view = $this->container
      ->get('entity_type.manager')
      ->getStorage('view')
      ->create($values);
    return $test_view;
  }

}
