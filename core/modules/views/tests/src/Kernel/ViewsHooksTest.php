<?php

namespace Drupal\Tests\views\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Render\RenderContext;
use Drupal\views\Form\ViewsFormMainForm;
use Drupal\views\Views;

/**
 * Tests that views hooks are registered when defined in $module.views.inc.
 *
 * @group views
 * @see views_hook_info().
 * @see field_hook_info().
 */
class ViewsHooksTest extends ViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * An array of available views hooks to test.
   *
   * @var array
   */
  protected static $hooks = [
    'views_data' => 'all',
    'views_data_alter' => 'alter',
    'views_query_substitutions' => 'view',
    'views_form_substitutions' => 'view',
    'views_analyze' => 'view',
    'views_pre_view' => 'view',
    'views_pre_build' => 'view',
    'views_post_build' => 'view',
    'views_pre_execute' => 'view',
    'views_post_execute' => 'view',
    'views_pre_render' => 'view',
    'views_post_render' => 'view',
    'views_query_alter'  => 'view',
    'views_invalidate_cache' => 'all',
  ];

  /**
   * The module handler to use for invoking hooks.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->moduleHandler = $this->container->get('module_handler');
  }

  /**
   * Tests the hooks.
   */
  public function testHooks() {
    $view = Views::getView('test_view');
    $view->setDisplay();

    // Test each hook is found in the implementations array and is invoked.
    foreach (static::$hooks as $hook => $type) {
      $this->assertTrue($this->moduleHandler->hasImplementations($hook, 'views_test_data'), new FormattableMarkup('The hook @hook was registered.', ['@hook' => $hook]));

      if ($hook == 'views_post_render') {
        $this->moduleHandler->invoke('views_test_data', $hook, [$view, &$view->display_handler->output, $view->display_handler->getPlugin('cache')]);
        continue;
      }

      switch ($type) {
        case 'view':
          $this->moduleHandler->invoke('views_test_data', $hook, [$view]);
          break;

        case 'alter':
          $data = [];
          $this->moduleHandler->alter($hook, $data);
          break;

        default:
          $this->moduleHandler->invoke('views_test_data', $hook);
      }

      $this->assertTrue($this->container->get('state')->get('views_hook_test_' . $hook), new FormattableMarkup('The %hook hook was invoked.', ['%hook' => $hook]));
      // Reset the module implementations cache, so we ensure that the
      // .views.inc file is loaded actively.
      $this->moduleHandler->resetImplementations();
    }
  }

  /**
   * Tests how hook_views_form_substitutions() makes substitutions.
   *
   * @see views_test_data_views_form_substitutions()
   * @see \Drupal\views\Form\ViewsFormMainForm::preRenderViewsForm()
   */
  public function testViewsFormMainFormPreRender() {
    $element = [
      'output' => [
        '#plain_text' => '<!--will-be-escaped--><!--will-be-not-escaped-->',
      ],
      '#substitutions' => ['#value' => []],
    ];
    $element = \Drupal::service('renderer')->executeInRenderContext(new RenderContext(), function () use ($element) {
      return ViewsFormMainForm::preRenderViewsForm($element);
    });
    $this->setRawContent((string) $element['output']['#markup']);
    $this->assertEscaped('<em>escaped</em>');
    $this->assertRaw('<em>unescaped</em>');
  }

}
