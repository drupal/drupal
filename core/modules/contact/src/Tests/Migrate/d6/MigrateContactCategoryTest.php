<?php

/**
 * @file
 * Contains \Drupal\contact\Tests\Migrate\d6\MigrateContactCategoryTest.
 */

namespace Drupal\contact\Tests\Migrate\d6;

use Drupal\contact\Entity\ContactForm;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Migrate contact categories to contact.form.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateContactCategoryTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['contact'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('contact_category');
  }

  /**
   * The Drupal 6 contact categories to Drupal 8 migration.
   */
  public function testContactCategory() {
    /** @var \Drupal\contact\Entity\ContactForm $contact_form */
    $contact_form = ContactForm::load('website_feedback');
    $this->assertIdentical('Website feedback', $contact_form->label());
    $this->assertIdentical(array('admin@example.com'), $contact_form->getRecipients());
    $this->assertIdentical('', $contact_form->getReply());
    $this->assertIdentical(0, $contact_form->getWeight());

    $contact_form = ContactForm::load('some_other_category');
    $this->assertIdentical('Some other category', $contact_form->label());
    $this->assertIdentical(array('test@example.com'), $contact_form->getRecipients());
    $this->assertIdentical('Thanks for contacting us, we will reply ASAP!', $contact_form->getReply());
    $this->assertIdentical(1, $contact_form->getWeight());

    $contact_form = ContactForm::load('a_category_much_longer_than_thir');
    $this->assertIdentical('A category much longer than thirty two characters', $contact_form->label());
    $this->assertIdentical(array('fortyninechars@example.com'), $contact_form->getRecipients());
    $this->assertIdentical('', $contact_form->getReply());
    $this->assertIdentical(2, $contact_form->getWeight());
  }

}
