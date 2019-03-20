<?php

namespace Drupal\jsonapi\Revisions;

/**
 * Used when a version ID is invalid.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032787
 * @see jsonapi.api.php
 */
class InvalidVersionIdentifierException extends \InvalidArgumentException {}
