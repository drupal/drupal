<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiJSTestTrait;

// cspell:ignore horserad

/**
 * Tests the Field UI "Manage Fields" screens.
 *
 * @group field_ui
 */
class ManageFieldsTest extends WebDriverTestBase {

  use FieldUiJSTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field_ui',
    'field_test',
    'block',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @var string
   */
  protected $type;

  /**
   * @var string
   */

  protected $type2;
  /**
   * @var \Drupal\Core\Entity\entityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');

    // Create a test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
    ]);
    $this->drupalLogin($admin_user);

    $type = $this->drupalCreateContentType([
      'name' => 'Article',
      'type' => 'article',
    ]);
    $this->type = $type->id();

    $type2 = $this->drupalCreateContentType([
      'name' => 'Basic Page',
      'type' => 'page',
    ]);
    $this->type2 = $type2->id();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests re-using an existing field and the visibility of the re-use button.
   */
  public function testReuseExistingField() {
    $path = 'admin/structure/types/manage/article';
    $path2 = 'admin/structure/types/manage/page';
    $this->drupalGet($path2 . '/fields');
    // The button should not be visible without any re-usable fields.
    $this->assertSession()->linkNotExists('Re-use an existing field');
    $field_label = 'Test field';
    // Create a field, and a node with some data for the field.
    $this->fieldUIAddNewFieldJS($path, 'test', $field_label);
    // Add an existing field.
    $this->fieldUIAddExistingFieldJS($path2, 'field_test', $field_label);
    // Confirm the button is no longer visible after re-using the field.
    $this->assertSession()->linkNotExists('Re-use an existing field');
  }

  /**
   * Tests filter results in the re-use form.
   */
  public function testFilterInReuseForm() {
    $session = $this->getSession();
    $page = $session->getPage();
    $path = 'admin/structure/types/manage/article';
    $path2 = 'admin/structure/types/manage/page';
    $this->fieldUIAddNewFieldJS($path, 'horse', 'Horse');
    $this->fieldUIAddNewFieldJS($path, 'horseradish', 'Horseradish', 'text');
    $this->fieldUIAddNewFieldJS($path, 'carrot', 'Carrot', 'text');
    $this->drupalGet($path2 . '/fields');
    $this->assertSession()->linkExists('Re-use an existing field');
    $this->clickLink('Re-use an existing field');
    $this->assertSession()->waitForElementVisible('css', '#drupal-modal');
    $filter = $this->assertSession()->waitForElementVisible('css', 'input[name="search"]');
    $horse_field_row = $page->find('css', '.js-reuse-table tr[data-field-id="field_horse"]');
    $horseradish_field_row = $page->find('css', '.js-reuse-table tr[data-field-id="field_horseradish"]');
    $carrot_field_row = $page->find('css', '.js-reuse-table tr[data-field-id="field_carrot"]');
    // Confirm every field is visible first.
    $this->assertTrue($horse_field_row->isVisible());
    $this->assertTrue($horseradish_field_row->isVisible());
    $this->assertTrue($carrot_field_row->isVisible());
    // Filter by 'horse' field name.
    $filter->setValue('horse');
    $session->wait(1000, "jQuery('[data-field-id=\"field_carrot\"]:visible').length == 0");
    $this->assertTrue($horse_field_row->isVisible());
    $this->assertTrue($horseradish_field_row->isVisible());
    $this->assertFalse($carrot_field_row->isVisible());
    // Filter even more so only 'horseradish' is visible.
    $filter->setValue('horserad');
    $session->wait(1000, "jQuery('[data-field-id=\"field_horse\"]:visible').length == 0");
    $this->assertFalse($horse_field_row->isVisible());
    $this->assertTrue($horseradish_field_row->isVisible());
    $this->assertFalse($carrot_field_row->isVisible());
    // Filter by field type but search with 'ext' instead of 'text' to
    // confirm that contains-based search works.
    $filter->setValue('ext');
    $session->wait(1000, "jQuery('[data-field-id=\"field_horse\"]:visible').length == 0");
    $session->wait(1000, "jQuery('[data-field-id=\"field_carrot\"]:visible').length == 1");
    $this->assertFalse($horse_field_row->isVisible());
    $this->assertTrue($horseradish_field_row->isVisible());
    $this->assertTrue($carrot_field_row->isVisible());
    // Ensure clearing brings all the results back.
    $filter->setValue('');
    $session->wait(1000, "jQuery('[data-field-id=\"field_horse\"]:visible').length == 1");
    $this->assertTrue($horse_field_row->isVisible());
    $this->assertTrue($horseradish_field_row->isVisible());
    $this->assertTrue($carrot_field_row->isVisible());
  }

