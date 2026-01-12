<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\Core\Extension\ThemeExtensionList.
 */
#[CoversClass(ThemeExtensionList::class)]
#[Group('Extension')]
#[RunTestsInSeparateProcesses]
class ThemeExtensionListTest extends KernelTestBase {

  /**
   * Tests get list.
   */
  public function testGetList(): void {
    \Drupal::configFactory()->getEditable('core.extension')
      ->set('module.testing', 1000)
      ->set('theme.test_theme', 0)
      ->set('profile', 'testing')
      ->save();

    // The installation profile is provided by a container parameter.
    // Saving the configuration doesn't automatically trigger invalidation.
    $this->container->get('kernel')->rebuildContainer();

    /** @var \Drupal\Core\Extension\ThemeExtensionList $theme_extension_list */
    $theme_extension_list = \Drupal::service('extension.list.theme');
    $extensions = $theme_extension_list->getList();

    $this->assertArrayHasKey('test_theme', $extensions);
  }

  /**
   * Tests that themes have an empty default version set.
   */
  public function testThemeWithoutVersion(): void {
    $theme = \Drupal::service('extension.list.theme')->get('test_theme_settings_features');
    $this->assertNull($theme->info['version']);
  }

}
