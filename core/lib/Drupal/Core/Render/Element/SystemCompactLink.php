<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Link as BaseLink;
use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Url as BaseUrl;
use Drupal\Component\Utility\NestedArray;

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
    $class = static::class;
    return [
      '#pre_render' => [
        [$class, 'preRenderCompactLink'],
        [$class, 'preRenderLink'],
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
    // By default, link options to pass to l() are normally set in #options.
    $element += ['#options' => []];

    if (system_admin_compact_mode()) {
      $element['#title'] = t('Show descriptions');
      $element['#url'] = BaseUrl::fromRoute('system.admin_compact_page', ['mode' => 'off']);
      $element['#options'] = [
        'attributes' => ['title' => t('Expand layout to include descriptions.')],
        'query' => \Drupal::destination()->getAsArray(),
      ];
    }
    else {
      $element['#title'] = t('Hide descriptions');
      $element['#url'] = BaseUrl::fromRoute('system.admin_compact_page', ['mode' => 'on']);
      $element['#options'] = [
        'attributes' => ['title' => t('Compress layout by hiding descriptions.')],
        'query' => \Drupal::destination()->getAsArray(),
      ];
    }

    $options = NestedArray::mergeDeep($element['#url']->getOptions(), $element['#options']);
    $element['#markup'] = BaseLink::fromTextAndUrl($element['#title'], $element['#url']->setOptions($options))->toString();

    return $element;
  }

}
