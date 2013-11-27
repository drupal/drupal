<?php

/**
 * @file
 * Contains \Drupal\hal\Normalizer\EntityReferenceItemNormalizer.
 */

namespace Drupal\hal\Normalizer;

use Drupal\serialization\EntityResolver\UuidReferenceInterface;

/**
 * Converts the Drupal entity reference item object to HAL array structure.
 */
class EntityReferenceItemNormalizer extends FieldItemNormalizer implements UuidReferenceInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem';

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   */
  public function normalize($field_item, $format = NULL, array $context = array()) {
    $target_entity = $field_item->get('entity')->getValue();

    // If the parent entity passed in a langcode, unset it before normalizing
    // the target entity. Otherwise, untranslatable fields of the target entity
    // will include the langcode.
    $langcode = isset($context['langcode']) ? $context['langcode'] : NULL;
    unset($context['langcode']);
    $context['included_fields'] = array('uuid');

    // Normalize the target entity.
    $embedded = $this->serializer->normalize($target_entity, $format, $context);
    $link = $embedded['_links']['self'];
    // If the field is translatable, add the langcode to the link relation
    // object. This does not indicate the language of the target entity.
    if ($langcode) {
      $embedded['lang'] = $link['lang'] = $langcode;
    }

    // The returned structure will be recursively merged into the normalized
    // entity so that the items are properly added to the _links and _embedded
    // objects.
    $field_name = $field_item->getParent()->getName();
    $entity = $field_item->getEntity();
    $field_uri = $this->linkManager->getRelationUri($entity->entityType(), $entity->bundle(), $field_name);
    return array(
      '_links' => array(
        $field_uri => array($link),
      ),
      '_embedded' => array(
        $field_uri => array($embedded),
      ),
    );
  }

  /**
   * Overrides \Drupal\hal\Normalizer\FieldItemNormalizer::constructValue().
   */
  protected function constructValue($data, $context) {
    $field_item = $context['target_instance'];
    $field_definition = $field_item->getFieldDefinition();
    $target_type = $field_definition->getFieldSetting('target_type');
    if ($id = $this->entityResolver->resolve($this, $data, $target_type)) {
      return array('target_id' => $id);
    }
    return NULL;
  }

  /**
   * Implements \Drupal\serialization\EntityResolver\UuidReferenceInterface::getUuid().
   */
  public function getUuid($data) {
    if (isset($data['uuid'])) {
      $uuid = $data['uuid'];
      if (is_array($uuid)) {
        $uuid = reset($uuid);
      }
      return $uuid;
    }
  }

}
