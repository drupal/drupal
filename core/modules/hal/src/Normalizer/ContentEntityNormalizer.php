<?php

namespace Drupal\hal\Normalizer;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\TypedData\TypedDataInternalPropertiesHelper;
use Drupal\hal\LinkManager\LinkManagerInterface;
use Drupal\serialization\Normalizer\FieldableEntityNormalizerTrait;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Converts the Drupal entity object structure to a HAL array structure.
 */
class ContentEntityNormalizer extends NormalizerBase {

  use FieldableEntityNormalizerTrait;

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Entity\ContentEntityInterface';

  /**
   * The hypermedia link manager.
   *
   * @var \Drupal\hal\LinkManager\LinkManagerInterface
   */
  protected $linkManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs an ContentEntityNormalizer object.
   *
   * @param \Drupal\hal\LinkManager\LinkManagerInterface $link_manager
   *   The hypermedia link manager.
   */
  public function __construct(LinkManagerInterface $link_manager, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler) {
    $this->linkManager = $link_manager;
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $context += [
      'account' => NULL,
      'included_fields' => NULL,
    ];

    // Create the array of normalized fields, starting with the URI.
    /** @var $entity \Drupal\Core\Entity\ContentEntityInterface */
    $normalized = [
      '_links' => [
        'self' => [
          'href' => $this->getEntityUri($entity),
        ],
        'type' => [
          'href' => $this->linkManager->getTypeUri($entity->getEntityTypeId(), $entity->bundle(), $context),
        ],
      ],
    ];

    $field_items = TypedDataInternalPropertiesHelper::getNonInternalProperties($entity->getTypedData());
    // If the fields to use were specified, only output those field values.
    if (isset($context['included_fields'])) {
      $field_items = array_intersect_key($field_items, array_flip($context['included_fields']));
    }
    foreach ($field_items as $field) {
      // Continue if the current user does not have access to view this field.
      if (!$field->access('view', $context['account'])) {
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
   * @return \Drupal\Core\Entity\EntityInterface
   *   An unserialized entity object containing the data in $data.
   *
   * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    // Get type, necessary for determining which bundle to create.
    if (!isset($data['_links']['type'])) {
      throw new UnexpectedValueException('The type link relation must be specified.');
    }

    // Create the entity.
    $typed_data_ids = $this->getTypedDataIds($data['_links']['type'], $context);
    $entity_type = $this->getEntityTypeDefinition($typed_data_ids['entity_type']);
    $default_langcode_key = $entity_type->getKey('default_langcode');
    $langcode_key = $entity_type->getKey('langcode');
    $values = [];

    // Figure out the language to use.
    if (isset($data[$default_langcode_key])) {
      // Find the field item for which the default_langcode value is set to 1 and
      // set the langcode the right default language.
      foreach ($data[$default_langcode_key] as $item) {
        if (!empty($item['value']) && isset($item['lang'])) {
          $values[$langcode_key] = $item['lang'];
          break;
        }
      }
      // Remove the default langcode so it does not get iterated over below.
      unset($data[$default_langcode_key]);
    }

    if ($entity_type->hasKey('bundle')) {
      $bundle_key = $entity_type->getKey('bundle');
      $values[$bundle_key] = $typed_data_ids['bundle'];
      // Unset the bundle key from data, if it's there.
      unset($data[$bundle_key]);
    }

    $entity = $this->entityManager->getStorage($typed_data_ids['entity_type'])->create($values);

    // Remove links from data array.
    unset($data['_links']);
    // Get embedded resources and remove from data array.
    $embedded = [];
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

    $this->denormalizeFieldData($data, $entity, $format, $context);

    // Pass the names of the fields whose values can be merged.
    // @todo https://www.drupal.org/node/2456257 remove this.
    $entity->_restSubmittedFields = array_keys($data);

    return $entity;
  }

  /**
   * Constructs the entity URI.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @return string
   *   The entity URI.
   */
  protected function getEntityUri(EntityInterface $entity) {
    // Some entity types don't provide a canonical link template, at least call
    // out to ->url().
    if ($entity->isNew() || !$entity->hasLinkTemplate('canonical')) {
      return $entity->url('canonical', []);
    }
    $url = $entity->urlInfo('canonical', ['absolute' => TRUE]);
    return $url->setRouteParameter('_format', 'hal_json')->toString();
  }

  /**
   * Gets the typed data IDs for a type URI.
   *
   * @param array $types
   *   The type array(s) (value of the 'type' attribute of the incoming data).
   * @param array $context
   *   Context from the normalizer/serializer operation.
   *
   * @return array
   *   The typed data IDs.
   */
  protected function getTypedDataIds($types, $context = []) {
    // The 'type' can potentially contain an array of type objects. By default,
    // Drupal only uses a single type in serializing, but allows for multiple
    // types when deserializing.
    if (isset($types['href'])) {
      $types = [$types];
    }

    if (empty($types)) {
      throw new UnexpectedValueException('No entity type(s) specified');
    }

    foreach ($types as $type) {
      if (!isset($type['href'])) {
        throw new UnexpectedValueException('Type must contain an \'href\' attribute.');
      }

      $type_uri = $type['href'];

      // Check whether the URI corresponds to a known type on this site. Break
      // once one does.
      if ($typed_data_ids = $this->linkManager->getTypeInternalIds($type['href'], $context)) {
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
