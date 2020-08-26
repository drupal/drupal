<?php

namespace Drupal\jsonapi\JsonApiResource;

/**
 * An interface for identifying a related resource.
 *
 * Implement this interface when an object is a stand-in for an Entity object.
 * For example, \Drupal\jsonapi\Exception\EntityAccessDeniedHttpException
 * implements this interface because it often replaces an entity in a JSON:API
 * Data object.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
interface ResourceIdentifierInterface {

  /**
   * Gets the resource identifier's ID.
   *
   * @return string
   *   A resource ID.
   */
  public function getId();

  /**
   * Gets the resource identifier's JSON:API resource type name.
   *
   * @return string
   *   The JSON:API resource type name.
   */
  public function getTypeName();

  /**
   * Gets the resource identifier's JSON:API resource type.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType
   *   The JSON:API resource type.
   */
  public function getResourceType();

}
