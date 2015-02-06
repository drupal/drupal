<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateBlockTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;
use Drupal\block\Entity\Block;

/**
 * Upgrade block settings to block.block.*.yml.
 *
 * @group migrate_drupal
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
    'block_content',
    'node',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $entities = array(
      entity_create('menu', array('id' => 'primary-links')),
      entity_create('menu', array('id' => 'secondary-links')),
      entity_create('block_content', array('id' => 1, 'type' => 'basic', 'info' => $this->randomMachineName(8))),
      entity_create('block_content', array('id' => 2, 'type' => 'basic', 'info' => $this->randomMachineName(8))),
    );
    foreach ($entities as $entity) {
      $entity->enforceIsNew(TRUE);
      $entity->save();
    }
    $this->prepareMigrations(array(
      'd6_custom_block'  => array(
        array(array(1), array(1)),
        array(array(2), array(2)),
      ),
      'd6_menu' => array(
        array(array('menu1'), array('menu')),
      ),
      'd6_user_role' => array(
        array(array(2), array('authenticated')),
        array(array(3), array('migrate_test_role_1')),
      ),
    ));

    // Set Bartik and Seven as the default public and admin theme.
    $config = $this->config('system.theme');
    $config->set('default', 'bartik');
    $config->set('admin', 'seven');
    $config->save();

    // Install one of D8's test themes.
    \Drupal::service('theme_handler')->install(array('test_theme'));

    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_block');
    $dumps = array(
      $this->getDumpDirectory() . '/Blocks.php',
      $this->getDumpDirectory() . '/BlocksRoles.php',
      $this->getDumpDirectory() . '/AggregatorFeed.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Test the block settings migration.
   */
  public function testBlockMigration() {
    $blocks = Block::loadMultiple();
    $this->assertIdentical(count($blocks), 10);

    // User blocks
    $test_block_user = $blocks['user'];
    $this->assertNotNull($test_block_user);
    $this->assertIdentical('sidebar_first', $test_block_user->getRegion());
    $this->assertIdentical('bartik', $test_block_user->getTheme());
    $visibility = $test_block_user->getVisibility();
    $this->assertTrue(empty($visibility));
    $this->assertIdentical(0, $test_block_user->getWeight());

    $test_block_user_1 = $blocks['user_1'];
    $this->assertNotNull($test_block_user_1);
    $this->assertIdentical('sidebar_first', $test_block_user_1->getRegion());
    $this->assertIdentical('bartik', $test_block_user_1->getTheme());
    $visibility = $test_block_user_1->getVisibility();
    $this->assertTrue(empty($visibility));
    $this->assertIdentical(0, $test_block_user_1->getWeight());

    $test_block_user_2 = $blocks['user_2'];
    $this->assertNotNull($test_block_user_2);
    $this->assertIdentical('sidebar_second', $test_block_user_2->getRegion());
    $this->assertIdentical('bartik', $test_block_user_2->getTheme());
    $visibility = $test_block_user_2->getVisibility();
    $this->assertIdentical($visibility['user_role']['id'], 'user_role');
    $roles = array();
    $roles['authenticated'] = 'authenticated';
    $this->assertIdentical($visibility['user_role']['roles'], $roles);
    $this->assertFalse($visibility['user_role']['negate']);
    $this->assertIdentical(-9, $test_block_user_2->getWeight());

    $test_block_user_3 = $blocks['user_3'];
    $this->assertNotNull($test_block_user_3);
    $this->assertIdentical('sidebar_second', $test_block_user_3->getRegion());
    $this->assertIdentical('bartik', $test_block_user_3->getTheme());
    $visibility = $test_block_user_3->getVisibility();
    $this->assertIdentical($visibility['user_role']['id'], 'user_role');
    $roles = array();
    $roles['migrate_test_role_1'] = 'migrate_test_role_1';
    $this->assertIdentical($visibility['user_role']['roles'], $roles);
    $this->assertFalse($visibility['user_role']['negate']);
    $this->assertIdentical(-6, $test_block_user_3->getWeight());

    // Check system block
    $test_block_system = $blocks['system'];
    $this->assertNotNull($test_block_system);
    $this->assertIdentical('footer', $test_block_system->getRegion());
    $this->assertIdentical('bartik', $test_block_system->getTheme());
    $visibility = $test_block_system->getVisibility();
    $this->assertIdentical('request_path', $visibility['request_path']['id']);
    $this->assertIdentical('node/1', $visibility['request_path']['pages']);
    $this->assertTrue($visibility['request_path']['negate']);
    $this->assertIdentical(-5, $test_block_system->getWeight());

    // Check menu blocks
    $test_block_menu = $blocks['menu'];
    $this->assertNotNull($test_block_menu);
    $this->assertIdentical('header', $test_block_menu->getRegion());
    $this->assertIdentical('bartik', $test_block_menu->getTheme());
    $visibility = $test_block_menu->getVisibility();
    $this->assertTrue(empty($visibility));
    $this->assertIdentical(-5, $test_block_menu->getWeight());

    // Check custom blocks
    $test_block_block = $blocks['block'];
    $this->assertNotNull($test_block_block);
    $this->assertIdentical('content', $test_block_block->getRegion());
    $this->assertIdentical('bartik', $test_block_block->getTheme());
    $visibility = $test_block_block->getVisibility();
    $this->assertIdentical('request_path', $visibility['request_path']['id']);
    $this->assertIdentical('<front>', $visibility['request_path']['pages']);
    $this->assertFalse($visibility['request_path']['negate']);
    $this->assertIdentical(0, $test_block_block->getWeight());

    $test_block_block_1 = $blocks['block_1'];
    $this->assertNotNull($test_block_block_1);
    $this->assertIdentical('right', $test_block_block_1->getRegion());
    $this->assertIdentical('bluemarine', $test_block_block_1->getTheme());
    $visibility = $test_block_block_1->getVisibility();
    $this->assertIdentical('request_path', $visibility['request_path']['id']);
    $this->assertIdentical('node', $visibility['request_path']['pages']);
    $this->assertFalse($visibility['request_path']['negate']);
    $this->assertIdentical(-4, $test_block_block_1->getWeight());

    $test_block_block_2 = $blocks['block_2'];
    $this->assertNotNull($test_block_block_2);
    $this->assertIdentical('right', $test_block_block_2->getRegion());
    $this->assertIdentical('test_theme', $test_block_block_2->getTheme());
    $visibility = $test_block_block_2->getVisibility();
    $this->assertTrue(empty($visibility));
    $this->assertIdentical(-7, $test_block_block_2->getWeight());

    $test_block_block_3 = $blocks['block_3'];
    $this->assertNotNull($test_block_block_3);
    $this->assertIdentical('left', $test_block_block_3->getRegion());
    $this->assertIdentical('test_theme', $test_block_block_3->getTheme());
    $visibility = $test_block_block_3->getVisibility();
    $this->assertTrue(empty($visibility));
    $this->assertIdentical(-2, $test_block_block_3->getWeight());
  }
}
