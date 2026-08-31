<?php

declare(strict_types=1);

namespace Drupal\text_with_summary\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\text\Plugin\Field\FieldFormatter\TextTrimmedFormatter;

/**
 * Plugin implementation of the 'text_summary_or_trimmed' formatter.
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
  public function viewElements(FieldItemListInterface $items, $langcode): array {
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
