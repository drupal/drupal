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
  public static $modules = ['text', 'form_test'];

  protected function setUp() {
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
    $this->assertFieldByName('name', 'foo', 'Entered username has been kept.');
    $this->assertFieldByName('mail', 'bar@example.com', 'Entered mail address has been kept.');
  }

  /**
   * Tests a rebuild caused by a multiple value field.
   */
  public function testUserRegistrationMultipleField() {
    $edit = [
      'name' => 'foo',
      'mail' => 'bar@example.com',
    ];
    $this->drupalPostForm('user/register', $edit, t('Add another item'));
    $this->assertText('Test a multiple valued field', 'Form has been rebuilt.');
    $this->assertFieldByName('name', 'foo', 'Entered username has been kept.');
    $this->assertFieldByName('mail', 'bar@example.com', 'Entered mail address has been kept.');
  }

}
