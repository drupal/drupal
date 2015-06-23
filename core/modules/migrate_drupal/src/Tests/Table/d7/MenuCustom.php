<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\MenuCustom.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the menu_custom table.
 */
class MenuCustom extends DrupalDumpBase {

  public function load() {
    $this->createTable("menu_custom", array(
      'primary key' => array(
        'menu_name',
      ),
      'fields' => array(
        'menu_name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
          'default' => '',
        ),
        'title' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'description' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("menu_custom")->fields(array(
      'menu_name',
      'title',
      'description',
    ))
    ->values(array(
      'menu_name' => 'main-menu',
      'title' => 'Main menu',
      'description' => 'The <em>Main</em> menu is used on many sites to show the major sections of the site, often in a top navigation bar.',
    ))->values(array(
      'menu_name' => 'management',
      'title' => 'Management',
      'description' => 'The <em>Management</em> menu contains links for administrative tasks.',
    ))->values(array(
      'menu_name' => 'menu-test-menu',
      'title' => 'Test Menu',
      'description' => 'Test menu description.',
    ))->values(array(
      'menu_name' => 'navigation',
      'title' => 'Navigation',
      'description' => 'The <em>Navigation</em> menu contains links intended for site visitors. Links are added to the <em>Navigation</em> menu automatically by some modules.',
    ))->values(array(
      'menu_name' => 'user-menu',
      'title' => 'User menu',
      'description' => "The <em>User</em> menu contains links related to the user's account, as well as the 'Log out' link.",
    ))->execute();
  }

}
#9544551cfc40d1dfb5de91c3036238e8
