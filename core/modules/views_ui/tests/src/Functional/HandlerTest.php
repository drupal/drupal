<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\ViewExecutable;

/**
 * Tests handler UI for views.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\HandlerBase
 */
class HandlerTest extends UITestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node_test_views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view_empty', 'test_view_broken', 'node', 'test_node_view'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->placeBlock('page_title_block');
    ViewTestData::createTestViews(static::class, ['node_test_views']);
  }

  /**
   * Overrides \Drupal\views\Tests\ViewTestBase::schemaDefinition().
   *
   * Adds a uid column to test the relationships.
   *
   * @internal
   */
  protected function schemaDefinition() {
    $schema = parent::schemaDefinition();

    $schema['views_test_data']['fields']['uid'] = [
      'description' => "The {users}.uid of the author of the beatle entry.",
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ];

    return $schema;
  }

  /**
   * Overrides \Drupal\views\Tests\ViewTestBase::viewsData().
   *
   * Adds:
   * - a relationship for the uid column.
   * - a dummy field with no help text.
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['uid'] = [
      'title' => t('UID'),
      'help' => t('The test data UID'),
      'relationship' => [
        'id' => 'standard',
        'base' => 'users_field_data',
        'base field' => 'uid',
      ],
    ];

    // Create a dummy field with no help text.
    $data['views_test_data']['no_help'] = $data['views_test_data']['name'];
    $data['views_test_data']['no_help']['field']['title'] = t('No help');
    $data['views_test_data']['no_help']['field']['real field'] = 'name';
    unset($data['views_test_data']['no_help']['help']);

    return $data;
  }

  /**
   * Tests UI CRUD.
   */
  public function testUICRUD() {
    $handler_types = ViewExecutable::getHandlerTypes();
    foreach ($handler_types as $type => $type_info) {
      // Test adding handlers.
      $add_handler_url = "admin/structure/views/nojs/add-handler/test_view_empty/default/$type";

      // Area handler types need to use a different handler.
      if (in_array($type, ['header', 'footer', 'empty'])) {
        $this->drupalGet($add_handler_url);
        $this->submitForm([
          'name[views.area]' => TRUE,
        ], 'Add and configure ' . $type_info['ltitle']);
        $id = 'area';
        $edit_handler_url = "admin/structure/views/nojs/handler/test_view_empty/default/$type/$id";
      }
      elseif ($type == 'relationship') {
        $this->drupalGet($add_handler_url);
        $this->submitForm([
          'name[views_test_data.uid]' => TRUE,
        ], 'Add and configure ' . $type_info['ltitle']);
        $id = 'uid';
        $edit_handler_url = "admin/structure/views/nojs/handler/test_view_empty/default/$type/$id";
      }
      else {
        $this->drupalGet($add_handler_url);
        $this->submitForm([
          'name[views_test_data.job]' => TRUE,
        ], 'Add and configure ' . $type_info['ltitle']);
        $id = 'job';
        $edit_handler_url = "admin/structure/views/nojs/handler/test_view_empty/default/$type/$id";
      }

      // Verify that the user got redirected to the handler edit form.
      $this->assertSession()->addressEquals($edit_handler_url);
      $random_label = $this->randomMachineName();
      $this->submitForm(['options[admin_label]' => $random_label], 'Apply');

      // Verify that the user got redirected to the views edit form.
      $this->assertSession()->addressEquals('admin/structure/views/view/test_view_empty/edit/default');
      $this->assertSession()->linkByHrefExists($edit_handler_url, 0, 'The handler edit link appears in the UI.');
      // Test that the  handler edit link has the right label.
      $this->assertSession()->elementExists('xpath', "//a[starts-with(normalize-space(text()), '{$random_label}')]");

      // Save the view and have a look whether the handler was added as expected.
      $this->submitForm([], 'Save');
      $view = $this->container->get('entity_type.manager')->getStorage('view')->load('test_view_empty');
      $display = $view->getDisplay('default');
      $this->assertTrue(isset($display['display_options'][$type_info['plural']][$id]), 'Ensure the field was added to the view itself.');

      // Remove the item and check that it's removed
      $this->drupalGet($edit_handler_url);
      $this->submitForm([], 'Remove');
      $this->assertSession()->linkByHrefNotExists($edit_handler_url, 0, 'The handler edit link does not appears in the UI after removing.');

      $this->submitForm([], 'Save');
      $view = $this->container->get('entity_type.manager')->getStorage('view')->load('test_view_empty');
      $display = $view->getDisplay('default');
      $this->assertFalse(isset($display['display_options'][$type_info['plural']][$id]), 'Ensure the field was removed from the view itself.');
    }

    // Test adding a field of the user table using the uid relationship.
    $type_info = $handler_types['relationship'];
    $add_handler_url = "admin/structure/views/nojs/add-handler/test_view_empty/default/relationship";
    $this->drupalGet($add_handler_url);
    $this->submitForm([
      'name[views_test_data.uid]' => TRUE,
    ], 'Add and configure ' . $type_info['ltitle']);

    $add_handler_url = "admin/structure/views/nojs/add-handler/test_view_empty/default/field";
    $type_info = $handler_types['field'];
    $this->drupalGet($add_handler_url);
    $this->submitForm([
      'name[users_field_data.name]' => TRUE,
    ], 'Add and configure ' . $type_info['ltitle']);
    $id = 'name';
    $edit_handler_url = "admin/structure/views/nojs/handler/test_view_empty/default/field/$id";

    // Verify that the user got redirected to the handler edit form.
    $this->assertSession()->addressEquals($edit_handler_url);
    $this->assertSession()->fieldValueEquals('options[relationship]', 'uid');
    $this->submitForm([], 'Apply');

    $this->submitForm([], 'Save');
    $view = $this->container->get('entity_type.manager')->getStorage('view')->load('test_view_empty');
    $display = $view->getDisplay('default');
    $this->assertTrue(isset($display['display_options'][$type_info['plural']][$id]), 'Ensure the field was added to the view itself.');
  }

  /**
   * Tests escaping of field labels in help text.
   */
  public function testHandlerHelpEscaping() {
    // Setup a field with two instances using a different label.
    // Ensure that the label is escaped properly.

    $this->drupalCreateContentType(['type' => 'article']);
    $this->drupalCreateContentType(['type' => 'page']);

    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'The giraffe" label',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'The <em>giraffe"</em> label <script>alert("the return of the xss")</script>',
    ])->save();

    $this->drupalGet('admin/structure/views/nojs/add-handler/content/default/field');
    $this->assertSession()->assertEscaped('The <em>giraffe"</em> label <script>alert("the return of the xss")</script>');
    $this->assertSession()->assertEscaped('Appears in: page, article. Also known as: Content: The giraffe" label');
  }

  /**
   * Tests broken handlers.
   */
  public function testBrokenHandlers() {
    $handler_types = ViewExecutable::getHandlerTypes();
    foreach ($handler_types as $type => $type_info) {
      $this->drupalGet('admin/structure/views/view/test_view_broken/edit');

      $href = "admin/structure/views/nojs/handler/test_view_broken/default/$type/id_broken";
      $text = 'Broken/missing handler';

      // Test that the handler edit link is present.
      $this->assertSession()->elementsCount('xpath', "//a[contains(@href, '{$href}')]", 1);
      $result = $this->assertSession()->elementTextEquals('xpath', "//a[contains(@href, '{$href}')]", $text);

      $this->drupalGet($href);
      $this->assertSession()->elementTextContains('xpath', '//h1[@class="page-title"]', $text);

      $original_configuration = [
        'field' => 'id_broken',
        'id' => 'id_broken',
        'relationship' => 'none',
        'table' => 'views_test_data',
        'plugin_id' => 'numeric',
      ];

      foreach ($original_configuration as $key => $value) {
        $this->assertSession()->pageTextContains($key . ': ' . $value);
      }
    }
  }

  /**
   * Ensures that neither node type or node ID appears multiple times.
   *
   * @see \Drupal\views\EntityViewsData
   */
  public function testNoDuplicateFields() {
    $handler_types = ['field', 'filter', 'sort', 'argument'];

    foreach ($handler_types as $handler_type) {
      $add_handler_url = 'admin/structure/views/nojs/add-handler/test_node_view/default/' . $handler_type;
      $this->drupalGet($add_handler_url);

      $this->assertNoDuplicateField('ID', 'Content');
      $this->assertNoDuplicateField('ID', 'Content revision');
      $this->assertNoDuplicateField('Content type', 'Content');
      $this->assertNoDuplicateField('UUID', 'Content');
      $this->assertNoDuplicateField('Revision ID', 'Content');
      $this->assertNoDuplicateField('Revision ID', 'Content revision');
    }
  }

  /**
   * Ensures that no missing help text is shown.
   *
   * @see \Drupal\views\EntityViewsData
   */
  public function testErrorMissingHelp() {
    // Test that the error message is not shown for entity fields but an empty
    // description field is shown instead.
    $this->drupalGet('admin/structure/views/nojs/add-handler/test_node_view/default/field');
    $this->assertSession()->pageTextNotContains('Error: missing help');
    $this->assertRaw('<td class="description"></td>');

    // Test that no error message is shown for other fields.
    $this->drupalGet('admin/structure/views/nojs/add-handler/test_view_empty/default/field');
    $this->assertSession()->pageTextNotContains('Error: missing help');
  }

  /**
   * Asserts that fields only appear once.
   *
   * @param string $field_name
   *   The field name.
   * @param string $entity_type
   *   The entity type to which the field belongs.
   */
  public function assertNoDuplicateField($field_name, $entity_type) {
    $elements = $this->xpath('//td[.=:entity_type]/preceding-sibling::td[@class="title" and .=:title]', [':title' => $field_name, ':entity_type' => $entity_type]);
    $this->assertCount(1, $elements, $field_name . ' appears just once in ' . $entity_type . '.');
  }

}
