<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests pager-related APIs.
 *
 * @group views
 */
class PagerKernelTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_pager_full'];

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
  }

  /**
   * Tests pager-related setter methods on ViewExecutable.
   *
   * @see \Drupal\views\ViewExecutable::setItemsPerPage
   * @see \Drupal\views\ViewExecutable::setOffset
   * @see \Drupal\views\ViewExecutable::setCurrentPage
   */
  public function testSetPagerMethods() {
    $view = Views::getView('test_pager_full');

    // Mark the view as cacheable in order have the cache checking working
    // below.
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['cache']['type'] = 'tag';
    $view->storage->save();

    $output = $view->preview();

    \Drupal::service('renderer')->renderPlain($output);
    $this->assertIdentical(CacheBackendInterface::CACHE_PERMANENT, $output['#cache']['max-age']);

    foreach (['setItemsPerPage', 'setOffset', 'setCurrentPage'] as $method) {
      $view = Views::getView('test_pager_full');
      $view->setDisplay('default');
      $view->{$method}(1);
      $output = $view->preview();

      \Drupal::service('renderer')->renderPlain($output);
      $this->assertIdentical(CacheBackendInterface::CACHE_PERMANENT, $output['#cache']['max-age'], 'Max age kept.');
    }

  }

}
