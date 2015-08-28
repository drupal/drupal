<?php

/**
 * @file
 * Contains \Drupal\Tests\menu_link_content\Unit\Plugin\migrate\source\d6\MenuLinkSourceTest.
 */

namespace Drupal\Tests\menu_link_content\Unit\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 menu link source plugin.
 *
 * @group menu_link_content
 */
class MenuLinkSourceTest extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\menu_link_content\Plugin\migrate\source\d6\MenuLink';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = array(
    // The ID of the entity, can be any string.
    'id' => 'mlid',
    // This needs to be the identifier of the actual key: cid for comment, nid
    // for node and so on.
    'source' => array(
      'plugin' => 'drupal6_menu_link',
    ),
    'sourceIds' => array(
      'mlid' => array(
        // This is where the field schema would go but for now we need to
        // specify the table alias for the key. Most likely this will be the
        // same as BASE_ALIAS.
        'alias' => 'ml',
      ),
    ),
    'destinationIds' => array(
      'mlid' => array(
        // This is where the field schema would go.
      ),
    ),
  );

  protected $expectedResults = array(
    array(
      'menu_name' => 'menu-test-menu',
      'mlid' => 138,
      'plid' => 0,
      'link_path' => 'admin',
      'router_path' => 'admin',
      'link_title' => 'Test 1',
      'options' => array('attributes' => array('title' => 'Test menu link 1')),
      'module' => 'menu',
      'hidden' => 0,
      'external' => 0,
      'has_children' => 1,
      'expanded' => 0,
      'weight' => 15,
      'depth' => 1,
      'customized' => 1,
      'p1' => '138',
      'p2' => '0',
      'p3' => '0',
      'p4' => '0',
      'p5' => '0',
      'p6' => '0',
      'p7' => '0',
      'p8' => '0',
      'p9' => '0',
      'updated' => '0',
    ),
    array(
      'menu_name' => 'menu-test-menu',
      'mlid' => 139,
      'plid' => 138,
      'link_path' => 'admin/modules',
      'router_path' => 'admin/modules',
      'link_title' => 'Test 2',
      'options' => array('attributes' => array('title' => 'Test menu link 2')),
      'module' => 'menu',
      'hidden' => 0,
      'external' => 0,
      'has_children' => 0,
      'expanded' => 0,
      'weight' => 12,
      'depth' => 2,
      'customized' => 1,
      'p1' => '138',
      'p2' => '139',
      'p3' => '0',
      'p4' => '0',
      'p5' => '0',
      'p6' => '0',
      'p7' => '0',
      'p8' => '0',
      'p9' => '0',
      'updated' => '0',
    ),
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // This array stores the database.
    foreach ($this->expectedResults as $k => $row) {
      $this->databaseContents['menu_links'][$k] = $row;
      $this->databaseContents['menu_links'][$k]['options'] = serialize($this->databaseContents['menu_links'][$k]['options']);
    }
    parent::setUp();
  }

}
