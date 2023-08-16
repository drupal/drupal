<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the removal of the default_argument_skip_url setting.
 *
 * @see views_post_update_remove_default_argument_skip_url()
 *
 * @group Update
 * @group legacy
 */
class ViewsRemoveDefaultArgumentSkipUrlTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'taxonomy', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/remove_default_argument_skip_url.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installModulesFromClassProperty($this->container);
  }

  /**
   * Tests the upgrade path removing default_argument_skip_url.
   */
  public function testViewsPostUpdateFixRevisionId() {
    $view = View::load('remove_default_argument_skip_url');
    $data = $view->toArray();
    $this->assertArrayHasKey('default_argument_skip_url', $data['display']['default']['display_options']['arguments']['tid']);

    $this->runUpdates();

    $view = View::load('remove_default_argument_skip_url');
    $data = $view->toArray();

    $this->assertArrayNotHasKey('default_argument_skip_url', $data['display']['default']['display_options']['arguments']['tid']);
  }

}
