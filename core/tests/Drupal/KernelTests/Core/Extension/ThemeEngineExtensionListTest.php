<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Extension\ThemeEngineExtensionList;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore nyan
/**
 * Tests Drupal\Core\Extension\ThemeEngineExtensionList.
 */
#[CoversClass(ThemeEngineExtensionList::class)]
#[Group('Extension')]
#[RunTestsInSeparateProcesses]
class ThemeEngineExtensionListTest extends KernelTestBase {

  /**
   * Tests get list.
   *
   * @legacy-covers ::getList
   */
  public function testGetList(): void {
    // Confirm that all theme engines are available.
    $theme_engines = \Drupal::service('extension.list.theme_engine')->getList();
    $this->assertArrayHasKey('twig', $theme_engines);
    $this->assertArrayHasKey('test_theme_engine', $theme_engines);
    $this->assertCount(2, $theme_engines);
  }

}
