<?php

namespace Drupal\Tests\contact\Kernel\Migrate;

use Drupal\contact\Entity\ContactForm;
use Drupal\contact\ContactFormInterface;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Migrate contact categories to contact.form.*.yml.
 *
 * @group contact_category
 */
class MigrateContactCategoryTest extends MigrateDrupal6TestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['contact'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('contact_category');
  }

  /**
   * Performs various assertions on a single contact form entity.
   *
   * @param string $id
   *   The contact form ID.
   * @param string $expected_label
   *   The expected label.
   * @param string[] $expected_recipients
   *   The recipient e-mail addresses the form should have.
   * @param string $expected_reply
   *   The expected reply message.
   * @param int $expected_weight
   *   The contact form's expected weight.
   *
   * @internal
   */
  protected function assertEntity(string $id, string $expected_label, array $expected_recipients, string $expected_reply, int $expected_weight): void {
    /** @var \Drupal\contact\ContactFormInterface $entity */
    $entity = ContactForm::load($id);
    $this->assertInstanceOf(ContactFormInterface::class, $entity);
    $this->assertSame($expected_label, $entity->label());
    $this->assertSame($expected_recipients, $entity->getRecipients());
    $this->assertSame($expected_reply, $entity->getReply());
    $this->assertSame($expected_weight, $entity->getWeight());
  }

  /**
   * The Drupal 6 and 7 contact categories to Drupal 8 migration.
   */
  public function testContactCategory() {
    $this->assertEntity('website_feedback', 'Website feedback', ['admin@example.com'], '', 0);
    $this->assertEntity('some_other_category', 'Some other category', ['test@example.com'], 'Thanks for contacting us, we will reply ASAP!', 1);
    $this->assertEntity('a_category_much_longer_than_th', 'A category much longer than thirty two characters', ['fortyninechars@example.com'], '', 2);

    // Test there are no duplicated roles.
    $contact_forms = [
      'website_feedback1',
      'some_other_category1',
      'a_category_much_longer_than_thir1',
    ];
    $this->assertEmpty(ContactForm::loadMultiple($contact_forms));

    /*
     * Remove the map row for the Website feedback contact form so that it
     * can be migrated again.
     */
    $id_map = $this->getMigration('contact_category')->getIdMap();
    $id_map->delete(['cid' => '1']);
    $this->executeMigration('contact_category');

    // Test there is a duplicate Website feedback form.
    $this->assertEntity('website_feedback1', 'Website feedback', ['admin@example.com'], '', 0);
  }

}
