<?php

namespace Drupal\jsonapi\Normalizer\Value;

/**
 * Helps normalize exceptions in compliance with the JSON:API spec.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class HttpExceptionNormalizerValue extends CacheableNormalization {}
