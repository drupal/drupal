<?php

namespace Drupal\Tests\rest\Functional\EntityResource\ContactForm;

@trigger_error('The ' . __NAMESPACE__ . '\ContactFormResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\contact\Functional\Rest\ContactFormResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\contact\Functional\Rest\ContactFormResourceTestBase as ContactFormResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\contact\Functional\Rest\ContactFormResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class ContactFormResourceTestBase extends ContactFormResourceTestBaseReal {
}
