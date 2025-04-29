<?php

declare(strict_types=1);

namespace Drupal\TestTools;

/**
 * Enumeration of JUnit test result statuses.
 */
enum PhpUnitTestCaseJUnitResult: string {

  case Pass = 'pass';
  case Fail = 'fail';
  case Error = 'error';
  case Skip = 'skipped';

}
