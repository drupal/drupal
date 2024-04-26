<?php

declare(strict_types=1);

namespace Drupal\navigation;

use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\navigation\Plugin\SectionStorage\NavigationSectionStorage;

/**
 * Defines a class for render element callbacks.
 *
 * @internal
 */
final class RenderCallbacks {

  /**
   * Pre-render callback for layout builder.
   */
  #[TrustedCallback]
  public static function alterLayoutBuilder(array $element): array {
    if (($element['#section_storage'] ?? NULL) instanceof NavigationSectionStorage) {
      // Remove add section links that exist before and after the existing
      // section.
      unset($element['layout_builder'][0], $element['layout_builder'][2]);
      // Remove add block link from the footer section and the remove and
      // configure buttons from the existing section.
      unset(
        $element['layout_builder'][1]['remove'],
        $element['layout_builder'][1]['configure'],
        $element['layout_builder'][1]['layout-builder__section']['footer']['layout_builder_add_block'],
      );
    }
    return $element;
  }

}
