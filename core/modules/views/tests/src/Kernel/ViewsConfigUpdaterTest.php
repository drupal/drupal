<?php

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
   * Dummy test to keep this test file with the loadTestView method.
   *
   * @see https://www.drupal.org/project/drupal/issues/3261245
   * @todo Remove the dummyTest function when this class contains a real test.
   */
  public function testPass() {
    $this->assertTrue(TRUE);
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
