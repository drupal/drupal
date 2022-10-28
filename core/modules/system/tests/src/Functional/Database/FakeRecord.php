<?php

namespace Drupal\Tests\system\Functional\Database;

/**
 * Fetches into a class.
 *
 * PDO supports using a new instance of an arbitrary class for records
 * rather than just a stdClass or array. This class is for testing that
 * functionality. (See testQueryFetchClass() below)
 */
class FakeRecord {

  /**
   * A class variable.
   *
   * @var int
   */
  public $fakeArg;

  /**
   * The property used in tests.
   *
   * @see \Drupal\KernelTests\Core\Database\FetchTest
   *
   * @var string
   */
  public string $name;

  /**
   * The property used in tests.
   *
   * @see \Drupal\KernelTests\Core\Database\DatabaseTestBase
   *
   * @var string
   */
  public string $job;

  /**
   * Constructs a FakeRecord object with an optional constructor argument.
   *
   * @param int $fakeArg
   *   A class variable.
   */
  public function __construct($fakeArg = 0) {
    $this->fakeArg = $fakeArg;
  }

}
