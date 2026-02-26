<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Element;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\Element\StatusReportPage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

include_once \DRUPAL_ROOT . '/core/includes/install.inc';

/**
 * Tests the status report page element.
 */
#[Group('system')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class StatusReportPageTest extends KernelTestBase {

  /**
   * Tests the status report page element.
   */
  public function testPeRenderCounters(): void {
    $element = [
      '#requirements' => [
        'foo' => [
          'title' => 'Foo',
          'severity' => RequirementSeverity::Info,
        ],
        'baz' => [
          'title' => 'Baz',
          'severity' => RequirementSeverity::Warning,
        ],
        'wiz' => [
          'title' => 'Wiz',
          'severity' => RequirementSeverity::Error,
        ],
      ],
    ];
    $element = StatusReportPage::preRenderCounters($element);

    $error = $element['#counters']['error'];
    $this->assertEquals(1, $error['#amount']);
    $this->assertEquals('error', $error['#severity']);

    $warning = $element['#counters']['warning'];
    $this->assertEquals(1, $warning['#amount']);
    $this->assertEquals('warning', $warning['#severity']);

    $checked = $element['#counters']['checked'];
    $this->assertEquals(1, $checked['#amount']);
    $this->assertEquals('checked', $checked['#severity']);
  }

}
