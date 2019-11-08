<?php

namespace Drupal\Tests\system\FunctionalJavascript\Form;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests functionality of \Drupal\Core\Form\FormBuilderInterface::rebuildForm().
 *
 * @group Form
 * @todo Add tests for other aspects of form rebuilding.
 */
class RebuildTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $this->webUser = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Tests that a form's action is retained after an Ajax submission.
   *
   * The 'action' attribute of a form should not change after an Ajax submission
   * followed by a non-Ajax submission, which triggers a validation error.
   */
  public function testPreserveFormActionAfterAJAX() {
    $page = $this->getSession()->getPage();
    // Create a multi-valued field for 'page' nodes to use for Ajax testing.
    $field_name = 'field_ajax_test';
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'text',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => 'page',
    ])->save();

    // Also create a file field to test server side validation error.
    $field_file_name = 'field_file_test';
    FieldStorageConfig::create([
      'field_name' => $field_file_name,
      'entity_type' => 'node',
      'type' => 'file',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => $field_file_name,
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Test file',
      'required' => TRUE,
    ])->save();

    \Drupal::service('entity_display.repository')->getFormDisplay('node', 'page', 'default')
      ->setComponent($field_name, ['type' => 'text_textfield'])
      ->setComponent($field_file_name, ['type' => 'file_generic'])
      ->save();

    // Log in a user who can create 'page' nodes.
    $this->webUser = $this->drupalCreateUser(['create page content']);
    $this->drupalLogin($this->webUser);

    // Get the form for adding a 'page' node. Submit an "add another item" Ajax
    // submission and verify it worked by ensuring the updated page has two text
    // field items in the field for which we just added an item.
    $this->drupalGet('node/add/page');
    $page->find('css', '[value="Add another item"]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertTrue(count($this->xpath('//div[contains(@class, "field--name-field-ajax-test")]//input[@type="text"]')) == 2, 'AJAX submission succeeded.');

    // Submit the form with the non-Ajax "Save" button, leaving the file field
    // blank to trigger a validation error, and ensure that a validation error
    // occurred, because this test is for testing what happens when a form is
    // re-rendered without being re-built, which is what happens when there's
    // a server side validation error.
    $edit = [
      'title[0][value]' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertSession()->pageTextContains('Test file field is required.', 'Non-AJAX submission correctly triggered a validation error.');

    // Ensure that the form contains two items in the multi-valued field, so we
    // know we're testing a form that was correctly retrieved from cache.
    $this->assertTrue(count($this->xpath('//form[contains(@id, "node-page-form")]//div[contains(@class, "js-form-item-field-ajax-test")]//input[@type="text"]')) == 2, 'Form retained its state from cache.');

    // Ensure that the form's action is correct.
    $forms = $this->xpath('//form[contains(@class, "node-page-form")]');
    $this->assertEquals(1, count($forms));
    // Strip query params off the action before asserting.
    $url = parse_url($forms[0]->getAttribute('action'))['path'];
    $this->assertEquals(Url::fromRoute('node.add', ['node_type' => 'page'])->toString(), $url);
  }

}
