<?php

/**
 * @file
 * Definition of Drupal\jsonld\JsonldEntityWrapper.
 */

namespace Drupal\jsonld;

use Drupal\Core\Entity\EntityNG;

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
   * Constructor.
   *
   * @param string $entity
   *   The Entity API entity
   */
  public function __construct(EntityNG $entity) {
    $this->entity = $entity;
  }

  /**
   * Get the Entity's URI for the @id attribute.
   */
  public function getId() {
    $uri_info = $this->entity->uri();
    return url($uri_info['path'], array('absolute' => TRUE));
  }

  /**
   * Get properties, excluding JSON-LD specific properties.
   *
   * Formats Entity properties in the JSON-LD array structure and removes
   * unwanted values.
   */
  public function getProperties() {
    // @todo Add property handling based on http://drupal.org/node/1813328.
    return array();
  }
}
