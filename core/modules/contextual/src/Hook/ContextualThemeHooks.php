<?php

namespace Drupal\contextual\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;

/**
 * Hook implementations for contextual.
 */
class ContextualThemeHooks {

  public function __construct(
    protected readonly AccountInterface $currentUser,
  ) {}

  /**
   * Implements hook_preprocess().
   *
   * @see \Drupal\contextual\Element\ContextualLinksPlaceholder
   * @see contextual_page_attachments()
   * @see \Drupal\contextual\ContextualController::render()
   */
  #[Hook('preprocess')]
  public function preprocess(&$variables, $hook, $info): void {
    // Determine the primary theme function argument.
    if (!empty($info['variables'])) {
      $keys = array_keys($info['variables']);
      $key = $keys[0];
    }
    elseif (!empty($info['render element'])) {
      $key = $info['render element'];
    }
    if (!empty($key) && isset($variables[$key])) {
      $element = $variables[$key];
    }

    if (isset($element) && is_array($element) && !empty($element['#contextual_links'])) {
      $variables['#cache']['contexts'][] = 'user.permissions';
      if ($this->currentUser->hasPermission('access contextual links')) {
        // Mark this element as potentially having contextual links attached to
        // it.
        $variables['attributes']['class'][] = 'contextual-region';

        // Renders a contextual links placeholder unconditionally, thus not
        // breaking the render cache. Although the empty placeholder is rendered
        // for all users, contextual_page_attachments() only adds the asset
        // library for users with the 'access contextual links' permission, thus
        // preventing unnecessary HTTP requests for users without that
        // permission.
        $variables['title_suffix']['contextual_links'] = [
          '#type' => 'contextual_links_placeholder',
          '#id' => _contextual_links_to_id($element['#contextual_links']),
        ];
      }
    }
  }

}
