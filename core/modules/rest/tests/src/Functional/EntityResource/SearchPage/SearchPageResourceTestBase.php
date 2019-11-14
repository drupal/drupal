<?php

namespace Drupal\Tests\rest\Functional\EntityResource\SearchPage;

@trigger_error('The ' . __NAMESPACE__ . '\SearchPageResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\search\Functional\Rest\SearchPageResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\search\Functional\Rest\SearchPageResourceTestBase as SearchPageResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\search\Functional\Rest\SearchPageResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class SearchPageResourceTestBase extends SearchPageResourceTestBaseReal {
}
