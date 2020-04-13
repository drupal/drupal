<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the generic entity area handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\Entity
 */
class AreaEmptyTest extends ViewsKernelTestBase {

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
   * {@inheritdoc}
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views']['test_example'] = [
      'title' => 'Test Example area',
      'help' => 'A area handler which just exists for tests.',
      'area' => [
        'id' => 'test_example',
      ],
    ];

    return $data;
  }

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_example_area'];

  /**
   * Tests that the header and footer areas are not rendered if empty.
   */
  public function testRenderEmptyHeaderFooter() {
    $view = Views::getView('test_example_area');
    $view->initHandlers();

    // Set example empty text.
    $empty_text = $this->randomMachineName();
    $empty_header = $this->randomMachineName();
    $empty_footer = $this->randomMachineName();

    // Set empty text.
    $view->empty['test_example']->options['string'] = '<p>' . $empty_text . '</p>';
    // Set example header text.
    $view->header['test_example']->options['string'] = '<p>' . $empty_header . '</p>';
    // Set example footer text.
    $view->footer['test_example']->options['string'] = '<p>' . $empty_footer . '</p>';

    // Verify that the empty header and footer sections have not been rendered.
    $view->setDisplay('default');
    $this->executeView($view);
    $output = $view->render();
    $output = \Drupal::service('renderer')->renderRoot($output);
    $this->setRawContent($output);
    $this->assertText($empty_text);
    $this->assertNoText($empty_header);
    $this->assertNoText($empty_footer);

    // Enable displaying the header and footer when the View is empty.
    $view->header['test_example']->options['empty'] = TRUE;
    $view->footer['test_example']->options['empty'] = TRUE;

    // Verify that the header and footer sections have been rendered.
    $this->executeView($view);
    $output = $view->render();
    $output = \Drupal::service('renderer')->renderRoot($output);
    $this->setRawContent($output);
    $this->assertText($empty_header);
    $this->assertText($empty_footer);
  }

}
