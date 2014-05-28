<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\FakeRecord.
 */

namespace Drupal\system\Tests\Database;

/**
 * Fetches into a class.
 *
 * PDO supports using a new instance of an arbitrary class for records
 * rather than just a stdClass or array. This class is for testing that
 * functionality. (See testQueryFetchClass() below)
 */
class FakeRecord { }
