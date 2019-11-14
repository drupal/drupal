<?php

namespace Drupal\Tests\rest\Functional\EntityResource\RdfMapping;

@trigger_error('The ' . __NAMESPACE__ . '\RdfMappingResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\rdf\Functional\Rest\RdfMappingResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\rdf\Functional\Rest\RdfMappingResourceTestBase as RdfMappingResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\rdf\Functional\Rest\RdfMappingResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class RdfMappingResourceTestBase extends RdfMappingResourceTestBaseReal {
}
