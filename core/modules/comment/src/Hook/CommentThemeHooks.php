<?php

namespace Drupal\comment\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for comment.
 */
class CommentThemeHooks {

  /**
   * Implements hook_preprocess_HOOK() for block templates.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    if ($variables['configuration']['provider'] == 'comment') {
      $variables['attributes']['role'] = 'navigation';
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for field templates.
   *
   * Prepares variables for comment field templates.
   *
   * Default template: field--comment.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: An associative array containing render arrays for the list of
   *     comments, and the comment form. Array keys: comments, comment_form.
   *
   * @todo Rename to template_preprocess_field__comment() once
   *   https://www.drupal.org/node/939462 is resolved.
   */
  #[Hook('preprocess_field')]
  public function preprocessField(&$variables): void {
    $element = $variables['element'];
    if ($element['#field_type'] == 'comment') {
      // Provide contextual information.
      $variables['comment_display_mode'] = $element[0]['#comment_display_mode'];
      $variables['comment_type'] = $element[0]['#comment_type'];

      // Append additional attributes from the first field item.
      $variables['attributes'] += $variables['items'][0]['attributes']->storage();

      // Create separate variables for the comments and comment form.
      $variables['comments'] = $element[0]['comments'];
      $variables['comment_form'] = $element[0]['comment_form'];
    }
  }

}
