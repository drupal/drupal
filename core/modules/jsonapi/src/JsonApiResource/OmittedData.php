<?php

namespace Drupal\jsonapi\JsonApiResource;

use Drupal\Component\Assertion\Inspector;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;

/**
 * Represents resource data that should be omitted from the JSON:API document.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class OmittedData extends ResourceObjectData {

  /**
   * OmittedData constructor.
   *
   * @param \Drupal\jsonapi\Exception\EntityAccessDeniedHttpException[] $data
   *   Resource objects that are the primary data for the response.
   *
   * @see \Drupal\jsonapi\JsonApiResource\Data::__construct
   */
  public function __construct(array $data) {
    assert(Inspector::assertAllObjects($data, EntityAccessDeniedHttpException::class));
    parent::__construct($data, -1);
  }

}
