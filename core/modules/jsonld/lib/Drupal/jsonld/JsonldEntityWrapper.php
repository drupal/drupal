<?php
 
/**
 * @file
 * Definition of Drupal\jsonld\JsonldEntityWrapper.
 */

namespace Drupal\jsonld;

use Drupal\Core\Entity\Entity;
use Drupal\rdf\SiteSchema\SiteSchema;
use Drupal\rdf\SiteSchema\SiteSchemaManager;
use Symfony\Component\Serializer\Serializer;

/**
 * Provide an interface for JsonldNormalizer to get required properties.
 *
 * @todo Eventually, this class should be removed. It allows both the
 * EntityNormalizer and the EntityReferenceNormalizer to have access to the
 * same functions. If an $options parameter is added to the serialize
 * signature, as requested in https://github.com/symfony/symfony/pull/4938,
 * then the EntityReferenceNormalizer could simply call
 * EntityNormalizer::normalize(), passing in the referenced entity.
 */
class JsonldEntityWrapper {

  /**
   * The entity that this object wraps.
   *
   * @var \Drupal\Core\Entity\EntityNG
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
   * The site schema manager.
   *
   * @var \Drupal\rdf\SiteSchema\SiteSchemaManager
   */
  protected $siteSchemaManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityNG $entity
   *   The Entity API entity
   * @param string $format.
   *   The format.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer, provided by the SerializerAwareNormaizer.
   * @param \Drupal\rdf\SiteSchema\SiteSchemaManager $site_schema_manager
   *   The site schema manager.
   */
  public function __construct(Entity $entity, $format, Serializer $serializer, SiteSchemaManager $site_schema_manager) {
    $this->entity = $entity;
    $this->format = $format;
    $this->serializer = $serializer;
    $this->siteSchemaManager = $site_schema_manager;
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
   * Get the type URI.
   *
   * @todo Once RdfMappingManager has a mapOutputTypes event, use that instead
   * of simply returning the site schema URI.
   */
  public function getTypeUri() {
    $entity_type = $this->entity->entityType();
    $bundle = $this->entity->bundle();
    switch ($this->format) {
      case 'drupal_jsonld':
        $schema_path = SiteSchema::CONTENT_DEPLOYMENT;
        break;
      case 'jsonld':
        $schema_path = SiteSchema::SYNDICATION;
    }
    $schema = $this->siteSchemaManager->getSchema($schema_path);
    return $schema->bundle($entity_type, $bundle)->getUri();
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
    $properties = array();

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
