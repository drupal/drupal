<?php

/**
 * @file
 * Contains \Drupal\responsive_image_test_module\Plugin\Field\FieldFormatter\ResponsiveImageTestFormatter.
 */

namespace Drupal\responsive_image_test_module\Plugin\Field\FieldFormatter;

use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin to test responsive image formatter.
 *
 * @FieldFormatter(
 *   id = "responsive_image_test",
 *   label = @Translation("Responsive image test"),
 *   field_types = {
 *     "image",
 *   }
 * )
 */
class ResponsiveImageTestFormatter extends ResponsiveImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    // Unset #item_attributes to test that the theme function can handle that.
    foreach ($elements as &$element) {
      if (isset($element['#item_attributes'])) {
        unset($element['#item_attributes']);
      }
    }
    return $elements;
  }
}
