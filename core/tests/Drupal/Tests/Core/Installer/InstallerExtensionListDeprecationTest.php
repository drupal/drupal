<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Installer;

use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests deprecations for installer extension list compatibility layers.
 */
#[Group('Installer')]
#[IgnoreDeprecations]
class InstallerExtensionListDeprecationTest extends UnitTestCase {

  /**
   * Tests the installer extension list trait deprecation.
   */
  public function testExtensionListTraitDeprecation(): void {
    $this->expectUserDeprecationMessage('Drupal\Core\Installer\ExtensionListTrait is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. There is no replacement; the base extension list behavior now provides the same functionality. See https://www.drupal.org/node/3577846');
    $this->assertTrue(trait_exists('Drupal\Core\Installer\ExtensionListTrait'));
  }

  /**
   * Tests the installer module extension list deprecation.
   */
  public function testInstallerModuleExtensionListDeprecation(): void {
    $this->expectUserDeprecationMessage('Drupal\Core\Installer\InstallerModuleExtensionList is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use Drupal\Core\Extension\ModuleExtensionList instead. See https://www.drupal.org/node/3577846');
    $this->assertTrue(class_exists('Drupal\Core\Installer\InstallerModuleExtensionList'));
  }

  /**
   * Tests the installer theme extension list deprecation.
   */
  public function testInstallerThemeExtensionListDeprecation(): void {
    $this->expectUserDeprecationMessage('Drupal\Core\Installer\InstallerThemeExtensionList is deprecated in drupal:11.5.0 and is removed from drupal:13.0.0. Use Drupal\Core\Extension\ThemeExtensionList instead. See https://www.drupal.org/node/3577846');
    $this->assertTrue(class_exists('Drupal\Core\Installer\InstallerThemeExtensionList'));
  }

}
