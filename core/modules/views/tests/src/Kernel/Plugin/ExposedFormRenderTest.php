<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Component\Utility\Html;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the exposed form markup.
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
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
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
    $result = $this->xpath('//form//div[contains(@id, :id) and normalize-space(text())=:description]', [':id' => 'edit-type--description', ':description' => t('Exposed description')]);
    $this->assertEqual(count($result), 1, 'Filter description was found.');
  }

}
