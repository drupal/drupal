<?php

namespace Drupal\Tests\path\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the BC layer for the deprecated routes of the path module.
 *
 * @group legacy
 * @group path
 */
class PathLegacyRoutesKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['path', 'path_alias'];

  /**
   * @expectedDeprecation The 'path.admin_add' route is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the 'entity.path_alias.add_form' route instead. See https://www.drupal.org/node/3013865
   */
  public function testLegacyAddRoute() {
    $this->assertNotEmpty(Url::fromRoute('path.admin_add')->toString());
  }

  /**
   * @expectedDeprecation The 'path.admin_edit' route is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the 'entity.path_alias.edit_form' route instead. See https://www.drupal.org/node/3013865
   */
  public function testLegacyEditRoute() {
    $this->assertNotEmpty(Url::fromRoute('path.admin_edit', ['path_alias' => 1])->toString());
  }

  /**
   * @expectedDeprecation The 'path.delete' route is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the 'entity.path_alias.delete_form' route instead. See https://www.drupal.org/node/3013865
   */
  public function testLegacyDeleteRoute() {
    $this->assertNotEmpty(Url::fromRoute('path.delete', ['path_alias' => 1])->toString());
  }

  /**
   * @expectedDeprecation The 'path.admin_overview' route is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the 'entity.path_alias.collection' route instead. See https://www.drupal.org/node/3013865
   */
  public function testLegacyCollectionRoute() {
    $this->assertNotEmpty(Url::fromRoute('path.admin_overview')->toString());
  }

  /**
   * @expectedDeprecation The 'path.admin_overview_filter' route is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the 'entity.path_alias.collection' route instead. See https://www.drupal.org/node/3013865
   */
  public function testLegacyCollectionFilterRoute() {
    $this->assertNotEmpty(Url::fromRoute('path.admin_overview_filter')->toString());
  }

}
