<?php
 
/**
 * @file
 * Definition of Drupal\jsonld\JsonldEntityWrapper.
 */

namespace Drupal\jsonld;

use Drupal\Core\Entity\Entity;

/**
 * Provide an interface for JsonldNormalizer to get required properties.
 */
class JsonldEntityWrapper {

  /**
   * The entity that this object wraps.
   *
   * @var Drupal\Core\Entity\EntityNG
   */
  protected $entity;

  /**
   * The requested format.
   *
   * @var string
   */
  protected $format;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Constructor.
   *
   * @param string $entity
   *   The Entity API entity
   * @param string $format.
   *   The format.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer, provided by the SerializerAwareNormaizer.
   */
  public function __construct(Entity $entity, $format, $serializer) {
    $this->entity = $entity;
    $this->format = $format;
    $this->serializer = $serializer;
  }

  /**
   * Get the Entity's URI for the @id attribute.
   *
   * @return string
   *   The URI of the entity.
   */
  public function getId() {
    $uri_info = $this->entity->uri();
    return url($uri_info['path'], array('absolute' => TRUE));
  }

  /**
   * Get properties, excluding JSON-LD specific properties.
   *
   * @return array
   *   An array of properties structured as in JSON-LD.
   */
  public function getProperties() {
    // Properties to skip.
    $skip = array('id');

    // Create language map property structure.
    foreach ($this->entity->getTranslationLanguages() as $langcode => $language) {
      foreach ($this->entity->getTranslation($langcode) as $name => $field) {
        $definition = $this->entity->getPropertyDefinition($name);
        $langKey = empty($definition['translatable']) ? LANGUAGE_NOT_SPECIFIED : $langcode;
        if (!$field->isEmpty()) {
          $properties[$name][$langKey] = $this->serializer->normalize($field, $this->format);
        }
      }
    }

    // Only return properties which are not in the $skip array.
    return array_diff_key($properties, array_fill_keys($skip, ''));
  }

}
