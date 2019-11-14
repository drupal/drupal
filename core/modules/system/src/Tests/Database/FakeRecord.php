<?php

namespace Drupal\system\Tests\Database;

@trigger_error(__NAMESPACE__ . '\FakeRecord is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use \Drupal\Tests\system\Functional\Database\FakeRecord', E_USER_DEPRECATED);

/**
 * Fetches into a class.
 *
 * PDO supports using a new instance of an arbitrary class for records
 * rather than just a stdClass or array. This class is for testing that
 * functionality. (See testQueryFetchClass() below)
 *
 * @deprecated in drupal:8.4.0 and is removed from drupal:9.0.0. Instead
 *   use \Drupal\Tests\system\Functional\Database\FakeRecord.
 */
class FakeRecord {}
