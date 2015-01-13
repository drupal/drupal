<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateContactCategoryTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\contact\Entity\ContactForm;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Migrate contact categories to contact.form.*.yml.
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
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_contact_category');
    $dumps = array(
      $this->getDumpDirectory() . '/Contact.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * The Drupal 6 contact categories to Drupal 8 migration.
   */
  public function testContactCategory() {
    /** @var \Drupal\contact\Entity\ContactForm $contact_form */
    $contact_form = ContactForm::load('website_feedback');
    $this->assertEqual($contact_form->label(), 'Website feedback');
    $this->assertEqual($contact_form->getRecipients(), array('admin@example.com'));
    $this->assertEqual($contact_form->getReply(), '');
    $this->assertEqual($contact_form->getWeight(), 0);

    $contact_form = ContactForm::load('some_other_category');
    $this->assertEqual($contact_form->label(), 'Some other category');
    $this->assertEqual($contact_form->getRecipients(), array('test@example.com'));
    $this->assertEqual($contact_form->getReply(), 'Thanks for contacting us, we will reply ASAP!');
    $this->assertEqual($contact_form->getWeight(), 1);

    $contact_form = ContactForm::load('a_category_much_longer_than_thir');
    $this->assertEqual($contact_form->label(), 'A category much longer than thirty two characters');
    $this->assertEqual($contact_form->getRecipients(), array('fortyninechars@example.com'));
    $this->assertEqual($contact_form->getReply(), '');
    $this->assertEqual($contact_form->getWeight(), 2);
  }

}
