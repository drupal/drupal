<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Generic module test for node.
 */
#[Group('node')]
#[RunTestsInSeparateProcesses]
class GenericTest extends GenericModuleTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Ensure the generic test base is working as expected.
    $this->assertSame('node', $this->getModule());
    parent::setUp();
  }

}
