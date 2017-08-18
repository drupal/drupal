<?php

namespace Drupal\Tests\layout_discovery\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Layout functionality.
 *
 * @group Layout
 */
class LayoutTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'layout_discovery', 'layout_test'];

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->layoutPluginManager = $this->container->get('plugin.manager.core.layout');
  }

  /**
   * Test rendering a layout.
   *
   * @dataProvider renderLayoutData
   */
  public function testRenderLayout($layout_id, $config, $regions, array $html) {
    $layout = $this->layoutPluginManager->createInstance($layout_id, $config);
    $built['layout'] = $layout->build($regions);
    $built['layout']['#prefix'] = 'Test prefix' . "\n";
    $built['layout']['#suffix'] = 'Test suffix' . "\n";

    // Assume each layout is contained by a form, in order to ensure the
    // building of the layout does not interfere with form processing.
    $form_state = new FormState();
    $form_builder = $this->container->get('form_builder');
    $form_builder->prepareForm('the_form_id', $built, $form_state);
    $form_builder->processForm('the_form_id', $built, $form_state);

    $this->render($built);

    // Add in the wrapping form elements and prefix/suffix.
    array_unshift($html, 'Test prefix');
    array_unshift($html, '<form data-drupal-selector="the-form-id" action="/" method="post" id="the-form-id" accept-charset="UTF-8">');
    // Retrieve the build ID from the rendered HTML since the string is random.
    $build_id_input = $this->cssSelect('input[name="form_build_id"]')[0]->asXML();
    $form_id_input = '<input data-drupal-selector="edit-the-form-id" type="hidden" name="form_id" value="the_form_id"/>';
    $html[] = 'Test suffix';
    $html[] = $build_id_input . $form_id_input . '</form>';

    // Match the HTML to the full form element.
    $this->assertSame(implode("\n", $html), $this->cssSelect('#the-form-id')[0]->asXML());
  }

  /**
   * {@inheritdoc}
   */
  protected function render(array &$elements) {
    $content = parent::render($elements);
    // Strip leading whitespace from every line.
    $this->content = preg_replace('/^\s+/m', '', $content);
    return $this->content;
  }

  /**
   * Data provider for testRenderLayout().
   */
  public function renderLayoutData() {
    $html = [];
    $html[] = '<div data-drupal-selector="edit-layout" class="layout layout--onecol">';
    $html[] = '<div data-drupal-selector="edit-content" class="layout__region layout__region--content">';
    $html[] = 'This is the content';
    $html[] = '</div>';
    $html[] = '</div>';
    $data['layout_onecol'] = [
      'layout_onecol',
      [],
      [
        'content' => [
          '#markup' => 'This is the content',
        ],
      ],
      $html,
    ];

    $html = [];
    $html[] = '<div data-drupal-selector="edit-layout" class="layout-example-1col clearfix">';
    $html[] = '<div data-drupal-selector="edit-top" class="region-top">';
    $html[] = 'This string added by #process.';
    $html[] = '</div>';
    $html[] = '<div data-drupal-selector="edit-bottom" class="region-bottom">';
    $html[] = 'This is the bottom';
    $html[] = '</div>';
    $html[] = '</div>';
    $data['layout_test_1col_with_form'] = [
      'layout_test_1col',
      [],
      [
        'top' => [
          '#process' => [[static::class, 'processCallback']],
        ],
        'bottom' => [
          '#markup' => 'This is the bottom',
        ],
      ],
      $html,
    ];

    $html = [];
    $html[] = '<div data-drupal-selector="edit-layout" class="layout-example-1col clearfix">';
    $html[] = '<div data-drupal-selector="edit-top" class="region-top">';
    $html[] = 'This is the top';
    $html[] = '</div>';
    $html[] = '<div data-drupal-selector="edit-bottom" class="region-bottom">';
    $html[] = 'This is the bottom';
    $html[] = '</div>';
    $html[] = '</div>';
    $data['layout_test_1col'] = [
      'layout_test_1col',
      [],
      [
        'top' => [
          '#markup' => 'This is the top',
        ],
        'bottom' => [
          '#markup' => 'This is the bottom',
        ],
      ],
      $html,
    ];

    $html = [];
    $html[] = '<div data-drupal-selector="edit-layout" class="layout layout--layout-test-1col-no-template">';
    $html[] = '<div data-drupal-selector="edit-top" class="layout__region layout__region--top">';
    $html[] = 'This is the top';
    $html[] = '</div>';
    $html[] = '<div data-drupal-selector="edit-bottom" class="layout__region layout__region--bottom">';
    $html[] = 'This is the bottom';
    $html[] = '</div>';
    $html[] = '</div>';
    $data['layout_test_1col_no_template'] = [
      'layout_test_1col_no_template',
      [],
      [
        'top' => [
          '#markup' => 'This is the top',
        ],
        'bottom' => [
          '#markup' => 'This is the bottom',
        ],
      ],
      $html,
    ];

    $html = [];
    $html[] = '<div data-drupal-selector="edit-layout" class="layout-example-2col clearfix">';
    $html[] = '<div data-drupal-selector="edit-left" class="class-added-by-preprocess region-left">';
    $html[] = 'This is the left';
    $html[] = '</div>';
    $html[] = '<div data-drupal-selector="edit-right" class="region-right">';
    $html[] = 'This is the right';
    $html[] = '</div>';
    $html[] = '</div>';
    $data['layout_test_2col'] = [
      'layout_test_2col',
      [],
      [
        'left' => [
          '#markup' => 'This is the left',
        ],
        'right' => [
          '#markup' => 'This is the right',
        ],
      ],
      $html,
    ];

    $html = [];
    $html[] = '<div data-drupal-selector="edit-layout" class="layout-test-plugin clearfix">';
    $html[] = '<div>';
    $html[] = '<span class="setting-1-label">Blah: </span>';
    $html[] = 'Config value';
    $html[] = '</div>';
    $html[] = '<div data-drupal-selector="edit-main" class="region-main">';
    $html[] = 'Main region';
    $html[] = '</div>';
    $html[] = '</div>';
    $data['layout_test_plugin'] = [
      'layout_test_plugin',
      [
        'setting_1' => 'Config value',
      ],
      [
        'main' => [
          '#markup' => 'Main region',
        ],
      ],
      $html,
    ];

    return $data;
  }

  /**
   * Provides a test #process callback.
   */
  public static function processCallback($element) {
    $element['#markup'] = 'This string added by #process.';
    return $element;
  }

}
