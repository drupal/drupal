<?php

namespace Drupal\Tests\rest\Functional\EntityResource\ConfigurableLanguage;

@trigger_error('The ' . __NAMESPACE__ . '\ConfigurableLanguageResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\language\Functional\Rest\ConfigurableLanguageResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\language\Functional\Rest\ConfigurableLanguageResourceTestBase as ConfigurableLanguageResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\language\Functional\Rest\ConfigurableLanguageResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class ConfigurableLanguageResourceTestBase extends ConfigurableLanguageResourceTestBaseReal {
}
