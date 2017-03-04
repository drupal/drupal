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
  public function testRenderLayout($layout_id, $config, $regions, $html) {
    $layout = $this->layoutPluginManager->createInstance($layout_id, $config);
    $built['layout'] = $layout->build($regions);
    $built['layout']['#prefix'] = 'Test prefix';
    $built['layout']['#suffix'] = 'Test suffix';
    $html = 'Test prefix' . $html . "\n" . 'Test suffix';

    // Assume each layout is contained by a form, in order to ensure the
    // building of the layout does not interfere with form processing.
    $form_state = new FormState();
    $form_builder = $this->container->get('form_builder');
    $form_builder->prepareForm('the_form_id', $built, $form_state);
    $form_builder->processForm('the_form_id', $built, $form_state);

    $this->render($built);
    $this->assertRaw($html);
    $this->assertRaw('<input data-drupal-selector="edit-the-form-id" type="hidden" name="form_id" value="the_form_id" />');
  }

  /**
   * Data provider for testRenderLayout().
   */
  public function renderLayoutData() {
    $data['layout_onecol'] = [
      'layout_onecol',
      [],
      [
        'content' => [
          '#markup' => 'This is the content',
        ],
      ],
    ];
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
    ];

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
    ];

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
    ];

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
    ];

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
    ];

    $data['layout_onecol'][] = <<<'EOD'
<div data-drupal-selector="edit-layout" class="layout--onecol">
  <div class="layout-region layout-region--content">
    This is the content
  </div>
</div>
EOD;

    $data['layout_test_1col_with_form'][] = <<<'EOD'
<div class="layout-example-1col clearfix">
  <div class="region-top">
    This string added by #process.
  </div>
  <div class="region-bottom">
    This is the bottom
  </div>
</div>
EOD;

    $data['layout_test_1col'][] = <<<'EOD'
<div class="layout-example-1col clearfix">
  <div class="region-top">
    This is the top
  </div>
  <div class="region-bottom">
    This is the bottom
  </div>
</div>
EOD;

    $data['layout_test_1col_no_template'][] = <<<'EOD'
<div data-drupal-selector="edit-layout" class="layout--layout-test-1col-no-template">
  <div class="region--top">
    This is the top
  </div>
  <div class="region--bottom">
    This is the bottom
  </div>
</div>
EOD;

    $data['layout_test_2col'][] = <<<'EOD'
<div class="layout-example-2col clearfix">
  <div class="region-left">
    This is the left
  </div>
  <div class="region-right">
    This is the right
  </div>
</div>
EOD;

    $data['layout_test_plugin'][] = <<<'EOD'
<div class="layout-test-plugin clearfix">
  <div>
    <span class="setting-1-label">Blah: </span>
    Config value
  </div>
  <div class="region-main">
    Main region
  </div>
</div>
EOD;

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
