<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateMenuLinkTest
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Menu link migration.
 *
 * @group migrate_drupal
 */
class MigrateMenuLinkTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('menu_ui');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

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
    $this->assertIdentical($menu_link->getTitle(), 'Test 1');
    $this->assertIdentical($menu_link->getMenuName(), 'secondary-links');
    $this->assertIdentical($menu_link->getDescription(), 'Test menu link 1');
    $this->assertIdentical($menu_link->isEnabled(), TRUE);
    $this->assertIdentical($menu_link->isExpanded(), FALSE);
    $this->assertIdentical($menu_link->link->options, ['attributes' => ['title' => 'Test menu link 1']]);
    $this->assertIdentical($menu_link->link->uri, 'user-path:user/login');
    $this->assertIdentical($menu_link->getWeight(), 15);

    $menu_link = entity_load('menu_link_content', 139);
    $this->assertIdentical($menu_link->getTitle(), 'Test 2');
    $this->assertIdentical($menu_link->getMenuName(), 'secondary-links');
    $this->assertIdentical($menu_link->getDescription(), 'Test menu link 2');
    $this->assertIdentical($menu_link->isEnabled(), TRUE);
    $this->assertIdentical($menu_link->isExpanded(), TRUE);
    $this->assertIdentical($menu_link->link->options, ['query' => 'foo=bar', 'attributes' => ['title' => 'Test menu link 2']]);
    $this->assertIdentical($menu_link->link->uri, 'user-path:admin');
    $this->assertIdentical($menu_link->getWeight(), 12);

    $menu_link = entity_load('menu_link_content', 140);
    $this->assertIdentical($menu_link->getTitle(), 'Drupal.org');
    $this->assertIdentical($menu_link->getMenuName(), 'secondary-links');
    $this->assertIdentical($menu_link->getDescription(), '');
    $this->assertIdentical($menu_link->isEnabled(), TRUE);
    $this->assertIdentical($menu_link->isExpanded(), FALSE);
    $this->assertIdentical($menu_link->link->options, ['attributes' => ['title' => '']]);
    $this->assertIdentical($menu_link->link->uri, 'http://drupal.org');
    $this->assertIdentical($menu_link->getWeight(), 0);
  }

}
