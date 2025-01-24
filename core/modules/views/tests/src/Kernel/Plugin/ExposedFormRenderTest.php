<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormState;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\ExposedFormCache;
use Drupal\views\Form\ViewsExposedForm;
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
  public static $testViews = ['test_exposed_form_buttons', 'test_exposed_admin_ui'];

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
  public function testExposedFormRender(): void {
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
  public function testExposedFormRawInput(): void {
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    // Build the form state.
    $form = [];
    $view = Views::getView('test_exposed_admin_ui');
    $view->setDisplay();
    $this->executeView($view);

    $form_state = new FormState();
    $form_state->set('view', $view);
    $form_state->setValue('type', 'article');

    // Mock the exposed form.
    $exposed_form_cache = $this->createMock(ExposedFormCache::class);
    $current_path_stack = $this->createMock(CurrentPathStack::class);
    $exposed_form = new ViewsExposedForm($exposed_form_cache, $current_path_stack);
    $exposed_form->submitForm($form, $form_state);
    $updated_view = $form_state->get('view');

    $expected = [
      'type' => 'article',
    ];
    $this->assertSame($updated_view->exposed_raw_input, $expected);

    $form_state->setValue('type', ['article', 'page']);
    $exposed_form->submitForm($form, $form_state);
    $updated_view = $form_state->get('view');
    $expected = [
      'type' => [
        'article',
        'page',
      ],
    ];
    $this->assertSame($updated_view->exposed_raw_input, $expected);
  }

}
