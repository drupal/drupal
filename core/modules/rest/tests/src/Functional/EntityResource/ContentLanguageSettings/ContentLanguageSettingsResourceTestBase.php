<?php

namespace Drupal\Tests\rest\Functional\EntityResource\ContentLanguageSettings;

@trigger_error('The ' . __NAMESPACE__ . '\ContentLanguageSettingsResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\language\Functional\Rest\ContentLanguageSettingsResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\language\Functional\Rest\ContentLanguageSettingsResourceTestBase as ContentLanguageSettingsResourceTestBaseReal;

/**
 * @deprecated in Drupal 8.6.x. Will be removed before Drupal 9.0.0. Use
 *   Drupal\Tests\language\Functional\Rest\ContentLanguageSettingsResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class ContentLanguageSettingsResourceTestBase extends ContentLanguageSettingsResourceTestBaseReal {
}
