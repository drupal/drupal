<?php

namespace Drupal\jsonapi\JsonApiResource;

use Drupal\Component\Assertion\Inspector;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;

/**
 * Represents the included member of a JSON:API document.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class IncludedData extends ResourceObjectData {

  /**
   * IncludedData constructor.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceObject[]|\Drupal\jsonapi\Exception\EntityAccessDeniedHttpException[] $data
   *   Resource objects that are the primary data for the response.
   *
   * @see \Drupal\jsonapi\JsonApiResource\Data::__construct
   */
  public function __construct($data) {
    assert(Inspector::assertAllObjects($data, ResourceObject::class, EntityAccessDeniedHttpException::class));
    parent::__construct($data, -1);
  }

}
