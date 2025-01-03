<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests the entity reference display plugin.
 *
 * @group views
 *
 * @see \Drupal\views\Plugin\views\display\EntityReference
 */
class DisplayEntityReferenceTest extends ViewTestBase {

  use EntityReferenceFieldCreationTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_display_entity_reference'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'field', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The used field name in the test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The used entity reference field name in the test.
   *
   * @var string
   */
  protected $entityRefFieldName;

  /**
   * The field storage.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field config.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->drupalLogin($this->drupalCreateUser(['administer views']));

    // Create the text field.
    $this->fieldName = 'field_test_entity_ref_display';
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'text',
    ]);
    $this->fieldStorage->save();

    // Create an instance of the text field on the content type.
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
    ]);
    $this->field->save();

    // Add an entity reference field to reference the same base table.
    $this->entityRefFieldName = 'field_test_entity_ref_entity_ref';
    $this->createEntityReferenceField('entity_test', 'entity_test', $this->entityRefFieldName, '', 'entity_test');

    // Create some entities to search. Add a common string to the name and
    // the text field in two entities so we can test that we can search in both.
    for ($i = 0; $i < 5; $i++) {
      EntityTest::create([
        'bundle' => 'entity_test',
        'name' => 'name' . $i,
        $this->fieldName => 'text',
      ])->save();
      EntityTest::create([
        'bundle' => 'entity_test',
        'name' => 'name',
        $this->fieldName => 'text' . $i,
      ])->save();
    }
    EntityTest::create([
      'bundle' => 'entity_test',
      'name' => 'name',
      $this->fieldName => 'tex',
    ])->save();
    EntityTest::create([
      'bundle' => 'entity_test',
      'name' => 'name',
      $this->fieldName => 'TEX',
    ])->save();
    EntityTest::create([
      'bundle' => 'entity_test',
      'name' => 'name',
      $this->fieldName => 'some_text',
    ])->save();
  }

  /**
   * Tests the entity reference display plugin.
   */
  public function testEntityReferenceDisplay(): void {
    // Test that the 'title' settings are not shown.
    $this->drupalGet('admin/structure/views/view/test_display_entity_reference/edit/entity_reference_1');
    $this->assertSession()->linkByHrefNotExists('admin/structure/views/nojs/display/test_display_entity_reference/entity_reference_1/title');

    // Add the new field to the fields.
    $this->drupalGet('admin/structure/views/nojs/add-handler/test_display_entity_reference/default/field');
    $this->submitForm([
      'name[entity_test__' . $this->fieldName . '.' . $this->fieldName . ']' => TRUE,
    ], 'Add and configure fields');
    $this->submitForm([], 'Apply');

    // Test that the right fields are shown on the display settings form.
    $this->drupalGet('admin/structure/views/nojs/display/test_display_entity_reference/entity_reference_1/style_options');
    $this->assertSession()->pageTextContains('Test entity: Name');
    $this->assertSession()->pageTextContains('Test entity: ' . $this->field->label());

    // Add the new field to the search fields.
    $this->submitForm([
      'style_options[search_fields][' . $this->fieldName . ']' => $this->fieldName,
    ], 'Apply');
    $this->submitForm([], 'Save');

    $view = Views::getView('test_display_entity_reference');
    $view->setDisplay('entity_reference_1');

    // Add the required settings to test a search operation.
    $options = [
      'match' => '1',
      'match_operator' => 'CONTAINS',
      'limit' => 0,
      'ids' => NULL,
    ];
    $view->display_handler->setOption('entity_reference_options', $options);

    $this->executeView($view);

    // Test that we have searched in both fields.
    $this->assertCount(2, $view->result, 'Search returned two rows');
    $view->destroy();

    // Test the 'CONTAINS' match_operator.
    $view = Views::getView('test_display_entity_reference');
    $view->setDisplay('entity_reference_1');
    $options = [
      'match' => 'tex',
      'match_operator' => 'CONTAINS',
      'limit' => 0,
      'ids' => NULL,
    ];
    $view->display_handler->setOption('entity_reference_options', $options);
    $this->executeView($view);
    $this->assertCount(13, $view->result, 'Search returned thirteen rows');
    $view->destroy();

    // Test the 'STARTS_WITH' match_operator.
    $view = Views::getView('test_display_entity_reference');
    $view->setDisplay('entity_reference_1');
    $options = [
      'match' => 'tex',
      'match_operator' => 'STARTS_WITH',
      'limit' => 0,
      'ids' => NULL,
    ];
    $view->display_handler->setOption('entity_reference_options', $options);
    $this->executeView($view);
    $this->assertCount(12, $view->result, 'Search returned twelve rows');
    $view->destroy();

    // Test the '=' match_operator.
    $view = Views::getView('test_display_entity_reference');
    $view->setDisplay('entity_reference_1');
    $options = [
      'match' => 'tex',
      'match_operator' => '=',
      'limit' => 0,
      'ids' => NULL,
    ];
    $view->display_handler->setOption('entity_reference_options', $options);
    $this->executeView($view);
    $this->assertCount(2, $view->result, 'Search returned two rows');
    $view->destroy();

    // Add a relationship and a field using that relationship.
    $this->drupalGet('admin/structure/views/nojs/add-handler/test_display_entity_reference/default/relationship');
    $this->submitForm(['name[entity_test.user_id]' => TRUE], 'Add and configure relationships');
    $this->submitForm([], 'Apply');

    $this->drupalGet('admin/structure/views/nojs/add-handler/test_display_entity_reference/default/field');
    $this->submitForm(['name[users_field_data.uid]' => TRUE], 'Add and configure fields');
    $this->submitForm([], 'Apply');

    // Add the new field to the search fields.
    $this->drupalGet('admin/structure/views/nojs/display/test_display_entity_reference/entity_reference_1/style_options');
    $this->submitForm(['style_options[search_fields][uid]' => 'uid'], 'Apply');
    $this->submitForm([], 'Save');

    // Test that the search still works with the related field.
    $view = Views::getView('test_display_entity_reference');
    $view->setDisplay('entity_reference_1');

    // Add the required settings to test a search operation.
    $options = [
      'match' => '2',
      'match_operator' => 'CONTAINS',
      'limit' => 0,
      'ids' => NULL,
    ];
    $view->display_handler->setOption('entity_reference_options', $options);

    $this->executeView($view);

    // Run validation when using a relationship to the same base table.
    $this->assertCount(2, $view->result, 'Search returned two rows');
    $view->destroy();

    $this->drupalGet('admin/structure/views/nojs/add-handler/test_display_entity_reference/default/relationship');
    $this->submitForm([
      'name[entity_test__field_test_entity_ref_entity_ref.field_test_entity_ref_entity_ref]' => TRUE,
    ], 'Add and configure relationships');
    $this->submitForm([], 'Apply');

    $this->submitForm([], 'Save');

    // Test that the search still works with the related field.
    $view = Views::getView('test_display_entity_reference');
    $view->setDisplay('entity_reference_1');

    // Add IDs to trigger validation.
    $options = [
      'match' => '1',
      'match_operator' => 'CONTAINS',
      'limit' => 0,
      'ids' => [1, 2],
    ];
    $view->display_handler->setOption('entity_reference_options', $options);

    $this->executeView($view);

    $this->assertCount(2, $view->result, 'Search returned two rows');

    // Test that the render() return empty array for empty result.
    $view = Views::getView('test_display_entity_reference');
    $view->setDisplay('entity_reference_1');
    $render = $view->display_handler->render();
    $this->assertSame([], $render, 'Render returned empty array');

    // Execute the View without setting the 'entity_reference_options'.
    // This is equivalent to using the following as entity_reference_options.
    // @code
    // $options = [
    //   'match' => NULL,
    //   'match_operator' => 'CONTAINS',
    //   'limit' => 0,
    //   'ids' => NULL,
    // ];
    // @endcode
    // Assert that this view returns a row for each test entity.
    $view->destroy();
    $view = Views::getView('test_display_entity_reference');
    $view->setDisplay('entity_reference_1');
    $this->executeView($view);
    $this->assertCount(13, $view->result, 'Search returned thirteen rows');
  }

}
