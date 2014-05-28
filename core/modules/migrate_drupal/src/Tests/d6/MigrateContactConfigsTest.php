<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateContactConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of variables from the Contact module.
 */
class MigrateContactConfigsTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('contact');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate variables to contact.settings',
      'description'  => 'Upgrade variables to contact.settings.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Add some id mappings for the dependent migrations.
    $id_mappings = array(
      'd6_contact_category' => array(
        array(array(1), array('website_feedback')),
        array(array(2), array('some_other_category')),
      ),
    );
    $this->prepareIdMappings($id_mappings);
    $migration = entity_load('migration', 'd6_contact_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6ContactSettings.php',
      $this->getDumpDirectory() . '/Drupal6ContactCategory.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests migration of contact variables to contact.settings.yml.
   */
  public function testContactSettings() {
    $config = \Drupal::config('contact.settings');
    $this->assertIdentical($config->get('user_default_enabled'), true);
    $this->assertIdentical($config->get('flood.limit'), 3);
    $this->assertIdentical($config->get('default_category'), 'some_other_category');
  }
}
