<?php

namespace Drupal\Tests\views\Kernel;

use Drupal\Core\Config\FileStorage;
use Drupal\views\ViewsConfigUpdater;

/**
 * @coversDefaultClass \Drupal\views\ViewsConfigUpdater
 *
 * @group Views
 */
class ViewsConfigUpdaterTest extends ViewsKernelTestBase {

  /**
   * The views config updater.
   *
   * @var \Drupal\views\ViewsConfigUpdater
   */
  protected $configUpdater;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp();

    $this->configUpdater = $this->container
      ->get('class_resolver')
      ->getInstanceFromDefinition(ViewsConfigUpdater::class);
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
    $config_dir = drupal_get_path('module', 'views') . '/tests/fixtures/update';
    $file_storage = new FileStorage($config_dir);
    $values = $file_storage->read($view_id);
    /** @var \Drupal\views\ViewEntityInterface $test_view */
    $test_view = $this->container
      ->get('entity_type.manager')
      ->getStorage('view')
      ->create($values);
    return $test_view;
  }

  /**
   * @covers ::needsEntityLinkUrlUpdate
   */
  public function testNeedsEntityLinkUrlUpdate() {
    $test_view = $this->loadTestView('views.view.node_link_update_test');
    $needs_update = $this->configUpdater->needsEntityLinkUrlUpdate($test_view);
    $this->assertTrue($needs_update);
  }

  /**
   * @covers ::needsOperatorDefaultsUpdate
   */
  public function testNeedsOperatorUpdateDefaults() {
    $test_view = $this->loadTestView('views.view.test_exposed_filters');
    $needs_update = $this->configUpdater->needsOperatorDefaultsUpdate($test_view);
    $this->assertTrue($needs_update);
  }

  /**
   * @covers ::needsMultivalueBaseFieldUpdate
   */
  public function testNeedsFieldNamesForMultivalueBaseFieldsUpdate() {
    $test_view = $this->loadTestView('views.view.test_user_multi_value');
    $needs_update = $this->configUpdater->needsMultivalueBaseFieldUpdate($test_view);
    $this->assertTrue($needs_update);
  }

  /**
   * @covers ::updateAll
   */
  public function testUpdateAll() {
    $view_ids = [
      'views.view.node_link_update_test',
      'views.view.test_exposed_filters',
      'views.view.test_user_multi_value',
    ];

    foreach ($view_ids as $view_id) {
      $test_view = $this->loadTestView($view_id);
      $this->configUpdater->updateAll($test_view);
    }

    // @todo Improve this in https://www.drupal.org/node/3121008.
    $this->pass('Views processed');
  }

}
