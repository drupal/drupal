<?php

namespace Drupal\Core\Render\Element;

/**
 * Provides a messages element.
 *
 * Used to display results of \Drupal::messenger()->addMessage() calls.
 *
 * Usage example:
 * @code
 * $build['status_messages'] = [
 *   '#type' => 'status_messages',
 * ];
 * @endcode
 *
 * @RenderElement("status_messages")
 */
class StatusMessages extends RenderElement {

  /**
   * {@inheritdoc}
   *
   * Generate the placeholder in a #pre_render callback, because the hash salt
   * needs to be accessed, which may not yet be available when this is called.
   */
  public function getInfo() {
    return [
      // May have a value of 'status' or 'error' when only displaying messages
      // of that specific type.
      '#display' => NULL,
      '#pre_render' => [
        get_class() . '::generatePlaceholder',
      ],
      '#include_fallback' => FALSE,
    ];
  }

  /**
   * #pre_render callback to generate a placeholder.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   The updated renderable array containing the placeholder.
   */
  public static function generatePlaceholder(array $element) {
    $build = [
      '#lazy_builder' => [get_class() . '::renderMessages', [$element['#display']]],
      '#create_placeholder' => TRUE,
    ];

    // Directly create a placeholder as we need this to be placeholdered
    // regardless if this is a POST or GET request.
    // @todo remove this when https://www.drupal.org/node/2367555 lands.
    $build = \Drupal::service('render_placeholder_generator')->createPlaceholder($build);

    if ($element['#include_fallback']) {
      return [
        'fallback' => [
          '#markup' => '<div data-drupal-messages-fallback class="hidden"></div>',
        ],
        'messages' => $build,
      ];
    }
    return $build;
  }

  /**
   * #lazy_builder callback; replaces placeholder with messages.
   *
   * @param string|null $type
   *   Limit the messages returned by type. Defaults to NULL, meaning all types.
   *   Passed on to \Drupal\Core\Messenger\Messenger::deleteByType(). These
   *   values are supported:
   *   - NULL
   *   - 'status'
   *   - 'warning'
   *   - 'error'
   *
   * @return array
   *   A renderable array containing the messages.
   *
   * @see \Drupal\Core\Messenger\Messenger::deleteByType()
   */
  public static function renderMessages($type = NULL) {
    $render = [];
    if (isset($type)) {
      $messages = [
        $type => \Drupal::messenger()->deleteByType($type),
      ];
    }
    else {
      $messages = \Drupal::messenger()->deleteAll();
    }

    if ($messages) {
      // Render the messages.
      $render = [
        '#theme' => 'status_messages',
        '#message_list' => $messages,
        '#status_headings' => [
          'status' => t('Status message'),
          'error' => t('Error message'),
          'warning' => t('Warning message'),
        ],
      ];
    }
    return $render;
  }

}
