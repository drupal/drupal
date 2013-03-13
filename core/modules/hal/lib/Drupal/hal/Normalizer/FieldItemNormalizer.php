<?php

/**
 * @file
 * Contains \Drupal\hal\Normalizer\FieldItemNormalizer.
 */

namespace Drupal\hal\Normalizer;

/**
 * Converts the Drupal field item object structure to HAL array structure.
 */
class FieldItemNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Entity\Field\FieldItemInterface';

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   */
  public function normalize($field_item, $format = NULL, array $context = array()) {
    $values = $field_item->getPropertyValues();
    if (isset($context['langcode'])) {
      $values['lang'] = $context['langcode'];
    }

    // The values are wrapped in an array, and then wrapped in another array
    // keyed by field name so that field items can be merged by the
    // FieldNormalizer. This is necessary for the EntityReferenceItemNormalizer
    // to be able to place values in the '_links' array.
    $field = $field_item->getParent();
    return array(
      $field->getName() => array($values),
    );
  }

}
