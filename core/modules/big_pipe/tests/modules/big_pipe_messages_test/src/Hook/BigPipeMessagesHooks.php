<?php

declare(strict_types=1);

namespace Drupal\big_pipe_messages_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Security\Attribute\TrustedCallback;

/**
 * Hook implementations for big_pipe_test.
 */
class BigPipeMessagesHooks {

  /**
   * Implements hook_element_info_alter().
   */
  #[Hook('element_info_alter')]
  public function elementInfoAlter(array &$info): void {
    $info['status_messages']['#pre_render'][] = static::class . '::preRenderMessages';
  }

  /**
   * Pre render callback.
   *
   * Removes #placeholder_strategy from the messages element to force the
   * messages placeholder to go via the big pipe strategy for testing purposes.
   */
  #[TrustedCallback]
  public static function preRenderMessages(array $element): array {
    if (isset($element['#attached']['placeholders'])) {
      $key = key($element['#attached']['placeholders']);
      unset($element['#attached']['placeholders'][$key]['#placeholder_strategy_denylist']);
    }
    if (isset($element['messages']['#attached']['placeholders'])) {
      $key = key($element['messages']['#attached']['placeholders']);
      unset($element['messages']['#attached']['placeholders'][$key]['#placeholder_strategy_denylist']);
    }
    return $element;
  }

}
