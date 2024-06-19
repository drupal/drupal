<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\entity_test\Entity\EntityTestComputedField;
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
  }

  /**
   * Tests the computed field handler.
   */
  public function testComputedFieldHandler(): void {
    \Drupal::state()->set('entity_test_computed_field_item_list_value', ['computed string']);

    $entity = EntityTestComputedField::create([]);
    $entity->save();

    $view = Views::getView('computed_field_view');

    $rendered_view = $view->preview();
    $output = $this->container->get('renderer')->renderRoot($rendered_view);
    $this->assertStringContainsString('computed string', (string) $output);
  }

}
