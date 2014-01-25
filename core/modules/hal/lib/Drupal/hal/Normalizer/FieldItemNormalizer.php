<?php

/**
 * @file
 * Contains \Drupal\hal\Normalizer\FieldItemNormalizer.
 */

namespace Drupal\hal\Normalizer;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Converts the Drupal field item object structure to HAL array structure.
 */
class FieldItemNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Field\FieldItemInterface';

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

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::denormalize()
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    if (!isset($context['target_instance'])) {
      throw new LogicException('$context[\'target_instance\'] must be set to denormalize with the FieldItemNormalizer');
    }
    if ($context['target_instance']->getParent() == NULL) {
      throw new LogicException('The field item passed in via $context[\'target_instance\'] must have a parent set.');
    }

    $field_item = $context['target_instance'];

    // If this field is translatable, we need to create a translated instance.
    if (isset($data['lang'])) {
      $langcode = $data['lang'];
      unset($data['lang']);
      $field_definition = $field_item->getFieldDefinition();
      if ($field_definition->isTranslatable()) {
        $field_item = $this->createTranslatedInstance($field_item, $langcode);
      }
    }

    $field_item->setValue($this->constructValue($data, $context));
    return $field_item;
  }

  /**
   * Build the field item value using the incoming data.
   *
   * @param $data
   *   The incoming data for this field item.
   * @param $context
   *   The context passed into the Normalizer.
   *
   * @return mixed
   *   The value to use in Entity::setValue().
   */
  protected function constructValue($data, $context) {
    return $data;
  }

  /**
   * Get a translated version of the field item instance.
   *
   * To indicate that a field item applies to one translation of an entity and
   * not another, the property path must originate with a translation of the
   * entity. This is the reason for using target_instances, from which the
   * property path can be traversed up to the root.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $field_item
   *   The untranslated field item instance.
   * @param $langcode
   *   The langcode.
   *
   * @return \Drupal\Core\Field\FieldItemInterface
   *   The translated field item instance.
   */
  protected function createTranslatedInstance(FieldItemInterface $field_item, $langcode) {
    $parent = $field_item->getParent();
    $ancestors = array();

    // Remove the untranslated instance from the field's list of items.
    $parent->offsetUnset($field_item->getName());

    // Get the property path.
    while (!method_exists($parent, 'getTranslation')) {
      array_unshift($ancestors, $parent);
      $parent = $parent->getParent();
    }

    // Recreate the property path with translations.
    $translation = $parent->getTranslation($langcode);
    foreach ($ancestors as $ancestor) {
      $ancestor_name =  $ancestor->getName();
      $translation = $translation->get($ancestor_name);
    }

    // Create a new instance at the end of the property path and return it.
    $count = $translation->isEmpty() ? 0 : $translation->count();
    return $translation->get($count);
  }

}
