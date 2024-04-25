<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\Core\Config\FileStorage;

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
    'field',
  ];

  /**
   * Tests ViewsConfigUpdater.
   */
  public function testViewsConfigUpdater(): void {
    // ViewsConfigUpdater currently contains no actual configuration update
    // logic. Replace this method with a real test when it does.
    $this->markTestSkipped();
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
