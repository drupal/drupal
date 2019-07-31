<?php

namespace Drupal\Tests\search\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests search blocks upgrade to default page setting.
 *
 * @group Update
 * @group legacy
 */
class BlockPageSettingTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Tests existing search block settings upgrade.
   *
   * @see search_post_update_block_page()
   */
  public function testUpdateActionPlugins() {
    $config = \Drupal::configFactory()->get('block.block.bartik_search');
    $this->assertArrayNotHasKey('page_id', $config->get('settings'));

    $this->runUpdates();

    $config = \Drupal::configFactory()->get('block.block.bartik_search');
    $this->assertSame('', $config->get('settings')['page_id']);
  }

}
