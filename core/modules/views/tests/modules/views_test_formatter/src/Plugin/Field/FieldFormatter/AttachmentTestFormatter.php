<?php

declare(strict_types=1);

namespace Drupal\views_test_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\NumericUnformattedFormatter;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'number_unformatted_with_attachment' formatter.
 */
#[FieldFormatter(
  id: 'number_unformatted_with_attachment',
  label: new TranslatableMarkup('Unformatted, with attachments'),
  field_types: [
    'integer',
    'decimal',
    'float',
  ],
)]
class AttachmentTestFormatter extends NumericUnformattedFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    // Add dummy attachments.
    $entity_id = $items->getEntity()->id();
    $elements['#attached']['library'][] = 'foo/fake_library';
    $elements['#attached']['drupalSettings']['AttachmentIntegerFormatter'][$entity_id] = $entity_id;

    return $elements;
  }

}
