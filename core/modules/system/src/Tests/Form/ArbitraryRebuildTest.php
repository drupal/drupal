<?php

namespace Drupal\system\Tests\Form;

use Drupal\field\Entity\FieldConfig;
use Drupal\simpletest\WebTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests altering forms to be rebuilt so there are multiple steps.
 *
 * @group Form
 */
class ArbitraryRebuildTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('text', 'form_test');

  protected function setUp() {
    parent::setUp();

    // Auto-create a field for testing.
    FieldStorageConfig::create(array(
      'entity_type' => 'user',
      'field_name' => 'test_multiple',
      'type' => 'text',
      'cardinality' => -1,
      'translatable' => FALSE,
    ))->save();
    FieldConfig::create([
      'entity_type' => 'user',
      'field_name' => 'test_multiple',
      'bundle' => 'user',
      'label' => 'Test a multiple valued field',
    ])->save();
    entity_get_form_display('user', 'user', 'register')
      ->setComponent('test_multiple', array(
        'type' => 'text_textfield',
        'weight' => 0,
      ))
      ->save();
  }

  /**
   * Tests a basic rebuild with the user registration form.
   */
  function testUserRegistrationRebuild() {
    $edit = array(
      'name' => 'foo',
      'mail' => 'bar@example.com',
    );
    $this->drupalPostForm('user/register', $edit, 'Rebuild');
    $this->assertText('Form rebuilt.');
    $this->assertFieldByName('name', 'foo', 'Entered username has been kept.');
    $this->assertFieldByName('mail', 'bar@example.com', 'Entered mail address has been kept.');
  }

  /**
   * Tests a rebuild caused by a multiple value field.
   */
  function testUserRegistrationMultipleField() {
    $edit = array(
      'name' => 'foo',
      'mail' => 'bar@example.com',
    );
    $this->drupalPostForm('user/register', $edit, t('Add another item'));
    $this->assertText('Test a multiple valued field', 'Form has been rebuilt.');
    $this->assertFieldByName('name', 'foo', 'Entered username has been kept.');
    $this->assertFieldByName('mail', 'bar@example.com', 'Entered mail address has been kept.');
  }
}
