<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\entity_test\Entity\EntityTestComputedBundleField;
use Drupal\entity_test\EntityTestHelper;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Provides some integration tests for computed bundle fields.
 *
 * @see \Drupal\views\Plugin\views\field\EntityField
 * @group views
 */
class ComputedBundleFieldTest extends ViewsKernelTestBase {

  /**
   * Views to be enabled.
   *
   * @var array
   */
  public static $testViews = ['computed_bundle_field_view'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    // Don't install the test view until the bundles are defined.
    parent::setUp(FALSE);

    $this->installEntitySchema('entity_test_comp_bund_fld');

    // Create a default bundle that has a computed field.
    EntityTestHelper::createBundle('entity_test_comp_bund_fld_bund', NULL, 'entity_test_comp_bund_fld');

    // Create a second bundle that also has a computed field.
    EntityTestHelper::createBundle('entity_test_comp_bund_fld_bund_2', NULL, 'entity_test_comp_bund_fld');

    // Create a bundle that does not have the computed field.
    EntityTestHelper::createBundle('entity_test_bundle_no_comp_field', NULL, 'entity_test_comp_bund_fld');

    ViewTestData::createTestViews(static::class, ['views_test_config']);

    // Create an entity using the default bundle with a computed field.
    $entity_with_comp_field = EntityTestComputedBundleField::create([
      'type' => 'entity_test_comp_bund_fld_bund',
      'name' => 'Entity with bundle field',
    ]);
    $entity_with_comp_field->save();

    // Create an entity using the second bundle with a computed field.
    $entity_with_comp_field_2 = EntityTestComputedBundleField::create([
      'type' => 'entity_test_comp_bund_fld_bund_2',
      'name' => 'Entity 2 with bundle field',
    ]);
    $entity_with_comp_field_2->save();

    // Create an entity using the third bundle without a computed field.
    $entity_no_computed_field = EntityTestComputedBundleField::create([
      'type' => 'entity_test_bundle_no_comp_field',
      'name' => 'Entity without bundle field',
    ]);
    $entity_no_computed_field->save();
  }

  /**
   * Tests the computed field handler.
   */
  public function testComputedFieldHandler(): void {
    \Drupal::state()->set('entity_test_computed_field_item_list_value', ['computed string']);
    \Drupal::state()->set('entity_test_comp_bund_fld_item_list_value', ['some other string that is also computed']);

    $view = Views::getView('computed_bundle_field_view');
    $this->executeView($view);
    $this->assertCount(3, $view->result, 'The number of returned rows match.');

    // Entities 1 and 2 should have the computed bundle field. But entity 3
    // should not.
    $this->assertStringContainsString('some other string that is also computed', (string) $view->field['computed_bundle_field']->render($view->result[0]));
    $this->assertStringContainsString('some other string that is also computed', (string) $view->field['computed_bundle_field']->render($view->result[1]));
    $this->assertStringNotContainsString('some other string that is also computed', (string) $view->field['computed_bundle_field']->render($view->result[2]));

    $view->destroy();
  }

}
