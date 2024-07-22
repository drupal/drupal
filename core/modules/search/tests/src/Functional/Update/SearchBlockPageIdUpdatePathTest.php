<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Functional\Update;

use Drupal\block\Entity\Block;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update path for the search block's `page_id` setting from '' to NULL.
 *
 * @group search
 */
class SearchBlockPageIdUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests update path for the search block's `page_id` setting from '' to NULL.
   */
  public function testRunUpdates() {
    $this->assertSame('', Block::load('olivero_search_form_narrow')->get('settings')['page_id']);
    $this->assertSame('', Block::load('olivero_search_form_wide')->get('settings')['page_id']);

    $this->runUpdates();

    $this->assertNull(Block::load('olivero_search_form_narrow')->get('settings')['page_id']);
    $this->assertNull(Block::load('olivero_search_form_wide')->get('settings')['page_id']);
  }

}
