<?php

/**
 * @file
 * Definition of Drupal\jsonld\JsonldEntityNormalizer.
 */

namespace Drupal\jsonld;

use Drupal\jsonld\JsonldNormalizerBase;
use Drupal\rdf\RdfMappingException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Converts the Drupal entity object structure to JSON-LD array structure.
 */
class JsonldEntityNormalizer extends JsonldNormalizerBase implements DenormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected static $supportedInterfaceOrClass = 'Drupal\Core\Entity\EntityInterface';

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   */
  public function normalize($entity, $format = NULL) {
    $entity_wrapper = new JsonldEntityWrapper($entity, $format, $this->serializer, $this->siteSchemaManager);

    $attributes = $entity_wrapper->getProperties();
    $attributes = array(
      '@id' => $entity_wrapper->getId(),
      '@type' => $entity_wrapper->getTypeUri(),
    ) + $attributes;
    return $attributes;
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::denormalize()
   *
   * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
   */
  public function denormalize($data, $class, $format = null) {
    if (!isset($data['@type'])) {
      throw new UnexpectedValueException('JSON-LD @type parameter must be included.');
    }

    // Every bundle has a type, identified by URI. The incoming data should
    // either include a type URI from this site's schema, or one of the type
    // URIs in the incoming data must map to a site schema URI when passed
    // through the RDF mapping manager.
    $type_uris = is_array($data['@type']) ? $data['@type'] : array($data['@type']);
    // If the RDF mapping manager can find a match to a site schema URI, it
    // will return the corresponding Typed Data ids. Otherwise, throw an
    // exception.
    // @todo The @types might be CURIEs or aliases. Expand before trying to map.
    try {
      $typed_data_ids = $this->rdfMappingManager->getTypedDataIdsFromTypeUris($type_uris);
    }
    catch (RdfMappingException $e) {
      throw new UnexpectedValueException($e->getMessage(), 0, $e);
    }

    $values = array(
      'type' => $typed_data_ids['bundle'],
    );
    // If the data specifies a default language, use it to create the entity.
    if (isset($data['langcode'])) {
      $values['langcode'] = $data['langcode'][LANGUAGE_NOT_SPECIFIED][0]['value'];
    }
    // Otherwise, if the default language is not specified but there are
    // translations of field values, explicitly set the entity's default
    // language to the site's default language. This is required to enable
    // field translation on this entity.
    else if ($this->containsTranslation($data)) {
      $values['langcode'] = language(LANGUAGE_TYPE_CONTENT)->langcode;
    }
    $entity = entity_create($typed_data_ids['entity_type'], $values);

    // For each attribute in the JSON-LD, add the values as fields to the newly
    // created entity. It is assumed that the JSON attribute names are the same
    // as the site's field names.
    // @todo Possibly switch to URI expansion of attribute names.
    foreach ($data as $fieldName => $incomingFieldValues) {
      // Skip the JSON-LD specific terms, which start with '@'.
      if ($fieldName[0] === '@') {
        continue;
      }
      // If the incoming value is an empty array we set the property to mark it
      // for deletion.
      if (empty($incomingFieldValues) && is_array($incomingFieldValues)) {
        $entity->{$fieldName} = array();
      }

      // Figure out the designated class for this field type, which is used by
      // the Serializer to determine which Denormalizer to use.
      // @todo Is there a better way to get the field type's associated class?
      $fieldItemClass = get_class($entity->get($fieldName)->offsetGet(0));

      // Iterate through the language keyed values and add them to the entity.
      // The vnd.drupal.ld+json mime type will always use language keys, per
      // http://drupal.org/node/1838700.
      foreach ($incomingFieldValues as $langcode => $incomingFieldItems) {
        $fieldValue = $this->serializer->denormalize($incomingFieldItems, $fieldItemClass, $format);
        $entity->getTranslation($langcode)
          ->set($fieldName, $fieldValue);
      }
    }
    return $entity;
  }

  /**
   * Determine whether incoming data contains translated content.
   *
   * @param array $data
   *   The incoming data.
   *
   * @return bool
   *   Whether or not this data contains translated content.
   */
  protected function containsTranslation($data) {
    // Langcodes which do not represent a translation of the entity.
    $defaultLangcodes = array(
      LANGUAGE_DEFAULT,
      LANGUAGE_NOT_SPECIFIED,
      LANGUAGE_NOT_APPLICABLE,
      language(LANGUAGE_TYPE_CONTENT)->langcode,
    );

    // Combine the langcodes from the field value keys in a single array.
    $fieldLangcodes = array();
    foreach ($data as $propertyName => $property) {
      //@todo Once @context has been added, check whether this property
      // corresponds to an annotation instead. This will allow us to support
      // incoming data that doesn't use language annotations.
      if ('@' !== $propertyName[0]) {
        $fieldLangcodes += array_keys($property);
      }
    }

    $translationLangcodes = array_diff($fieldLangcodes, $defaultLangcodes);
    return !empty($translationLangcodes);
  }
}
