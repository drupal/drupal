<?php

declare(strict_types=1);

namespace Drupal\claro;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;

/**
 * Helper method for claro link to action conversion.
 */
trait ClaroLinkActionTrait {

  /**
   * Converts a link render element to an action link.
   *
   * This helper merges every attributes from $link['#attributes'], from
   * $link['#options']['attributes'] and from the Url object's.
   *
   * @param array $link
   *   Link renderable array.
   * @param string|null $icon_name
   *   The name of the needed icon. When specified, a CSS class will be added
   *   with the following pattern: 'action-link--icon-[icon_name]'. If the
   *   needed icon is not implemented in CSS, no icon will be added.
   *   Currently available icons are:
   *    - checkmark,
   *    - cog,
   *    - ex,
   *    - plus,
   *    - trash.
   * @param string $size
   *   Name of the small action link variant. Defaults to 'default'.
   *   Supported sizes are:
   *    - default,
   *    - small,
   *    - extrasmall.
   * @param string $variant
   *   Variant of the action link. Supported variants are 'default' and
   *   'danger'. Defaults to 'default'.
   *
   * @return array
   *   The link renderable converted to action link.
   */
  protected function convertLinkToActionLink(array $link, $icon_name = NULL, $size = 'default', $variant = 'default'): array {
    // Early opt-out if we cannot do anything.
    if (empty($link['#type']) || $link['#type'] !== 'link' || empty($link['#url'])) {
      return $link;
    }

    // \Drupal\Core\Render\Element\Link::preRenderLink adds $link['#attributes']
    // to $link[#options]['attributes'] if it is not empty, but it does not
    // merges the 'class' subkey deeply.
    // Because of this, when $link[#options]['attributes']['class'] is set, the
    // classes defined in $link['#attributes']['class'] are ignored.
    //
    // To keep this behavior we repeat this for action-link, which means that
    // this conversion happens a bit earlier. We unset $link['#attributes'] to
    // prevent Link::preRenderLink() doing the same, because for action-links,
    // that would be needless.
    $link += ['#options' => []];
    if (isset($link['#attributes'])) {
      $link['#options'] += [
        'attributes' => [],
      ];
      $link['#options']['attributes'] += $link['#attributes'];
      unset($link['#attributes']);
    }
    $link['#options'] += ['attributes' => []];
    $link['#options']['attributes'] += ['class' => []];

    // Determine the needed (type) variant.
    $variants_supported = ['default', 'danger'];
    $variant = is_string($variant) && in_array($variant, $variants_supported) ? $variant : reset($variants_supported);

    // Remove button, button modifier CSS classes and other unwanted ones.
    $link['#options']['attributes']['class'] = array_diff($link['#options']['attributes']['class'], [
      'button',
      'button--action',
      'button--primary',
      'button--danger',
      'button--small',
      'button--extrasmall',
      'link',
    ]);

    // Adding the needed CSS classes.
    $link['#options']['attributes']['class'][] = 'action-link';

    // Add the variant-modifier CSS class only if the variant is not the
    // default.
    if ($variant !== reset($variants_supported)) {
      $link['#options']['attributes']['class'][] = Html::getClass("action-link--$variant");
    }

    // Add the icon modifier CSS class.
    if (!empty($icon_name)) {
      $link['#options']['attributes']['class'][] = Html::getClass("action-link--icon-$icon_name");
    }

    if ($size && in_array($size, ['small', 'extrasmall'])) {
      $link['#options']['attributes']['class'][] = Html::getClass("action-link--$size");
    }

    // If the provided $link is an item of the 'links' theme function, then only
    // the attributes of the Url object are processed during rendering.
    $url_attributes = $link['#url']->getOption('attributes') ?: [];
    $url_attributes = NestedArray::mergeDeep($url_attributes, $link['#options']['attributes']);
    $link['#url']->setOption('attributes', $url_attributes);

    return $link;
  }

}
