<?php

/**
 * @file
 * Contains \Drupal\hal\Normalizer\ContentEntityNormalizer.
 */

namespace Drupal\hal\Normalizer;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;
use Drupal\rest\LinkManager\LinkManagerInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Converts the Drupal entity object structure to a HAL array structure.
 */
class ContentEntityNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Entity\ContentEntityInterface';

  /**
   * The hypermedia link manager.
   *
   * @var \Drupal\rest\LinkManager\LinkManagerInterface
   */
  protected $linkManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  /**
   * Constructs an ContentEntityNormalizer object.
   *
   * @param \Drupal\rest\LinkManager\LinkManagerInterface $link_manager
   *   The hypermedia link manager.
   */
  public function __construct(LinkManagerInterface $link_manager, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler) {
    $this->linkManager = $link_manager;
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    // Create the array of normalized fields, starting with the URI.
    /** @var $entity \Drupal\Core\Entity\ContentEntityInterface */
    $normalized = array(
      '_links' => array(
        'self' => array(
          'href' => $this->getEntityUri($entity),
        ),
        'type' => array(
          'href' => $this->linkManager->getTypeUri($entity->getEntityTypeId(), $entity->bundle()),
        ),
      ),
    );

    // If the fields to use were specified, only output those field values.
    // Otherwise, output all field values except internal ID.
    if (isset($context['included_fields'])) {
      $fields = array();
      foreach ($context['included_fields'] as $field_name) {
        $fields[] = $entity->get($field_name);
      }
    }
    else {
      $fields = $entity->getProperties();
    }
    // Ignore the entity ID and revision ID.
    $exclude = array($entity->getEntityType()->getKey('id'), $entity->getEntityType()->getKey('revision'));
    foreach ($fields as $field) {
      if (in_array($field->getFieldDefinition()->getName(), $exclude)) {
        continue;
      }
      $normalized_property = $this->serializer->normalize($field, $format, $context);
      $normalized = NestedArray::mergeDeep($normalized, $normalized_property);
    }

    return $normalized;
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::denormalize().
   *
   * @param array $data
   *   Entity data to restore.
   * @param string $class
   *   Unused, entity_create() is used to instantiate entity objects.
   * @param string $format
   *   Format the given data was extracted from.
   * @param array $context
   *   Options available to the denormalizer. Keys that can be used:
   *   - request_method: if set to "patch" the denormalization will clear out
   *     all default values for entity fields before applying $data to the
   *     entity.
   *
   * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    // Get type, necessary for determining which bundle to create.
    if (!isset($data['_links']['type'])) {
      throw new UnexpectedValueException('The type link relation must be specified.');
    }

    // Create the entity.
    $typed_data_ids = $this->getTypedDataIds($data['_links']['type']);
    // Figure out the language to use.
    if (isset($data['langcode'])) {
      $langcode = $data['langcode'][0]['value'];
      // Remove the langcode so it does not get iterated over below.
      unset($data['langcode']);
    }
    elseif ($this->moduleHandler->moduleExists('language')) {
      $langcode = language_get_default_langcode($typed_data_ids['entity_type'], $typed_data_ids['bundle']);
    }
    else {
      $langcode = Language::LANGCODE_NOT_SPECIFIED;
    }

    $entity_type = $this->entityManager->getDefinition($typed_data_ids['entity_type']);
    $values = array('langcode' => $langcode);

    if ($entity_type->hasKey('bundle')) {
      $bundle_key = $entity_type->getKey('bundle');
      $values[$bundle_key] = $typed_data_ids['bundle'];
      // Unset the bundle key from data, if it's there.
      unset($data[$bundle_key]);
    }

    $entity = $this->entityManager->getStorage($typed_data_ids['entity_type'])->create($values);

    // Special handling for PATCH: destroy all possible default values that
    // might have been set on entity creation. We want an "empty" entity that
    // will only get filled with fields from the data array.
    if (isset($context['request_method']) && $context['request_method'] == 'patch') {
      foreach ($entity as $field_name => $field) {
        $entity->set($field_name, NULL);
      }
    }

    // Remove links from data array.
    unset($data['_links']);
    // Get embedded resources and remove from data array.
    $embedded = array();
    if (isset($data['_embedded'])) {
      $embedded = $data['_embedded'];
      unset($data['_embedded']);
    }

    // Flatten the embedded values.
    foreach ($embedded as $relation => $field) {
      $field_ids = $this->linkManager->getRelationInternalIds($relation);
      if (!empty($field_ids)) {
        $field_name = $field_ids['field_name'];
        $data[$field_name] = $field;
      }
    }

    // Iterate through remaining items in data array. These should all
    // correspond to fields.
    foreach ($data as $field_name => $field_data) {
      // Remove any values that were set as a part of entity creation (e.g
      // uuid). If this field is set to an empty array in the data, this will
      // also have the effect of marking the field for deletion in REST module.
      $entity->{$field_name} = array();

      $field = $entity->get($field_name);
      // Get the class of the field. This will generally be the default Field
      // class.
      $field_class = get_class($field);
      // Pass in the empty field object as a target instance. Since the context
      // is already prepared for the field, any data added to it is
      // automatically added to the entity.
      $context['target_instance'] = $field;
      $this->serializer->denormalize($field_data, $field_class, $format, $context);
    }

    return $entity;
  }

  /**
   * Constructs the entity URI.
   *
   * @param $entity
   *   The entity.
   *
   * @return string
   *   The entity URI.
   */
  protected function getEntityUri($entity) {
    return $entity->url('canonical', array('absolute' => TRUE));
  }

  /**
   * Gets the typed data IDs for a type URI.
   *
   * @param array $types
   *   The type array(s) (value of the 'type' attribute of the incoming data).
   *
   * @return array
   *   The typed data IDs.
   *
   * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
   */
  protected function getTypedDataIds($types) {
    // The 'type' can potentially contain an array of type objects. By default,
    // Drupal only uses a single type in serializing, but allows for multiple
    // types when deserializing.
    if (isset($types['href'])) {
      $types = array($types);
    }

    foreach ($types as $type) {
      if (!isset($type['href'])) {
        throw new UnexpectedValueException('Type must contain an \'href\' attribute.');
      }
      $type_uri = $type['href'];
      // Check whether the URI corresponds to a known type on this site. Break
      // once one does.
      if ($typed_data_ids = $this->linkManager->getTypeInternalIds($type['href'])) {
        break;
      }
    }

    // If none of the URIs correspond to an entity type on this site, no entity
    // can be created. Throw an exception.
    if (empty($typed_data_ids)) {
      throw new UnexpectedValueException(sprintf('Type %s does not correspond to an entity on this site.', $type_uri));
    }

    return $typed_data_ids;
  }
}
