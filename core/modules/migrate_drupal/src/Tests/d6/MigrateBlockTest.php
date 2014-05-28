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
      entity_create('custom_block', array('id' => 1, 'type' => 'basic', 'info' => $this->randomName(8))),
      entity_create('custom_block', array('id' => 2, 'type' => 'basic', 'info' => $this->randomName(8))),
    );
    foreach ($entities as $entity) {
      $entity->enforceIsNew(TRUE);
      $entity->save();
    }
    $this->prepareIdMappings(array(
      'd6_custom_block'  => array(
        array(array(10), array(1)),
        array(array(11), array(2)),
      )
    ));
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
    $this->assertEqual(count($blocks), 11);

    // User blocks
    $test_block_user = $blocks['user'];
    $this->assertNotNull($test_block_user);
    $this->assertEqual('left', $test_block_user->get('region'));
    $this->assertEqual('garland', $test_block_user->get('theme'));
    $visibility = $test_block_user->get('visibility');
    $this->assertEqual(0, $visibility['path']['visibility']);
    $this->assertEqual('', $visibility['path']['pages']);
    $this->assertEqual(0, $test_block_user->weight);

    $test_block_user_1 = $blocks['user_1'];
    $this->assertNotNull($test_block_user_1);
    $this->assertEqual('left', $test_block_user_1->get('region'));
    $this->assertEqual('garland', $test_block_user_1->get('theme'));
    $visibility = $test_block_user_1->get('visibility');
    $this->assertEqual(0, $visibility['path']['visibility']);
    $this->assertEqual('', $visibility['path']['pages']);
    $this->assertEqual(0, $test_block_user_1->weight);

    $test_block_user_2 = $blocks['user_2'];
    $this->assertNotNull($test_block_user_2);
    $this->assertEqual('', $test_block_user_2->get('region'));
    $this->assertEqual('garland', $test_block_user_2->get('theme'));
    $visibility = $test_block_user_2->get('visibility');
    $this->assertEqual(0, $visibility['path']['visibility']);
    $this->assertEqual('', $visibility['path']['pages']);
    $this->assertEqual(-3, $test_block_user_2->weight);

    $test_block_user_3 = $blocks['user_3'];
    $this->assertNotNull($test_block_user_3);
    $this->assertEqual('', $test_block_user_3->get('region'));
    $this->assertEqual('garland', $test_block_user_3->get('theme'));
    $visibility = $test_block_user_3->get('visibility');
    $this->assertEqual(0, $visibility['path']['visibility']);
    $this->assertEqual('', $visibility['path']['pages']);
    $this->assertEqual(-1, $test_block_user_3->weight);

    // Check system block
    $test_block_system = $blocks['system'];
    $this->assertNotNull($test_block_system);
    $this->assertEqual('footer', $test_block_system->get('region'));
    $this->assertEqual('garland', $test_block_system->get('theme'));
    $visibility = $test_block_system->get('visibility');
    $this->assertEqual(0, $visibility['path']['visibility']);
    $this->assertEqual('', $visibility['path']['pages']);
    $this->assertEqual(-5, $test_block_system->weight);

    // Check comment block
    $test_block_comment = $blocks['comment'];
    $this->assertNotNull($test_block_comment);
    $this->assertEqual('', $test_block_comment->get('region'));
    $this->assertEqual('garland', $test_block_comment->get('theme'));
    $visibility = $test_block_comment->get('visibility');
    $this->assertEqual(0, $visibility['path']['visibility']);
    $this->assertEqual('', $visibility['path']['pages']);
    $this->assertEqual(-6, $test_block_comment->weight);

    // Check menu blocks
    $test_block_menu = $blocks['menu'];
    $this->assertNotNull($test_block_menu);
    $this->assertEqual('header', $test_block_menu->get('region'));
    $this->assertEqual('garland', $test_block_menu->get('theme'));
    $visibility = $test_block_menu->get('visibility');
    $this->assertEqual(0, $visibility['path']['visibility']);
    $this->assertEqual('', $visibility['path']['pages']);
    $this->assertEqual(-5, $test_block_menu->weight);

    $test_block_menu_1 = $blocks['menu_1'];
    $this->assertNotNull($test_block_menu_1);
    $this->assertEqual('', $test_block_menu_1->get('region'));
    $this->assertEqual('garland', $test_block_menu_1->get('theme'));
    $visibility = $test_block_menu_1->get('visibility');
    $this->assertEqual(0, $visibility['path']['visibility']);
    $this->assertEqual('', $visibility['path']['pages']);
    $this->assertEqual(-5, $test_block_menu_1->weight);

    // Check node block
    $test_block_node = $blocks['node'];
    $this->assertNotNull($test_block_node);
    $this->assertEqual('', $test_block_node->get('region'));
    $this->assertEqual('garland', $test_block_node->get('theme'));
    $visibility = $test_block_node->get('visibility');
    $this->assertEqual(0, $visibility['path']['visibility']);
    $this->assertEqual('', $visibility['path']['pages']);
    $this->assertEqual(-4, $test_block_node->weight);

    // Check custom blocks
    $test_block_block = $blocks['block'];
    $this->assertNotNull($test_block_block);
    $this->assertEqual('content', $test_block_block->get('region'));
    $this->assertEqual('garland', $test_block_block->get('theme'));
    $visibility = $test_block_block->get('visibility');
    $this->assertEqual(1, $visibility['path']['visibility']);
    $this->assertEqual('<front>', $visibility['path']['pages']);
    $this->assertEqual(0, $test_block_block->weight);

    $test_block_block_1 = $blocks['block_1'];
    $this->assertNotNull($test_block_block_1);
    $this->assertEqual('right', $test_block_block_1->get('region'));
    $this->assertEqual('bluemarine', $test_block_block_1->get('theme'));
    $visibility = $test_block_block_1->get('visibility');
    $this->assertEqual(1, $visibility['path']['visibility']);
    $this->assertEqual('node', $visibility['path']['pages']);
    $this->assertEqual(-4, $test_block_block_1->weight);
  }
}
