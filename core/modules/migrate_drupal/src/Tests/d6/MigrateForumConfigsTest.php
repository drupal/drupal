<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateForumConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade variables to forum.settings.yml.
 *
 * @group migrate_drupal
 */
class MigrateForumConfigsTest extends MigrateDrupalTestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('forum');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->prepareMigrations(array(
      'd6_taxonomy_vocabulary' => array(
        array(array(1), array('vocabulary_1_i_0_')),
      )
    ));
    $migration = entity_load('migration', 'd6_forum_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of forum variables to forum.settings.yml.
   */
  public function testForumSettings() {
    $config = $this->config('forum.settings');
    $this->assertIdentical($config->get('topics.hot_threshold'), 15);
    $this->assertIdentical($config->get('topics.page_limit'), 25);
    $this->assertIdentical($config->get('topics.order'), 1);
    $this->assertIdentical($config->get('vocabulary'), 'vocabulary_1_i_0_');
    // This is 'forum_block_num_0' in D6, but block:active:limit' in D8.
    $this->assertIdentical($config->get('block.active.limit'), 5);
    // This is 'forum_block_num_1' in D6, but 'block:new:limit' in D8.
    $this->assertIdentical($config->get('block.new.limit'), 5);
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'forum.settings', $config->get());
  }

}
