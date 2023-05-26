<?php

namespace Drupal\Tests\field_ui\FunctionalJavascript;

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

}
