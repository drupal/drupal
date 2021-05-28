<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\entity_test\Entity\EntityTestComputedField;
use Drupal\entity_test\Entity\EntityTestComputedFieldBundle;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Provides some integration tests for the Field handler.
 *
 * @see \Drupal\views\Plugin\views\field\EntityField
 * @group views
 */
class ComputedFieldTest extends ViewsKernelTestBase {

  /**
   * Views to be enabled.
   *
   * @var array
   */
  public static $testViews = ['computed_field_view'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('entity_test_computed_field');
    $this->installEntitySchema('entity_test_comp_field_bundle');

    // Create a default bundle that has a computed field.
    entity_test_create_bundle('entity_test_comp_field_bundle', NULL, 'entity_test_computed_field');

    // Create a second bundle that also has a computed field.
    entity_test_create_bundle('entity_test_comp_field_bundle_2', NULL, 'entity_test_computed_field');

    // Create a bundle that does not have the computed field.
    entity_test_create_bundle('entity_test_bundle_no_comp_field', NULL, 'entity_test_computed_field');

    // Create an entity using the default bundle with a computed field.
    $entity_with_comp_field = EntityTestComputedField::create([
      'type' => 'entity_test_comp_field_bundle',
      'name' => 'Entity with bundle field',
    ]);
    $entity_with_comp_field->save();

    // Create an entity using the second bundle with a computed field.
    $entity_with_comp_field_2 = EntityTestComputedField::create([
      'type' => 'entity_test_comp_field_bundle_2',
      'name' => 'Entity 2 with bundle field',
    ]);
    $entity_with_comp_field_2->save();

    // Create an entity using the third bundle without a computed field.
    $entity_no_computed_field = EntityTestComputedField::create([
      'type' => 'entity_test_bundle_no_comp_field',
      'name' => 'Entity without bundle field',
    ]);
    $entity_no_computed_field->save();
  }

  /**
   * Tests the computed field handler.
   */
  public function testComputedFieldHandler() {
    \Drupal::state()->set('entity_test_computed_field_item_list_value', ['computed string']);
    \Drupal::state()->set('entity_test_computed_bundle_field_item_list_value', ['some other string that is also computed']);

    $view = Views::getView('computed_field_view');
    $this->executeView($view);
    $this->assertCount(3, $view->result, 'The number of returned rows match.');

    // All bundles should have the computed string basefield.
    $this->assertStringContainsString('computed string', $view->field['computed_string_field']->render($view->result[0]));
    $this->assertStringContainsString('computed string', $view->field['computed_string_field']->render($view->result[1]));
    $this->assertStringContainsString('computed string', $view->field['computed_string_field']->render($view->result[2]));

    // Entities 1 and 2 should have the computed bundle field. But entity 3
    // should not.
    $this->assertStringContainsString('some other string that is also computed', $view->field['computed_bundle_field']->render($view->result[0]));
    $this->assertStringContainsString('some other string that is also computed', $view->field['computed_bundle_field']->render($view->result[1]));
    $this->assertStringNotContainsString('some other string that is also computed', $view->field['computed_bundle_field']->render($view->result[2]));

    $view->destroy();
  }

}
