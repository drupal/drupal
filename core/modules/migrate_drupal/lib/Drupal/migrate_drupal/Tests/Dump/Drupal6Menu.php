<?php
/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6Menu.
 */

namespace Drupal\migrate_drupal\Tests\Dump;
/**
 * Database dump for testing menu migration.
 */
class Drupal6Menu extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('menu_custom', array(
      'fields' => array(
        'menu_name' => array(
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
          'default' => '',
        ),
        'title' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'description' => array(
          'type' => 'text',
          'not null' => FALSE,
        ),
      ),
      'primary key' => array(
        'menu_name',
      ),
      'module' => 'menu',
      'name' => 'menu_custom',
    ));
    $this->database->insert('menu_custom')->fields(array('menu_name', 'title', 'description'))
      ->values(array(
        'menu_name' => 'navigation',
        'title' => 'Navigation',
        'description' => 'The navigation menu is provided by Drupal and is the main interactive menu for any site. It is usually the only menu that contains personalized links for authenticated users, and is often not even visible to anonymous users.',
      ))
      ->values(array(
        'menu_name' => 'primary-links',
        'title' => 'Primary links',
        'description' => 'Primary links are often used at the theme layer to show the major sections of a site. A typical representation for primary links would be tabs along the top.',
      ))
      ->values(array(
        'menu_name' => 'secondary-links',
        'title' => 'Secondary links',
        'description' => 'Secondary links are often used for pages like legal notices, contact details, and other secondary navigation items that play a lesser role than primary links',
      ))
      ->execute();
    $this->setModuleVersion('menu', '6001');
  }

}
