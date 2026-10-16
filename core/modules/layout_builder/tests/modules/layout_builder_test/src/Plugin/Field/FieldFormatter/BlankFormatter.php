<?php

declare(strict_types=1);

namespace Drupal\layout_builder_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A blank field formatter.
 */
#[FieldFormatter(
  id: 'blank',
  label: new TranslatableMarkup('Blank'),
  field_types: [
    'string',
  ],
)]
final class BlankFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    return [];
  }

}
