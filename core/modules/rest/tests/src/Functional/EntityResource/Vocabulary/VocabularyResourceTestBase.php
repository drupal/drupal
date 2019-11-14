<?php

namespace Drupal\Tests\rest\Functional\EntityResource\Vocabulary;

@trigger_error('The ' . __NAMESPACE__ . '\VocabularyResourceTestBase is deprecated in Drupal 8.6.x and will be removed before Drupal 9.0.0. Instead, use Drupal\Tests\taxonomy\Functional\Rest\VocabularyResourceTestBase. See https://www.drupal.org/node/2971931.', E_USER_DEPRECATED);

use Drupal\Tests\taxonomy\Functional\Rest\VocabularyResourceTestBase as VocabularyResourceTestBaseReal;

/**
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   Drupal\Tests\taxonomy\Functional\Rest\VocabularyResourceTestBase instead.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class VocabularyResourceTestBase extends VocabularyResourceTestBaseReal {
}
