<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Component\Utility\Xss;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\field\Custom handler.
 *
 * @group views
 */
class FieldCustomTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  public function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['field']['id'] = 'custom';
    return $data;
  }

  /**
   * Ensure that custom fields work and doesn't escape unnecessary markup.
   */
  public function testFieldCustom() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Alter the text of the field to a random string.
    $random = '<div>' . $this->randomMachineName() . '</div>';
    $view->displayHandlers->get('default')->overrideOption('fields', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'alter' => [
          'text' => $random,
        ],
      ],
    ]);

    $this->executeView($view);

    $this->assertEqual($random, $view->style_plugin->getField(0, 'name'));
  }

  /**
   * Ensure that custom fields can use tokens.
   */
  public function testFieldCustomTokens() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', [
      'age' => [
        'id' => 'age',
        'exclude' => TRUE,
        'table' => 'views_test_data',
        'field' => 'age',
      ],
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'alter' => [
          'text' => 'Amount of kittens: {{ age }}',
        ],
      ],
    ]);

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $preview = $view->preview();
    $output = $renderer->renderRoot($preview);

    $expected_text = 'Amount of kittens: ' . $view->style_plugin->getField(0, 'age');
    $this->assertStringContainsString($expected_text, (string) $output, 'The views token has been successfully replaced.');
  }

  /**
   * Ensure that custom field content is XSS filtered.
   */
  public function testCustomFieldXss() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Alter the text of the field to include XSS.
    $text = '<script>alert("kittens")</script>';
    $view->displayHandlers->get('default')->overrideOption('fields', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'alter' => [
          'text' => $text,
        ],
      ],
    ]);
    $this->executeView($view);
    $this->assertEqual(Xss::filter($text), $view->style_plugin->getField(0, 'name'));
  }

}
