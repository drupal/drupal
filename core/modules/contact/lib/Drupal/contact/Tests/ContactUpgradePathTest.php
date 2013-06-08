<?php

/**
 * @file
 * Definition of Drupal\contact\Tests\ContactUpgradePathTest.
 */

namespace Drupal\contact\Tests;

use Drupal\system\Tests\Upgrade\UpgradePathTestBase;

/**
 * Tests upgrade of contact.
 */
class ContactUpgradePathTest extends UpgradePathTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Contact upgrade test',
      'description' => 'Tests upgrade of contact to the configuration system.',
      'group' => 'Contact',
    );
  }

  public function setUp() {
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
      drupal_get_path('module', 'contact') . '/tests/drupal-7.contact.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests upgrade of contact table to configuration entities.
   */
  public function testContactUpgrade() {
    $default_contact_category = db_query('SELECT cid FROM {contact} WHERE selected = 1')->fetchField();

    $this->assertTrue(db_table_exists('contact'), 'Contact table exists.');
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    // Test that the contact form is available.
    $this->assertFalse(db_table_exists('contact'), 'Contact table has been deleted.');
    $this->drupalGet('contact');
    $this->assertText(t('Your e-mail address'));

    // Ensure that the Drupal 7 default contact category has been created.
    $contact_category = entity_load('contact_category', '1');
    $this->assertTrue(isset($contact_category->uuid), 'Converted contact category has a UUID');
    $this->assertEqual($contact_category->label, 'Website feedback');
    $this->assertEqual($contact_category->reply, '');
    $this->assertEqual($contact_category->weight, 0);
    $this->assertEqual($contact_category->recipients, array('admin@example.com'));

    // Test that the custom contact category has been updated.
    $contact_category = entity_load('contact_category', '2');
    $this->assertTrue(isset($contact_category->uuid), 'Converted contact category has a UUID');
    $this->assertEqual($contact_category->label, 'Upgrade test');
    $this->assertEqual($contact_category->reply, 'Test reply');
    $this->assertEqual($contact_category->weight, 1);
    $this->assertEqual($contact_category->recipients, array('test1@example.com', 'test2@example.com'));

    // Ensure that the default category has been maintained.
    $this->assertEqual(config('contact.settings')->get('default_category'), $default_contact_category, 'Default category upgraded.');

    // Check that no default config imported on upgrade.
    $this->assertFalse(entity_load('contact_category', 'feedback'));
  }
}