  /**
   * Tests that field delete operation opens in modal.
   */
  public function testFieldDelete() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/structure/types/manage/article/fields');

    $page->find('css', '.dropbutton-toggle button')->click();
    $page->clickLink('Delete');

    // Asserts a dialog opens with the expected text.
    $this->assertEquals('Are you sure you want to delete the field Body?', $assert_session->waitForElement('css', '.ui-dialog-title')->getText());

    $page->find('css', '.ui-dialog-buttonset')->pressButton('Delete');
    $assert_session->waitForText('The field Body has been deleted from the Article content type.');
  }

  /**
   * Tests field add.
   */
  public function testAddField() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');
    $field_name = 'test_field_1';
    $page->fillField('label', $field_name);

    // Test validation.
    $page->pressButton('Continue');
    $assert_session->pageTextContains('You need to select a field type.');
    $assert_session->elementExists('css', '[name="new_storage_type"].error');
    $assert_session->pageTextNotContains('Choose an option below');

    $this->assertNotEmpty($number_field = $page->find('xpath', '//*[text() = "Number"]')->getParent());
    $number_field->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertTrue($assert_session->elementExists('css', '[name="new_storage_type"][value="number"]')->isSelected());
    $assert_session->pageTextContains('Choose an option below');
    $page->pressButton('Continue');
    $assert_session->pageTextContains('You need to select a field type.');
    $assert_session->elementNotExists('css', '[name="new_storage_type"].error');
    $assert_session->elementExists('css', '[name="group_field_options_wrapper"].error');

    // Try adding a field using a grouped field type.
    $this->assertNotEmpty($email_field = $page->find('xpath', '//*[text() = "Email"]')->getParent());
    $email_field->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertTrue($assert_session->elementExists('css', '[name="new_storage_type"][value="email"]')->isSelected());
    $assert_session->pageTextNotContains('Choose an option below');

    $this->assertNotEmpty($text = $page->find('xpath', '//*[text() = "Plain text"]')->getParent());
    $text->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertTrue($assert_session->elementExists('css', '[name="new_storage_type"][value="plain_text"]')->isSelected());
    $assert_session->pageTextContains('Choose an option below');

    $this->assertNotEmpty($text_plain = $page->find('xpath', '//*[text() = "Text (plain)"]')->getParent());
    $text_plain->click();
    $this->assertTrue($assert_session->elementExists('css', '[name="group_field_options_wrapper"][value="string"]')->isSelected());
    $page->pressButton('Continue');

    $this->assertMatchesRegularExpression('/.*article\/add-field\/node\/field_test_field_1.*/', $this->getUrl());

    // Ensure the default value is reloaded when the field storage settings
    // are changed.
    $default_input_1_name = 'default_value_input[field_test_field_1][0][value]';
    $default_input_1 = $assert_session->fieldExists($default_input_1_name);
    $this->assertFalse($default_input_1->isVisible());

    $default_value = $assert_session->fieldExists('set_default_value');
    $default_value->check();
    $assert_session->waitForElementVisible('xpath', $default_value->getXpath());
    $default_input_1->setValue('There can be only one!');
    $default_input_2_name = 'default_value_input[field_test_field_1][1][value]';
    $assert_session->fieldNotExists($default_input_2_name);
    $cardinality = $assert_session->fieldExists('field_storage[subform][cardinality_number]');
    $cardinality->setValue(2);
    $default_input_2 = $assert_session->waitForField($default_input_2_name);
    // Ensure the default value for first input is retained.
    $assert_session->fieldValueEquals($default_input_1_name, 'There can be only one!');
    $page->findField($default_input_2_name)->setValue('But maybe also two?');
    $cardinality->setValue('1');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->waitForElementRemoved('xpath', $default_input_2->getXpath());
    // Ensure the first input retains its value.
    $assert_session->fieldValueEquals($default_input_1_name, 'There can be only one!');
    $cardinality->setValue(2);
    $assert_session->waitForField($default_input_2_name);
    // Ensure when the second input is added again it does not retain its value.
    $assert_session->fieldValueEquals($default_input_2_name, '');

    // Ensure changing the max length input will also reload the form.
    $max_length_input = $assert_session->fieldExists('field_storage[subform][settings][max_length]');
    $this->assertSame('255', $max_length_input->getValue());
    $this->assertSame('255', $default_input_1->getAttribute('maxlength'));
    $max_length_input->setValue('5');
    $page->waitFor(5, function () use ($default_input_1) {
      return $default_input_1->getAttribute('maxlength') === '5';
    });
    $this->assertSame('5', $default_input_1->getAttribute('maxlength'));
    // Set a default value that is under the new limit.
    $default_input_1->setValue('Five!');

    $page->pressButton('Save settings');
    $assert_session->pageTextContains('Saved ' . $field_name . ' configuration.');
    $this->assertNotNull($field_storage = FieldStorageConfig::loadByName('node', "field_$field_name"));
    $this->assertEquals('string', $field_storage->getType());

    // Try adding a field using a non-grouped field type.
    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');
    $field_name = 'test_field_2';
    $page->fillField('label', $field_name);

    $this->assertNotEmpty($number_field = $page->find('xpath', '//*[text() = "Number"]')->getParent());
    $number_field->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertTrue($assert_session->elementExists('css', '[name="new_storage_type"][value="number"]')->isSelected());
    $assert_session->pageTextContains('Choose an option below');
    $this->assertNotEmpty($number_integer = $page->find('xpath', '//*[text() = "Number (integer)"]')->getParent());
    $number_integer->click();
    $this->assertTrue($assert_session->elementExists('css', '[name="group_field_options_wrapper"][value="integer"]')->isSelected());

    $this->assertNotEmpty($test_field = $page->find('xpath', '//*[text() = "Test field"]')->getParent());
    $test_field->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertTrue($assert_session->elementExists('css', '[name="new_storage_type"][value="test_field"]')->isSelected());
    $assert_session->pageTextNotContains('Choose an option below');

    $page->pressButton('Continue');
    $this->assertMatchesRegularExpression('/.*article\/add-field\/node\/field_test_field_2.*/', $this->getUrl());
    $page->pressButton('Save settings');
    $assert_session->pageTextContains('Saved ' . $field_name . ' configuration.');
    $this->assertNotNull($field_storage = FieldStorageConfig::loadByName('node', "field_$field_name"));
    $this->assertEquals('test_field', $field_storage->getType());
  }

  /**
   * Tests the order in which the field types appear in the form.
   */
  public function testFieldTypeOrder() {
    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');
    $page = $this->getSession()->getPage();
    $field_type_categories = [
      'selection_list',
      'number',
    ];
    foreach ($field_type_categories as $field_type_category) {
      // Select the group card.
      $group_field_card = $page->find('css', "[name='new_storage_type'][value='$field_type_category']")->getParent();
      $group_field_card->click();
      $this->assertSession()->assertWaitOnAjaxRequest();
      $field_types = $page->findAll('css', '.subfield-option .option');
      $field_type_labels = [];
      foreach ($field_types as $field_type) {
        $field_type_labels[] = $field_type->getText();
      }
      $expected_field_types = match ($field_type_category) {
        'selection_list' => [
          'List (text)',
          'List (integer)',
          'List (float)',
        ],
        'number' => [
          'Number (integer)',
          'Number (decimal)',
          'Number (float)',
        ],
      };
      // Assert that the field type options are displayed as per their weights.
      $this->assertSame($expected_field_types, $field_type_labels);
    }
  }

  /**
   * Tests the form validation for allowed values field.
   */
  public function testAllowedValuesFormValidation() {
    FieldStorageConfig::create([
      'field_name' => 'field_text',
      'entity_type' => 'node',
      'type' => 'text',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_text',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();
    $this->drupalGet('/admin/structure/types/manage/article/fields/node.article.field_text');
    $page = $this->getSession()->getPage();
    $page->findField('edit-field-storage-subform-cardinality-number')->setValue('-11');
    $this->assertSession()->assertExpectedAjaxRequest(1);
    $page->findButton('Save settings')->click();
    $this->assertSession()->pageTextContains('Limit must be higher than or equal to 1.');
  }

  /**
   * Tests the form validation for label field.
   */
  public function testLabelFieldFormValidation() {
    $this->drupalGet('/admin/structure/types/manage/article/fields/add-field');
    $page = $this->getSession()->getPage();
    $page->findButton('Continue')->click();

    $this->assertSession()->pageTextContains('You need to provide a label.');
    $this->assertSession()->pageTextContains('You need to select a field type.');
  }

}
