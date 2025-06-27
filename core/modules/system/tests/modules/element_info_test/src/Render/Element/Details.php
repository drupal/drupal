<?php

declare(strict_types=1);

namespace Drupal\element_info_test\Render\Element;

use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElementBase;

/**
 * Provides a render element for a details element.
 *
 * Properties:
 *
 * @property $title
 *   The title of the details container. Defaults to "Details".
 * @property $open
 *   Indicates whether the container should be open by default.
 *   Defaults to FALSE.
 * @property $custom
 *   Confirm that this class has been swapped properly.
 * @property $summary_attributes
 *   An array of attributes to apply to the <summary>
 *   element.
 */
#[RenderElement('details')]
class Details extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#open' => FALSE,
      '#summary_attributes' => [],
      '#custom' => 'Custom',
    ];
  }

  /**
   * Adds form element theming to details.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   details.
   *
   * @return array
   *   The modified element.
   */
  public static function preRenderDetails($element): array {
    Element::setAttributes($element, ['custom']);

    return $element;
  }

}
