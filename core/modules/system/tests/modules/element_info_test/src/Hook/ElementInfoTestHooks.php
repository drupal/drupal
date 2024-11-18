<?php

declare(strict_types=1);

namespace Drupal\element_info_test\Hook;

use Drupal\element_info_test\ElementInfoTestNumberBuilder;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for element_info_test.
 */
class ElementInfoTestHooks {

  /**
   * Implements hook_element_info_alter().
   */
  #[Hook('element_info_alter')]
  public function elementInfoAlter(array &$info): void {
    $info['number'] += ['#pre_render' => []];
    /* @see \Drupal\KernelTests\Core\Render\Element\WeightTest::testProcessWeightSelectMax() */
    $info['number']['#pre_render'][] = [ElementInfoTestNumberBuilder::class, 'preRender'];
  }

  /**
   * Implements hook_element_plugin_alter().
   */
  #[Hook('element_plugin_alter')]
  public function elementPluginAlter(array &$definitions): void {
    if (\Drupal::state()->get('hook_element_plugin_alter:remove_weight', FALSE)) {
      unset($definitions['weight']);
    }
  }

}
