<?php

declare(strict_types=1);

namespace Drupal\Tests\standard\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\standard\Traits\StandardTestTrait;

/**
 * Tests Standard installation profile expectations.
 *
 * @group standard
 */
class StandardTest extends BrowserTestBase {
  use StandardTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

}
