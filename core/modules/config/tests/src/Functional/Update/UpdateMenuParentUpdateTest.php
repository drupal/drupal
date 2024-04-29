<?php

declare(strict_types=1);

namespace Drupal\Tests\config\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update of core.menu.static_menu_link_overrides:definitions.*.parent.
 *
 * @group config
 * @covers \config_post_update_set_menu_parent_value_to_null
 */
class UpdateMenuParentUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests update of core.menu.static_menu_link_overrides:definitions.*.parent.
   */
  public function testUpdate(): void {
    $this->assertNotNull($this->config('core.menu.static_menu_link_overrides')->get('definitions.contact__site_page.parent'));

    $this->runUpdates();

    $this->assertNull($this->config('core.menu.static_menu_link_overrides')->get('definitions.contact__site_page.parent'));
  }

}
