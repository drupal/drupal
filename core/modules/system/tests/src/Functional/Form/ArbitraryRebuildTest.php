<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests altering forms to be rebuilt so there are multiple steps.
 *
 * @group Form
 */
class ArbitraryRebuildTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['text', 'form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    // Auto-create a field for testing.
    FieldStorageConfig::create([
      'entity_type' => 'user',
      'field_name' => 'test_multiple',
      'type' => 'text',
      'cardinality' => -1,
      'translatable' => FALSE,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'user',
      'field_name' => 'test_multiple',
      'bundle' => 'user',
      'label' => 'Test a multiple valued field',
    ])->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('user', 'user', 'register')
      ->setComponent('test_multiple', [
        'type' => 'text_textfield',
        'weight' => 0,
      ])
      ->save();
  }

  /**
   * Tests a basic rebuild with the user registration form.
   */
  public function testUserRegistrationRebuild() {
    $edit = [
      'name' => 'foo',
      'mail' => 'bar@example.com',
    ];
    $this->drupalPostForm('user/register', $edit, 'Rebuild');
    $this->assertText('Form rebuilt.');
    $this->assertSession()->fieldValueEquals('name', 'foo');
    $this->assertSession()->fieldValueEquals('mail', 'bar@example.com');
  }

  /**
   * Tests a rebuild caused by a multiple value field.
   */
  public function testUserRegistrationMultipleField() {
    $edit = [
      'name' => 'foo',
      'mail' => 'bar@example.com',
    ];
    $this->drupalPostForm('user/register', $edit, 'Add another item');
    $this->assertText('Test a multiple valued field', 'Form has been rebuilt.');
    $this->assertSession()->fieldValueEquals('name', 'foo');
    $this->assertSession()->fieldValueEquals('mail', 'bar@example.com');
  }

}
