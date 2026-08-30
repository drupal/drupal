<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Url;

/**
 * Provides a link to show or hide help text on administration pages.
 *
 * Usage example:
 * @code
 * $form['system_compact_link'] = [
 *   '#type' => 'system_compact_link',
 * ];
 * @endcode
 */
#[RenderElement('system_compact_link')]
class SystemCompactLink extends Link {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        [static::class, 'preRenderCompactLink'],
        [static::class, 'preRenderLink'],
      ],
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => ['class' => ['compact-link']],
        ],
      ],
    ];
  }

  /**
   * Pre-render callback: Renders a link into #markup.
   *
   * Doing so during pre_render gives modules a chance to alter the link parts.
   *
   * @param array $element
   *   A structured array whose keys form the arguments to Drupal::l():
   *   - #title: The link text to pass as argument to Drupal::l().
   *   - One of the following:
   *     - #route_name and (optionally) a #route_parameters array; The route
   *       name and route parameters which will be passed into the link
   *       generator.
   *     - #href: The system path or URL to pass as argument to Drupal::l().
   *   - #options: (optional) An array of options to pass to Drupal::l() or the
   *     link generator.
   *
   * @return array
   *   The passed-in element containing the system compact link default values.
   */
  public static function preRenderCompactLink($element) {
    $element['#title'] = t('Hide descriptions');
    $element['#url'] = Url::fromUri('internal:#?');
    $element['#options'] = [
      'attributes' => [
        'title' => t('Compress layout by hiding descriptions.'),
        'data-admin-compact-toggle' => TRUE,
      ],
    ];
    $element['#attached']['library'][] = 'system/drupal.system.admin_compact';
    return $element;
  }

}
