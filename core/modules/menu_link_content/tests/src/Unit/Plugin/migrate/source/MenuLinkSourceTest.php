<?php

/**
 * @file
 * Contains \Drupal\Tests\menu_link_content\Unit\Plugin\migrate\source\MenuLinkSourceTest.
 */
namespace Drupal\Tests\menu_link_content\Unit\Plugin\migrate\source;

use Drupal\Component\Utility\Unicode;
use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests menu link source plugin.
 *
 * @group menu_link_content
 */
class MenuLinkSourceTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\menu_link_content\Plugin\migrate\source\MenuLink';

  protected $migrationConfiguration = array(
    'id' => 'mlid',
    'source' => array(
      'plugin' => 'menu_link',
    ),
  );

  protected $expectedResults = array(
    array(
      // Customized menu link, provided by system module.
      'menu_name' => 'menu-test-menu',
      'mlid' => 140,
      'plid' => 0,
      'link_path' => 'admin/config/system/cron',
      'router_path' => 'admin/config/system/cron',
      'link_title' => 'Cron',
      'options' => array(),
      'module' => 'system',
      'hidden' => 0,
      'external' => 0,
      'has_children' => 0,
      'expanded' => 0,
      'weight' => 0,
      'depth' => 0,
      'customized' => 1,
      'p1' => '0',
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
      // D6 customized menu link, provided by menu module.
      'menu_name' => 'menu-test-menu',
      'mlid' => 141,
      'plid' => 0,
      'link_path' => 'node/141',
      'router_path' => 'node/%',
      'link_title' => 'Node 141',
      'options' => array(),
      'module' => 'menu',
      'hidden' => 0,
      'external' => 0,
      'has_children' => 0,
      'expanded' => 0,
      'weight' => 0,
      'depth' => 0,
      'customized' => 1,
      'p1' => '0',
      'p2' => '0',
      'p3' => '0',
      'p4' => '0',
      'p5' => '0',
      'p6' => '0',
      'p7' => '0',
      'p8' => '0',
      'p9' => '0',
      'updated' => '0',
      'description' => '',
    ),
    array(
      // D6 non-customized menu link, provided by menu module.
      'menu_name' => 'menu-test-menu',
      'mlid' => 142,
      'plid' => 0,
      'link_path' => 'node/142',
      'router_path' => 'node/%',
      'link_title' => 'Node 142',
      'options' => array(),
      'module' => 'menu',
      'hidden' => 0,
      'external' => 0,
      'has_children' => 0,
      'expanded' => 0,
      'weight' => 0,
      'depth' => 0,
      'customized' => 0,
      'p1' => '0',
      'p2' => '0',
      'p3' => '0',
      'p4' => '0',
      'p5' => '0',
      'p6' => '0',
      'p7' => '0',
      'p8' => '0',
      'p9' => '0',
      'updated' => '0',
      'description' => '',
    ),
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
      'description' => 'Test menu link 1',
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
      'description' => 'Test menu link 2',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['menu_links'] = $this->expectedResults;

    // Add long link title attributes.
    $title = $this->getRandomGenerator()->string('500');
    $this->databaseContents['menu_links'][0]['options']['attributes']['title'] = $title;
    $this->expectedResults[0]['description'] = Unicode::truncate($title, 255);

    // D6 menu link to a custom menu, provided by menu module.
    $this->databaseContents['menu_links'][] = [
      'menu_name' => 'menu-user',
      'mlid' => 143,
      'plid' => 0,
      'link_path' => 'admin/build/menu-customize/navigation',
      'router_path' => 'admin/build/menu-customize/%',
      'link_title' => 'Navigation',
      'options' => array(),
      'module' => 'menu',
      'hidden' => 0,
      'external' => 0,
      'has_children' => 0,
      'expanded' => 0,
      'weight' => 0,
      'depth' => 0,
      'customized' => 0,
      'p1' => '0',
      'p2' => '0',
      'p3' => '0',
      'p4' => '0',
      'p5' => '0',
      'p6' => '0',
      'p7' => '0',
      'p8' => '0',
      'p9' => '0',
      'updated' => '0',
      'description' => '',
    ];

    array_walk($this->databaseContents['menu_links'], function (&$row) {
      $row['options'] = serialize($row['options']);
    });

    parent::setUp();
  }

}
