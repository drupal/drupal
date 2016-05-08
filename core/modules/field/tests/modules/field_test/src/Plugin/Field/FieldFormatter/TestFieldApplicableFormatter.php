<?php

namespace Drupal\field_test\Plugin\Field\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;


/**
 * Plugin implementation of the 'field_test_applicable' formatter.
 *
 * It is applicable to test_field fields unless their name is 'deny_applicable'.
 *
 * @FieldFormatter(
 *   id = "field_test_applicable",
 *   label = @Translation("Applicable"),
 *   description = @Translation("Applicable formatter"),
 *   field_types = {
 *     "test_field"
 *   },
 *   weight = 15,
 * )
 */
class TestFieldApplicableFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getName() != 'deny_applicable';
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return array('#markup' => 'Nothing to see here');
  }

}
