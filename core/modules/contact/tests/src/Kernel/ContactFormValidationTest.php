<?php

namespace Drupal\Tests\contact\Kernel;

use Drupal\contact\ContactFormInterface;
use Drupal\contact\Entity\ContactForm;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;

/**
 * Tests validation of contact_form entities.
 *
 * @group contact
 */
class ContactFormValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['contact', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = ContactForm::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $this->entity->save();
  }

  /**
   * Tests validation of message.
   */
  public function testMessageValidation(): void {
    assert($this->entity instanceof ContactFormInterface);
    // Messages should be able to span multiple lines.
    $this->entity->setMessage("Multi\nLine");
    $this->assertValidationErrors([]);
  }

}
