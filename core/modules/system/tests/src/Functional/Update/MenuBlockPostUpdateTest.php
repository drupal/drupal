<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\block\Entity\Block;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests updating menu blocks configuration.
 *
 * @group Update
 * @group legacy
 */
class MenuBlockPostUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      // Use a more recent fixture for performance, do not run all pre-8.4
      // updates when testing this feature.
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests updating blocks with default 'expand_all_items' value.
   *
   * @see system_post_update_add_expand_all_items_key_in_system_menu_block()
   */
  public function testPostUpdateMenuBlockFields() {
    $this->assertArrayNotHasKey('expand_all_items', Block::load('bartik_account_menu')->get('settings'));
    $this->runUpdates();
    $settings = Block::load('bartik_account_menu')->get('settings');
    $this->assertArrayHasKey('expand_all_items', $settings);
    $this->assertFalse($settings['expand_all_items']);
  }

}
