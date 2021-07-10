<?php

namespace Drupal\Tests\forum\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for the forum module.
 *
 * @group forum
 * @group Update
 * @group legacy
 */
class ForumUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.0.0.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/forum-block-properties-2251789.php',
    ];
  }

  /**
   * Tests the removal of the unused 'properties' key.
   *
   * @see forum_post_update_remove_properties_key()
   */
  public function testRemovedPropertiesKey() {
    $config = $this->config('block.block.activeforumtopics');
    $this->assertArrayHasKey('properties', $config->get('settings'));
    $config = $this->config('block.block.newforumtopics');
    $this->assertArrayHasKey('properties', $config->get('settings'));

    $this->runUpdates();

    $config = $this->config('block.block.activeforumtopics');
    $this->assertArrayNotHasKey('properties', $config->get('settings'));
    $config = $this->config('block.block.newforumtopics');
    $this->assertArrayNotHasKey('properties', $config->get('settings'));
  }

}
