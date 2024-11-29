<?php

declare(strict_types=1);

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\Icon\IconDefinition;

/**
 * Provides a render element to display an icon.
 *
 * Properties:
 * - #pack_id: (string) Icon Pack provider plugin id.
 * - #icon_id: (string) Name of the icon.
 * - #settings: (array) Settings sent to the inline Twig template.
 *
 * Usage Example:
 * @code
 * $build['icon'] = [
 *   '#type' => 'icon',
 *   '#pack_id' => 'material_symbols',
 *   '#icon_id' => 'home',
 *   '#settings' => [
 *     'width' => 64,
 *   ],
 * ];
 * @endcode
 *
 * @internal
 */
#[RenderElement('icon')]
class Icon extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#pre_render' => [
        [self::class, 'preRenderIcon'],
      ],
      '#pack_id' => '',
      '#icon_id' => '',
      '#settings' => [],
    ];
  }

  /**
   * Icon element pre render callback.
   *
   * @param array $element
   *   An associative array containing the properties of the icon element.
   *
   * @return array
   *   The modified element.
   */
  public static function preRenderIcon(array $element): array {
    $icon_full_id = IconDefinition::createIconId($element['#pack_id'], $element['#icon_id']);

    $pluginManagerIconPack = \Drupal::service('plugin.manager.icon_pack');
    if (!$icon = $pluginManagerIconPack->getIcon($icon_full_id)) {
      return $element;
    }

    // Build context minimal values as icon_id, optional source and attributes.
    $context = [
      'icon_id' => $icon->getIconId(),
    ];
    // Better to not have source value if not set for the template.
    if ($source = $icon->getSource()) {
      $context['source'] = $source;
    }
    // Silently ensure settings is an array.
    if (!is_array($element['#settings'])) {
      $element['#settings'] = [];
    }

    $extractor_data = $icon->getAllData();
    // Inject attributes variable if not created by the extractor.
    if (!isset($extractor_data['attributes'])) {
      $extractor_data['attributes'] = new Attribute();
    }

    $element['inline-template'] = [
      '#type' => 'inline_template',
      '#template' => $icon->getTemplate(),
      // Context include data from extractor and settings, priority on settings
      // from this element. Context as last value to be sure nothing override
      // icon_id or source if set.
      '#context' => array_merge($extractor_data, $element['#settings'], $context),
    ];

    if ($library = $icon->getLibrary()) {
      $element['inline-template']['#attached'] = ['library' => [$library]];
    }

    return $element;
  }

}
