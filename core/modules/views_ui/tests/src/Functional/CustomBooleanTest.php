<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\views\Views;

/**
 * Tests the UI and functionality for the Custom boolean field handler options.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\field\Boolean
 */
class CustomBooleanTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * \Drupal\views\Tests\ViewTestBase::viewsData().
   */
  public function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['age']['field']['id'] = 'boolean';
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function dataSet() {
    $data = parent::dataSet();
    $data[0]['age'] = 0;
    $data[3]['age'] = 0;
    return $data;
  }

  /**
   * Tests the setting and output of custom labels for boolean values.
   */
  public function testCustomOption() {
    // Add the boolean field handler to the test view.
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', [
      'age' => [
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
        'plugin_id' => 'boolean',
      ],
    ]);
    $view->save();

    $this->executeView($view);

    $custom_true = 'Yay';
    $custom_false = 'Nay';

    // Set up some custom value mappings for different types.
    $custom_values = [
      'plain' => [
        'true' => $custom_true,
        'false' => $custom_false,
        'test' => 'assertStringContainsString',
      ],
      'allowed tag' => [
        'true' => '<p>' . $custom_true . '</p>',
        'false' => '<p>' . $custom_false . '</p>',
        'test' => 'assertStringContainsString',
      ],
      'disallowed tag' => [
        'true' => '<script>' . $custom_true . '</script>',
        'false' => '<script>' . $custom_false . '</script>',
        'test' => 'assertStringNotContainsString',
      ],
    ];

    // Run the same tests on each type.
    foreach ($custom_values as $type => $values) {
      $options = [
        'options[type]' => 'custom',
        'options[type_custom_true]' => $values['true'],
        'options[type_custom_false]' => $values['false'],
      ];
      $this->drupalPostForm('admin/structure/views/nojs/handler/test_view/default/field/age', $options, 'Apply');

      // Save the view.
      $this->drupalPostForm('admin/structure/views/view/test_view', [], 'Save');

      $view = Views::getView('test_view');
      $output = $view->preview();
      $output = \Drupal::service('renderer')->renderRoot($output);
      $this->{$values['test']}($values['true'], (string) $output, new FormattableMarkup('Expected custom boolean TRUE value %value in output for %type', ['%value' => $values['true'], '%type' => $type]));
      $this->{$values['test']}($values['false'], (string) $output, new FormattableMarkup('Expected custom boolean FALSE value %value in output for %type', ['%value' => $values['false'], '%type' => $type]));
    }
  }

  /**
   * Tests the setting and output of custom labels for boolean values.
   */
  public function testCustomOptionTemplate() {
    // Install theme to test with template system.
    \Drupal::service('theme_installer')->install(['views_test_theme']);

    // Set the default theme for Views preview.
    $this->config('system.theme')
      ->set('default', 'views_test_theme')
      ->save();
    $this->assertEqual('views_test_theme', $this->config('system.theme')->get('default'));

    // Add the boolean field handler to the test view.
    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('fields', [
      'age' => [
        'id' => 'age',
        'table' => 'views_test_data',
        'field' => 'age',
        'relationship' => 'none',
        'plugin_id' => 'boolean',
      ],
    ]);
    $view->save();

    $this->executeView($view);

    $custom_true = 'Yay';
    $custom_false = 'Nay';

    // Set up some custom value mappings for different types.
    $custom_values = [
      'plain' => [
        'true' => $custom_true,
        'false' => $custom_false,
        'test' => 'assertStringContainsString',
      ],
      'allowed tag' => [
        'true' => '<p>' . $custom_true . '</p>',
        'false' => '<p>' . $custom_false . '</p>',
        'test' => 'assertStringContainsString',
      ],
      'disallowed tag' => [
        'true' => '<script>' . $custom_true . '</script>',
        'false' => '<script>' . $custom_false . '</script>',
        'test' => 'assertStringNotContainsString',
      ],
    ];

    // Run the same tests on each type.
    foreach ($custom_values as $type => $values) {
      $options = [
        'options[type]' => 'custom',
        'options[type_custom_true]' => $values['true'],
        'options[type_custom_false]' => $values['false'],
      ];
      $this->drupalPostForm('admin/structure/views/nojs/handler/test_view/default/field/age', $options, 'Apply');

      // Save the view.
      $this->drupalPostForm('admin/structure/views/view/test_view', [], 'Save');

      $view = Views::getView('test_view');
      $output = $view->preview();
      $output = \Drupal::service('renderer')->renderRoot($output);
      $this->{$values['test']}($values['true'], (string) $output, new FormattableMarkup('Expected custom boolean TRUE value %value in output for %type', ['%value' => $values['true'], '%type' => $type]));
      $this->{$values['test']}($values['false'], (string) $output, new FormattableMarkup('Expected custom boolean FALSE value %value in output for %type', ['%value' => $values['false'], '%type' => $type]));

      // Assert that we are using the correct template.
      $this->assertStringContainsString('llama', (string) $output);
    }
  }

}
