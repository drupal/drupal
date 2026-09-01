<?php

namespace Drupal\text\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'text_summary_or_trimmed' formatter.
 *
 * @deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. Use
 * \Drupal\text_with_summary\Plugin\Field\FieldFormatter\TextSummaryOrTrimmedFormatter
 * instead.
 *
 * @see https://www.drupal.org/node/3568381
 */
#[FieldFormatter(
  id: 'text_summary_or_trimmed',
  label: new TranslatableMarkup('Summary or trimmed'),
  field_types: [
    'text_with_summary',
  ],
)]
class TextSummaryOrTrimmedFormatter extends TextTrimmedFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $element = [
        '#type' => 'processed_text',
        '#format' => $item->format,
        '#langcode' => $item->getLangcode(),
      ];
      if (!empty($item->summary)) {
        $element['#text'] = $item->summary;
      }
      else {
        $element['#text'] = $item->value;
        $this->addTrimPreRender($element);
      }
      $elements[$delta] = $element;
    }
    return $elements;
  }

}
