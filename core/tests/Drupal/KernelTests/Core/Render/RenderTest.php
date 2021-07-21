<?php

namespace Drupal\KernelTests\Core\Render;

use Drupal\KernelTests\KernelTestBase;

/**
 * Performs functional tests on drupal_render().
 *
 * @group Common
 */
class RenderTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'common_test', 'theme_test'];

  /**
   * Tests theme preprocess functions being able to attach assets.
   */
  public function testDrupalRenderThemePreprocessAttached() {
    \Drupal::state()->set('theme_preprocess_attached_test', TRUE);

    $test_element = [
      '#theme' => 'common_test_render_element',
      'foo' => [
        '#markup' => 'Kittens!',
      ],
    ];
    \Drupal::service('renderer')->renderRoot($test_element);

    $expected_attached = [
      'library' => [
        'test/generic_preprocess',
        'test/specific_preprocess',
      ],
    ];
    $this->assertEquals($expected_attached, $test_element['#attached'], 'All expected assets from theme preprocess hooks attached.');

    \Drupal::state()->set('theme_preprocess_attached_test', FALSE);
  }

  /**
   * Ensures that render array children are processed correctly.
   */
  public function testRenderChildren() {
    // Ensure that #prefix and #suffix is only being printed once since that is
    // the behavior the caller code expects.
    $build = [
      '#type' => 'container',
      '#theme' => 'theme_test_render_element_children',
      '#prefix' => 'kangaroo',
      '#suffix' => 'kitten',
    ];
    $this->render($build);
    $this->removeWhiteSpace();
    $this->assertNoRaw('<div>kangarookitten</div>');
  }

  /**
   * Ensures that render array element context is processed correctly.
   */
  public function testRenderElementContext() {
    $build = [
      '#type' => 'theme_test_render_element_context',
      '#context' => ['foo' => 'Has render element context'],
    ];
    $this->render($build);
    $this->removeWhiteSpace();
    $this->assertRaw('<div data-render-element-context="Has render element context"></div>');
  }

  /**
   * Ensures that theme hook variable context is processed correctly.
   */
  public function testRenderThemeHookVariableContext() {
    $build = [
      '#theme' => 'theme_test_theme_hook_variable_context',
      '#context' => ['foo' => 'Has theme hook variable context'],
    ];
    $this->render($build);
    $this->removeWhiteSpace();
    $this->assertRaw('<div data-theme-hook-variable-context="Has theme hook variable context"></div>');
  }

  /**
   * Tests that we get an exception when we try to attach an illegal type.
   */
  public function testProcessAttached() {
    // Specify invalid attachments in a render array.
    $build['#attached']['library'][] = 'core/drupal.states';
    $build['#attached']['drupal_process_states'][] = [];
    $renderer = $this->container->get('bare_html_page_renderer');
    $this->expectException(\LogicException::class);
    $renderer->renderBarePage($build, '', 'maintenance_page');
  }

}
