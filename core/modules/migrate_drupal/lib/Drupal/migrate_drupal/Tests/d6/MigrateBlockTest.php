<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateBlockTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Test the block settings migration.
 */
class MigrateBlockTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  static $modules = array(
    'block',
    'views',
    'comment',
    'menu_ui',
    'custom_block',
    'node',
  );

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate block settings to block.block.*.yml',
      'description'  => 'Upgrade block settings to block.block.*.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $entities = array(
      entity_create('menu', array('id' => 'primary-links')),
      entity_create('menu', array('id' => 'secondary-links')),
      entity_create('custom_block', array('id' => 1, 'type' => 'basic')),
    );
    foreach ($entities as $entity) {
      $entity->enforceIsNew(TRUE);
      $entity->save();
    }
    $this->prepareIdMappings(array('d6_custom_block'  => array(array(array(1), array(1)))));
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_block');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6Block.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Test the block settings migration.
   */
  public function testBlockMigration() {
    $blocks = entity_load_multiple('block');
    $this->assertTrue(count($blocks));
    // @TODO add more asserts.
  }
}
