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
  public static $modules = ['system', 'common_test'];

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
      ]
    ];
    $this->assertEqual($expected_attached, $test_element['#attached'], 'All expected assets from theme preprocess hooks attached.');

    \Drupal::state()->set('theme_preprocess_attached_test', FALSE);
  }

  /**
   * Tests that we get an exception when we try to attach an illegal type.
   */
  public function testProcessAttached() {
    // Specify invalid attachments in a render array.
    $build['#attached']['library'][] = 'core/drupal.states';
    $build['#attached']['drupal_process_states'][] = [];
    $renderer = $this->container->get('bare_html_page_renderer');
    try {
      $renderer->renderBarePage($build, '', 'maintenance_page');
      $this->fail("Invalid #attachment 'drupal_process_states' allowed");
    }
    catch (\LogicException $e) {
      $this->pass("Invalid #attachment 'drupal_process_states' not allowed");
    }
  }

}
