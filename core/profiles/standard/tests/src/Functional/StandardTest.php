<?php

declare(strict_types=1);

namespace Drupal\Tests\standard\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\standard\Traits\StandardTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Standard installation profile expectations.
 */
#[Group('standard')]
#[RunTestsInSeparateProcesses]
class StandardTest extends BrowserTestBase {
  use StandardTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

}
