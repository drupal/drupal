<?php

/**
 * @file
 * Contains \Drupal\rdf\Plugin\Core\Entity\RdfMapping.
 */

namespace Drupal\rdf\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\rdf\RdfMappingInterface;

/**
 * Config entity for working with RDF mappings.
 *
 * @EntityType(
 *   id = "rdf_mapping",
 *   label = @Translation("RDF mapping"),
 *   module = "rdf",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController"
 *   },
 *   config_prefix = "rdf.mapping",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class RdfMapping extends ConfigEntityBase implements RdfMappingInterface {

  /**
   * Unique ID for the config entity.
   *
   * @var string
   */
  public $id;

  /**
   * UUID for the config entity.
   *
   * @var string
   */
  public $uuid;

  /**
   * Entity type to be mapped.
   *
   * @var string
   */
  public $targetEntityType;

  /**
   * Bundle to be mapped.
   *
   * @var string
   */
  public $bundle;

  /**
   * The RDF type mapping for this bundle.
   *
   * @var array
   */
  protected $types;

  /**
   * The mappings for fields on this bundle.
   *
   * @var array
   */
  protected $fieldMappings;

  /**
   * {@inheritdoc}
   */
  public function getPreparedBundleMapping() {
    $types = array();
    if (isset($this->types)) {
      $types = $this->types;
    }
    return array('types' => $types);
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleMapping() {
    if (isset($this->types)) {
      return array('types' => $this->types);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundleMapping(array $mapping) {
    if (isset($mapping['types'])) {
      $this->types = $mapping['types'];
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreparedFieldMapping($field_name) {
    $field_mapping = array(
      'properties' => NULL,
      'datatype' => NULL,
      'datatype_callback' => NULL,
      'mapping_type' => NULL,
    );
    if (isset($this->fieldMappings[$field_name])) {
      $field_mapping = array_merge($field_mapping, $this->fieldMappings[$field_name]);
    }
    return $field_mapping;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMapping($field_name) {
    if (isset($this->fieldMappings[$field_name])) {
      return $this->fieldMappings[$field_name];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldMapping($field_name, array $mapping = array()) {
    $this->fieldMappings[$field_name] = $mapping;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->targetEntityType . '.' . $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    // Build an ID if none is set.
    if (empty($this->id)) {
      $this->id = $this->id();
    }
    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function getExportProperties() {
    $names = array(
      'id',
      'uuid',
      'targetEntityType',
      'bundle',
      'types',
      'fieldMappings',
    );
    $properties = array();
    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }
    return $properties;
  }

}
