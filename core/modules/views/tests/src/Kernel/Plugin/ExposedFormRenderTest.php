<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Component\Utility\Html;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the exposed form.
 *
 * @group views
 * @see \Drupal\views_test_data\Plugin\views\display_extender\DisplayExtenderTest
 */
class ExposedFormRenderTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_exposed_form_buttons'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();
    $this->installEntitySchema('node');
  }

  /**
   * Tests the exposed form markup.
   */
  public function testExposedFormRender() {
    $view = Views::getView('test_exposed_form_buttons');
    $this->executeView($view);
    $exposed_form = $view->display_handler->getPlugin('exposed_form');
    $output = $exposed_form->renderExposedForm();
    $this->setRawContent(\Drupal::service('renderer')->renderRoot($output));

    $this->assertFieldByXpath('//form/@id', Html::cleanCssIdentifier('views-exposed-form-' . $view->storage->id() . '-' . $view->current_display), 'Expected form ID found.');

    $view->setDisplay('page_1');
    $expected_action = $view->display_handler->getUrlInfo()->toString();
    $this->assertFieldByXPath('//form/@action', $expected_action, 'The expected value for the action attribute was found.');
    // Make sure the description is shown.
    $result = $this->xpath('//form//div[contains(@id, "edit-type--2--description") and normalize-space(text())="Exposed description"]');
    $this->assertCount(1, $result, 'Filter description was found.');
  }

  /**
   * Tests the exposed form raw input.
   */
  public function testExposedFormRawInput() {
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    $view = Views::getView('test_exposed_form_buttons');
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('filters', [
      'type' => [
        'exposed' => TRUE,
        'field' => 'type',
        'id' => 'type',
        'table' => 'node_field_data',
        'plugin_id' => 'in_operator',
        'entity_type' => 'node',
        'entity_field' => 'type',
        'expose' => [
          'identifier' => 'type',
          'label' => 'Content: Type',
          'operator_id' => 'type_op',
          'reduce' => FALSE,
          'multiple' => FALSE,
        ],
      ],
      'type_with_default_value' => [
        'exposed' => TRUE,
        'field' => 'type',
        'id' => 'type_with_default_value',
        'table' => 'node_field_data',
        'plugin_id' => 'in_operator',
        'entity_type' => 'node',
        'entity_field' => 'type',
        'value' => ['article', 'article'],
        'expose' => [
          'identifier' => 'type_with_default_value',
          'label' => 'Content: Type with value',
          'operator_id' => 'type_op',
          'reduce' => FALSE,
          'multiple' => FALSE,
        ],
      ],
      'multiple_types' => [
        'exposed' => TRUE,
        'field' => 'type',
        'id' => 'multiple_types',
        'table' => 'node_field_data',
        'plugin_id' => 'in_operator',
        'entity_type' => 'node',
        'entity_field' => 'type',
        'expose' => [
          'identifier' => 'multiple_types',
          'label' => 'Content: Type (multiple)',
          'operator_id' => 'type_op',
          'reduce' => FALSE,
          'multiple' => TRUE,
        ],
      ],
      'multiple_types_with_default_value' => [
        'exposed' => TRUE,
        'field' => 'type',
        'id' => 'multiple_types_with_default_value',
        'table' => 'node_field_data',
        'plugin_id' => 'in_operator',
        'entity_type' => 'node',
        'entity_field' => 'type',
        'value' => ['article', 'article'],
        'expose' => [
          'identifier' => 'multiple_types_with_default_value',
          'label' => 'Content: Type with default value (multiple)',
          'operator_id' => 'type_op',
          'reduce' => FALSE,
          'multiple' => TRUE,
        ],
      ],
    ]);
    $view->save();
    $this->executeView($view);

    $expected = [
      'type' => 'All',
      'type_with_default_value' => 'article',
      'multiple_types_with_default_value' => ['article' => 'article'],
    ];
    $this->assertSame($view->exposed_raw_input, $expected);
  }

}
