<?php

namespace Drupal\layout_builder\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'layout_section' formatter.
 *
 * @internal
 *
 * @FieldFormatter(
 *   id = "layout_section",
 *   label = @Translation("Layout Section"),
 *   field_types = {
 *     "layout_section"
 *   }
 * )
 */
class LayoutSectionFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\layout_builder\SectionStorageInterface $items */
    foreach ($items->getSections() as $delta => $section) {
      $elements[$delta] = $section->toRenderArray();
    }

    return $elements;
  }

}
