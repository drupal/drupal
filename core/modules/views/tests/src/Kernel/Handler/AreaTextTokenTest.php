<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the token in text area handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\Text
 */
class AreaTextTokenTest extends ViewsKernelTestBase {

  protected static $modules = ['system', 'user', 'filter'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installConfig(['system', 'filter']);
    $this->installEntitySchema('user');
  }

  /**
   * Tests the token into text area plugin within header.
   */
  public function testAreaTextToken(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Enable checkbox 'token replacement', add token into href in text header.
    $string = '<a href="[site:url]">Added Site URL token in href</a>';
    $view->displayHandlers->get('default')->overrideOption('header', [
      'area' => [
        'id' => 'area',
        'table' => 'views',
        'field' => 'area',
        'content' => [
          'value' => $string,
        ],
        'tokenize' => TRUE,
      ],
    ]);

    // Execute the view.
    $this->executeView($view);

    $build = $view->display_handler->handlers['header']['area']->render();
    $replaced_token = \Drupal::token()->replace('[site:url]');
    $desired_output = str_replace('[site:url]', $replaced_token, $string);
    $this->assertEquals(check_markup($desired_output), $renderer->renderRoot($build), 'Global token assessed in href');
  }

}
