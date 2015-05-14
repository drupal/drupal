<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateMenuLinkTest
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Menu link migration.
 *
 * @group migrate_drupal
 */
class MigrateMenuLinkTest extends MigrateDrupal6TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('link', 'menu_ui', 'menu_link_content');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['router']);
    $this->installEntitySchema('menu_link_content');

    $menu = entity_create('menu', array('id' => 'secondary-links'));
    $menu->enforceIsNew(TRUE);
    $menu->save();

    $this->prepareMigrations(array(
      'd6_menu' => array(
        array(array('secondary-links'), array('secondary-links')),
      ),
    ));

    $migration = entity_load('migration', 'd6_menu_links');
    $dumps = array(
      $this->getDumpDirectory() . '/MenuLinks.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  public function testMenuLinks() {
    $menu_link = entity_load('menu_link_content', 138);
    $this->assertIdentical('Test 1', $menu_link->getTitle());
    $this->assertIdentical('secondary-links', $menu_link->getMenuName());
    $this->assertIdentical('Test menu link 1', $menu_link->getDescription());
    $this->assertIdentical(TRUE, $menu_link->isEnabled());
    $this->assertIdentical(FALSE, $menu_link->isExpanded());
    $this->assertIdentical(['attributes' => ['title' => 'Test menu link 1']], $menu_link->link->options);
    $this->assertIdentical('internal:/user/login', $menu_link->link->uri);
    $this->assertIdentical(15, $menu_link->getWeight());

    $menu_link = entity_load('menu_link_content', 139);
    $this->assertIdentical('Test 2', $menu_link->getTitle());
    $this->assertIdentical('secondary-links', $menu_link->getMenuName());
    $this->assertIdentical('Test menu link 2', $menu_link->getDescription());
    $this->assertIdentical(TRUE, $menu_link->isEnabled());
    $this->assertIdentical(TRUE, $menu_link->isExpanded());
    $this->assertIdentical(['query' => 'foo=bar', 'attributes' => ['title' => 'Test menu link 2']], $menu_link->link->options);
    $this->assertIdentical('internal:/admin', $menu_link->link->uri);
    $this->assertIdentical(12, $menu_link->getWeight());

    $menu_link = entity_load('menu_link_content', 140);
    $this->assertIdentical('Drupal.org', $menu_link->getTitle());
    $this->assertIdentical('secondary-links', $menu_link->getMenuName());
    $this->assertIdentical('', $menu_link->getDescription());
    $this->assertIdentical(TRUE, $menu_link->isEnabled());
    $this->assertIdentical(FALSE, $menu_link->isExpanded());
    $this->assertIdentical(['attributes' => ['title' => '']], $menu_link->link->options);
    $this->assertIdentical('http://drupal.org', $menu_link->link->uri);
    $this->assertIdentical(0, $menu_link->getWeight());
  }

}
