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
 * Migrate contact categories to contact.category.*.yml.
 *
 * @group migrate_drupal
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
  public function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_contact_category');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6ContactCategory.php',
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
    $this->assertEqual($contact_category->recipients, array('admin@example.com'));
    $this->assertEqual($contact_category->reply, '');
    $this->assertEqual($contact_category->weight, 0);

    $contact_category = entity_load('contact_category', 'some_other_category');
    $this->assertEqual($contact_category->label, 'Some other category');
    $this->assertEqual($contact_category->recipients, array('test@example.com'));
    $this->assertEqual($contact_category->reply, 'Thanks for contacting us, we will reply ASAP!');
    $this->assertEqual($contact_category->weight, 1);

    $contact_category = entity_load('contact_category', 'a_category_much_longer_than_thir');
    $this->assertEqual($contact_category->label, 'A category much longer than thirty two characters');
    $this->assertEqual($contact_category->recipients, array('fortyninechars@example.com'));
    $this->assertEqual($contact_category->reply, '');
    $this->assertEqual($contact_category->weight, 2);
  }

}
