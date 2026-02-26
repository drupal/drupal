<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Render\Element;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Render\Element\StatusReport;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

include_once \DRUPAL_ROOT . '/core/includes/install.inc';

/**
 * Tests the status report element.
 */
#[Group('Render')]
#[RunTestsInSeparateProcesses]
class StatusReportTest extends KernelTestBase {

  /**
   * Tests the status report element.
   */
  public function testPreRenderGroupRequirements(): void {
    $element = [
      '#priorities' => [
        'error',
        'warning',
        'checked',
        'ok',
      ],
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
        'bar' => [
          'title' => 'Bar',
          'severity' => RequirementSeverity::OK,
        ],
      ],
    ];

    $element = StatusReport::preRenderGroupRequirements($element);
    $groups = $element['#grouped_requirements'];

    $errors = $groups['error'];
    $this->assertEquals('Errors found', (string) $errors['title']);
    $this->assertEquals('error', $errors['type']);
    $errorItems = $errors['items'];
    $this->assertCount(1, $errorItems);
    $this->assertArrayHasKey('wiz', $errorItems);

    $warnings = $groups['warning'];
    $this->assertEquals('Warnings found', (string) $warnings['title']);
    $this->assertEquals('warning', $warnings['type']);
    $warningItems = $warnings['items'];
    $this->assertCount(1, $warningItems);
    $this->assertArrayHasKey('baz', $warningItems);

    $checked = $groups['checked'];
    $this->assertEquals('Checked', (string) $checked['title']);
    $this->assertEquals('checked', $checked['type']);
    $checkedItems = $checked['items'];
    $this->assertCount(2, $checkedItems);
    $this->assertArrayHasKey('foo', $checkedItems);
    $this->assertArrayHasKey('bar', $checkedItems);
  }

}
