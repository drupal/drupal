<?php

/**
 * @file
 * Contains \Drupal\rdf\Entity\RdfMapping.
 */

namespace Drupal\rdf\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\rdf\RdfMappingInterface;

/**
 * Config entity for working with RDF mappings.
 *
 * @ConfigEntityType(
 *   id = "rdf_mapping",
 *   label = @Translation("RDF mapping"),
 *   config_prefix = "mapping",
 *   entity_keys = {
 *     "id" = "id"
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

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

    if (\Drupal::entityManager()->hasController($this->targetEntityType, 'view_builder')) {
      \Drupal::entityManager()->getViewBuilder($this->targetEntityType)->resetCache();
    }
  }

}
