<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateContactCategoryTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * The Drupal 6 contact categories to Drupal 8 migration.
 */
class MigrateContactCategoryTest extends MigrateDrupalTestBase {

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
      'name'  => 'Migrate contact categories.',
      'description'  => 'Migrate contact categories to contact.category.*.yml',
      'group' => 'Migrate Drupal',
    );
  }


  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_contact_category');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6ContactCategory.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * The Drupal 6 contact categories to Drupal 8 migration.
   */
  public function testContactCategory() {
    /** @var \Drupal\contact\Entity\Category $contact_category */
    $contact_category = entity_load('contact_category', 'website_feedback');
    $this->assertEqual($contact_category->label, 'Website feedback');
    $this->assertEqual($contact_category->recipients, 'admin@example.com');
    $this->assertEqual($contact_category->reply, '');
    $this->assertEqual($contact_category->weight, 0);
  }
}
