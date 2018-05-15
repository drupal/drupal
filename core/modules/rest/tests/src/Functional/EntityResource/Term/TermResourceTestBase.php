<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Term;

@trigger_error('The ' . __NAMESPACE__ . '\TermResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\taxonomy\Functional\Rest\TermResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\taxonomy\Functional\Rest\TermResourceTestBase as TermResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\taxonomy\Functional\Rest\TermResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class TermResourceTestBase extends TermResourceTestBaseReal {
}
