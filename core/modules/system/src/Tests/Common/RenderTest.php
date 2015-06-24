<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\RenderTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\simpletest\KernelTestBase;

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
  public static $modules = array('system', 'common_test');

  /**
   * Tests theme preprocess functions being able to attach assets.
   */
  function testDrupalRenderThemePreprocessAttached() {
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
   * Tests drupal_process_attached().
   */
  public function testDrupalProcessAttached() {
    // Specify invalid attachments in a render array.
    $build['#attached']['library'][] = 'core/drupal.states';
    $build['#attached']['drupal_process_states'][] = [];
    try {
      drupal_process_attached($build);
      $this->fail("Invalid #attachment 'drupal_process_states' allowed");
    }
    catch (\Exception $e) {
      $this->pass("Invalid #attachment 'drupal_process_states' not allowed");
    }
  }

}
