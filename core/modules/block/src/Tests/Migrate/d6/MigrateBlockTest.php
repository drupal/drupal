<?php

/**
 * @file
 * Contains \Drupal\block\Tests\Migrate\d6\MigrateBlockTest.
 */

namespace Drupal\block\Tests\Migrate\d6;

use Drupal\block\Entity\Block;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of blocks to configuration entities.
 *
 * @group migrate_drupal_6
 */
class MigrateBlockTest extends MigrateDrupal6TestBase {

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
    $this->installEntitySchema('block_content');

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
      'd6_custom_block' => array(
        array(array(1), array(1)),
        array(array(2), array(2)),
      ),
      'menu' => array(
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

    $this->executeMigration('d6_block');
  }

  /**
   * Asserts various aspects of a block.
   *
   * @param string $id
   *   The block ID.
   * @param string $module
   *   The module.
   * @param array $visibility
   *   The block visibility settings.
   * @param string $region
   *   The display region.
   * @param string $theme
   *   The theme.
   * @param string $weight
   *   The block weight.
   */
  public function assertEntity($id, $visibility, $region, $theme, $weight) {
    $block = Block::load($id);
    $this->assertTrue($block instanceof Block);
    $this->assertIdentical($visibility, $block->getVisibility());
    $this->assertIdentical($region, $block->getRegion());
    $this->assertIdentical($theme, $block->getTheme());
    $this->assertIdentical($weight, $block->getWeight());
  }

  /**
   * Tests the block migration.
   */
  public function testBlockMigration() {
    $blocks = Block::loadMultiple();
    $this->assertIdentical(9, count($blocks));

    // User blocks
    $visibility = [];
    $visibility['request_path']['id'] = 'request_path';
    $visibility['request_path']['negate'] = TRUE;
    $visibility['request_path']['pages'] = "<front>\n/node/1\n/blog/*";
    $this->assertEntity('user', $visibility, 'sidebar_first', 'bartik', 0);

    $visibility = [];
    $this->assertEntity('user_1', $visibility, 'sidebar_first', 'bartik', 0);

    $visibility['user_role']['id'] = 'user_role';
    $roles['authenticated'] = 'authenticated';
    $visibility['user_role']['roles'] = $roles;
    $context_mapping['user'] = '@user.current_user_context:current_user';
    $visibility['user_role']['context_mapping'] = $context_mapping;
    $visibility['user_role']['negate'] = FALSE;
    $this->assertEntity('user_2', $visibility, 'sidebar_second', 'bartik', -9);

    $visibility = [];
    $visibility['user_role']['id'] = 'user_role';
    $visibility['user_role']['roles'] = [
      'migrate_test_role_1' => 'migrate_test_role_1'
    ];
    $context_mapping['user'] = '@user.current_user_context:current_user';
    $visibility['user_role']['context_mapping'] = $context_mapping;
    $visibility['user_role']['negate'] = FALSE;
    $this->assertEntity('user_3', $visibility, 'sidebar_second', 'bartik', -6);

    // Check system block
    $visibility = [];
    $visibility['request_path']['id'] = 'request_path';
    $visibility['request_path']['negate'] = TRUE;
    $visibility['request_path']['pages'] = '/node/1';
    $this->assertEntity('system', $visibility, 'footer', 'bartik', -5);

    // Check menu blocks
    $visibility = [];
    $this->assertEntity('menu', $visibility, 'header', 'bartik', -5);

    // Check custom blocks
    $visibility['request_path']['id'] = 'request_path';
    $visibility['request_path']['negate'] = FALSE;
    $visibility['request_path']['pages'] = '<front>';
    $this->assertEntity('block', $visibility, 'content', 'bartik', 0);

    $visibility['request_path']['id'] = 'request_path';
    $visibility['request_path']['negate'] = FALSE;
    $visibility['request_path']['pages'] = '/node';
    $this->assertEntity('block_1', $visibility, 'right', 'bluemarine', -4);

    $visibility = [];
    $this->assertEntity('block_2', $visibility, 'right', 'test_theme', -7);

    // Custom block with php code is not migrated.
    $block = Block::load('block_3');
    $this->assertFalse($block instanceof Block);
  }

}
