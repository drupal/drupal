<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\StatusMessages.
 */

namespace Drupal\Core\Render\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;

/**
 * Provides a messages element.
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
    ];
  }

  /**
   * #pre_render callback to generate a placeholder.
   *
   * Ensures the same token is used for all instances, hence resulting in the
   * same placeholder for all places rendering the status messages for this
   * request (e.g. in multiple blocks). This ensures we can put the rendered
   * messages in all placeholders in one go.
   * Also ensures the same context key is used for the #post_render_cache
   * property, this ensures that if status messages are rendered multiple times,
   * their individual (but identical!) #post_render_cache properties are merged,
   * ensuring the callback is only invoked once.
   *
   * @see ::renderMessages()
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   The updated renderable array containing the placeholder.
   */
  public static function generatePlaceholder(array $element) {
    $plugin_id = 'status_messages';

    $callback = get_class() . '::renderMessages';
    try {
      $hash_salt = Settings::getHashSalt();
    }
    catch (\RuntimeException $e) {
      // Status messages are also shown during the installer, at which time no
      // hash salt is defined yet.
      $hash_salt = Crypt::randomBytes(8);
    }
    $key = $plugin_id . $element['#display'];
    $context = [
      'display' => $element['#display'],
      'token' => Crypt::hmacBase64($key, $hash_salt),
    ];
    $placeholder = static::renderer()->generateCachePlaceholder($callback, $context);
    $element['#post_render_cache'] = [
      $callback => [
        $key => $context,
      ],
    ];
    $element['#markup'] = $placeholder;

    return $element;
  }

  /**
   * #post_render_cache callback; replaces placeholder with messages.
   *
   * Note: this is designed to replace all #post_render_cache placeholders for
   *   messages in a single #post_render_cache callback; hence all placeholders
   *   must be identical.
   *
   * @see ::getInfo()
   *
   * @param array $element
   *   The renderable array that contains the to be replaced placeholder.
   * @param array $context
   *   An array with any context information.
   *
   * @return array
   *   A renderable array containing the messages.
   */
  public static function renderMessages(array $element, array $context) {
    $renderer = static::renderer();

    // Render the messages.
    $messages = [
      '#theme' => 'status_messages',
      // @todo Improve when https://www.drupal.org/node/2278383 lands.
      '#message_list' => drupal_get_messages($context['display']),
      '#status_headings' => [
        'status' => t('Status message'),
        'error' => t('Error message'),
        'warning' => t('Warning message'),
      ],
    ];
    $markup = $renderer->render($messages);

    // Replace the placeholder.
    $callback = get_class() . '::renderMessages';
    $placeholder = $renderer->generateCachePlaceholder($callback, $context);
    $element['#markup'] = str_replace($placeholder, $markup, $element['#markup']);
    $element = $renderer->mergeBubbleableMetadata($element, $messages);

    return $element;
  }

  /**
   * Wraps the renderer.
   *
   * @return \Drupal\Core\Render\RendererInterface
   */
  protected static function renderer() {
    return \Drupal::service('renderer');
  }

}
