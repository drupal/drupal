<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\views\Views;

/**
 * Tests the UI of views when using the table style.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\style\Table.
 */
class StyleTableTest extends UITestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests created a table style view.
   */
  public function testWizard() {
    // Create a new view and check that the first field has a label.
    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = $this->randomMachineName(16);
    $view['show[wizard_key]'] = 'node';
    $view['page[create]'] = TRUE;
    $view['page[style][style_plugin]'] = 'table';
    $view['page[title]'] = $this->randomMachineName(16);
    $view['page[path]'] = $view['id'];
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($view, 'Save and edit');

    $view = Views::getView($view['id']);
    $view->initHandlers();
    $this->assertEquals('Title', $view->field['title']->options['label'], 'The field label for table styles is not empty.');
  }

}
