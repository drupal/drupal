<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Form\RebuildTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests functionality of \Drupal\Core\Form\FormBuilderInterface::rebuildForm().
 *
 * @group Form
 * @todo Add tests for other aspects of form rebuilding.
 */
class RebuildTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'form_test');

  /**
   * A user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $this->webUser = $this->drupalCreateUser(array('access content'));
    $this->drupalLogin($this->webUser);
  }

  /**
   * Tests preservation of values.
   */
  function testRebuildPreservesValues() {
    $edit = array(
      'checkbox_1_default_off' => TRUE,
      'checkbox_1_default_on' => FALSE,
      'text_1' => 'foo',
    );
    $this->drupalPostForm('form-test/form-rebuild-preserve-values', $edit, 'Add more');

    // Verify that initial elements retained their submitted values.
    $this->assertFieldChecked('edit-checkbox-1-default-off', 'A submitted checked checkbox retained its checked state during a rebuild.');
    $this->assertNoFieldChecked('edit-checkbox-1-default-on', 'A submitted unchecked checkbox retained its unchecked state during a rebuild.');
    $this->assertFieldById('edit-text-1', 'foo', 'A textfield retained its submitted value during a rebuild.');

    // Verify that newly added elements were initialized with their default values.
    $this->assertFieldChecked('edit-checkbox-2-default-on', 'A newly added checkbox was initialized with a default checked state.');
    $this->assertNoFieldChecked('edit-checkbox-2-default-off', 'A newly added checkbox was initialized with a default unchecked state.');
    $this->assertFieldById('edit-text-2', 'DEFAULT 2', 'A newly added textfield was initialized with its default value.');
  }

  /**
   * Tests that a form's action is retained after an Ajax submission.
   *
   * The 'action' attribute of a form should not change after an Ajax submission
   * followed by a non-Ajax submission, which triggers a validation error.
   */
  function testPreserveFormActionAfterAJAX() {
    // Create a multi-valued field for 'page' nodes to use for Ajax testing.
    $field_name = 'field_ajax_test';
    entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'text',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ))->save();
    entity_create('field_config', array(
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => 'page',
    ))->save();
    entity_get_form_display('node', 'page', 'default')
      ->setComponent($field_name, array('type' => 'text_textfield'))
      ->save();

    // Log in a user who can create 'page' nodes.
    $this->webUser = $this->drupalCreateUser(array('create page content'));
    $this->drupalLogin($this->webUser);

    // Get the form for adding a 'page' node. Submit an "add another item" Ajax
    // submission and verify it worked by ensuring the updated page has two text
    // field items in the field for which we just added an item.
    $this->drupalGet('node/add/page');
    $this->drupalPostAjaxForm(NULL, array(), array('field_ajax_test_add_more' => t('Add another item')), NULL, array(), array(), 'node-page-form');
    $this->assert(count($this->xpath('//div[contains(@class, "field--name-field-ajax-test")]//input[@type="text"]')) == 2, 'AJAX submission succeeded.');

    // Submit the form with the non-Ajax "Save" button, leaving the title field
    // blank to trigger a validation error, and ensure that a validation error
    // occurred, because this test is for testing what happens when a form is
    // re-rendered without being re-built, which is what happens when there's
    // a validation error.
    $this->drupalPostForm(NULL, array(), t('Save'));
    $this->assertText('Title field is required.', 'Non-AJAX submission correctly triggered a validation error.');

    // Ensure that the form contains two items in the multi-valued field, so we
    // know we're testing a form that was correctly retrieved from cache.
    $this->assert(count($this->xpath('//form[contains(@id, "node-page-form")]//div[contains(@class, "form-item-field-ajax-test")]//input[@type="text"]')) == 2, 'Form retained its state from cache.');

    // Ensure that the form's action is correct.
    $forms = $this->xpath('//form[contains(@class, "node-page-form")]');
    $this->assertEqual(1, count($forms));
    // Strip query params off the action before asserting.
    $url = parse_url($forms[0]['action'])['path'];
    $this->assertEqual(Url::fromRoute('node.add', ['node_type' => 'page'])->toString(), $url);
  }
}
