<?php

namespace Drupal\Core\Menu;

/**
 * Menu theme preprocess.
 *
 * @internal
 */
class MenuPreprocess {

  /**
   * Prepares variables for single local task link templates.
   *
   * Default template: menu-local-task.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: A render element containing:
   *     - #link: A menu link array with 'title', 'url', and (optionally)
   *       'localized_options' keys.
   *     - #active: A boolean indicating whether the local task is active.
   */
  public function preprocessMenuLocalTask(array &$variables): void {
    $link = $variables['element']['#link'];
    $link += [
      'localized_options' => [],
    ];
    $link_text = $link['title'];

    if (!empty($variables['element']['#active'])) {
      $variables['is_active'] = TRUE;
    }

    $link['localized_options']['set_active_class'] = TRUE;

    $variables['link'] = [
      '#type' => 'link',
      '#title' => $link_text,
      '#url' => $link['url'],
      '#options' => $link['localized_options'],
    ];
  }

  /**
   * Prepares variables for single local action link templates.
   *
   * Default template: menu-local-action.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - element: A render element containing:
   *     - #link: A menu link array with 'title', 'url', and (optionally)
   *       'localized_options' keys.
   */
  public function preprocessMenuLocalAction(array &$variables): void {
    $link = $variables['element']['#link'];
    $link += [
      'localized_options' => [],
    ];
    $link['localized_options']['attributes']['class'][] = 'button';
    $link['localized_options']['attributes']['class'][] = 'button-action';
    $link['localized_options']['set_active_class'] = TRUE;

    $variables['link'] = [
      '#type' => 'link',
      '#title' => $link['title'],
      '#options' => $link['localized_options'],
      '#url' => $link['url'],
    ];
  }

}
