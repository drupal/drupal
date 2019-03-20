<?php

namespace Drupal\jsonapi\JsonApiResource;

/**
 * Used to associate an object like an exception to a particular resource.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 *
 * @see \Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface
 */
trait ResourceIdentifierTrait {

  /**
   * A ResourceIdentifier object.
   *
   * @var \Drupal\jsonapi\JsonApiResource\ResourceIdentifier
   */
  protected $resourceIdentifier;

  /**
   * The JSON:API resource type of of the identified resource object.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceType
   */
  protected $resourceType;

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->resourceIdentifier->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeName() {
    return $this->resourceIdentifier->getTypeName();
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceType() {
    if (!isset($this->resourceType)) {
      $this->resourceType = $this->resourceIdentifier->getResourceType();
    }
    return $this->resourceType;
  }

}
