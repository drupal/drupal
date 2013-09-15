<?php

/**
 * @file
 *
 * Contains \Drupal\field_test\Plugin\field\formatter\TestFieldEmptyFormatter.
 */
namespace Drupal\field_test\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Field\FieldInterface;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;

/**
 * Plugin implementation of the 'field_empty_test' formatter.
 *
 * @FieldFormatter(
 *   id = "field_empty_test",
 *   label = @Translation("Field empty test"),
 *   field_types = {
 *     "test_field",
 *   },
 *   settings = {
 *     "test_empty_string" = "**EMPTY FIELD**"
 *   }
 * )
 */
class TestFieldEmptyFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(EntityInterface $entity, $langcode, FieldInterface $items) {
    $elements = array();

    if ($items->isEmpty()) {
      // For fields with no value, just add the configured "empty" value.
      $elements[0] = array('#markup' => $this->getSetting('test_empty_string'));
    }
    else {
      foreach ($items as $delta => $item) {
        // This formatter only needs to output raw for testing.
        $elements[$delta] = array('#markup' => $item->value);
      }
    }

    return $elements;
  }

}
