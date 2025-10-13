<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Render\Element;

use Drupal\Core\Render\Element\StatusReport;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the status report element legacy methods.
 */
#[Group('Render')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class LegacyStatusReportTest extends KernelTestBase {

  /**
   * Tests the getSeverities() method deprecation.
   */
  public function testGetSeveritiesDeprecation(): void {
    $this->expectDeprecation('Calling Drupal\Core\Render\Element\StatusReport::getSeverities() is deprecated in drupal:11.2.0 and is removed from in drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3410939');
    $severities = StatusReport::getSeverities();
    $this->assertIsArray($severities);
  }

}
