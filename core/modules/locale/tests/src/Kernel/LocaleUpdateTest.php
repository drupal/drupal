<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\locale\LocaleProjectRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for updating the interface translations of projects.
 */
#[Group('locale')]
#[RunTestsInSeparateProcesses]
class LocaleUpdateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'locale',
    'locale_test',
  ];

  /**
   * Checks if a list of translatable projects gets build.
   */
  public function testUpdateProjects(): void {
    // Make the test modules look like a normal custom module. I.e. make the
    // modules not hidden. locale_test_system_info_alter() modifies the project
    // info of the locale_test and locale_test_translate modules.
    \Drupal::state()->set('locale.test_system_info_alter', TRUE);

    // Check if interface translation data is collected from hook_info.
    \Drupal::service(LocaleProjectRepository::class)->buildProjects();
    $projects = \Drupal::service(LocaleProjectRepository::class)->getAll();
    $this->assertArrayNotHasKey('locale_test_translate', $projects);
    $this->assertEquals('locale_test', $projects['locale_test']->name);
  }

}
