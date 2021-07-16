<?php

namespace Drupal\KernelTests\Core\Bootstrap;

use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that extension path resolver works correctly.
 *
 * @coversDefaultClass \Drupal\Core\Extension\ExtensionPathResolver
 *
 * @group Bootstrap
 */
class ExtensionPathResolverTest extends KernelTestBase {

  /**
   * @covers ::getPathname
   */
  public function testExtensionPathResolving(): void {
    // Retrieving the location of a module.
    $this->assertSame('core/modules/system/system.info.yml', \Drupal::service('extension.list.module')
      ->getPathname('system'));

    // Retrieving the location of a theme.
    \Drupal::service('theme_installer')->install(['stark']);
    $this->assertSame('core/themes/stark/stark.info.yml', \Drupal::service('extension.list.theme')
      ->getPathname('stark'));

    // Retrieving the location of a theme engine.
    $this->assertSame('core/themes/engines/twig/twig.info.yml', \Drupal::service('extension.list.theme_engine')
      ->getPathname('twig'));

    // Retrieving the location of a profile. Profiles are a special case with
    // a fixed location and naming.
    $this->assertSame('core/profiles/testing/testing.info.yml', \Drupal::service('extension.list.profile')
      ->getPathname('testing'));
  }

  /**
   * @covers ::getPath
   */
  public function testExtensionPathResolvingPath(): void {
    $this->assertSame('core/modules/system/tests/modules/driver_test', \Drupal::service('extension.list.module')
      ->getPath('driver_test'));
  }

  /**
   * @covers ::getPathname
   */
  public function testExtensionPathResolvingWithNonExistingModule(): void {
    $this->expectException(UnknownExtensionException::class);
    $this->expectExceptionMessage('The module there_is_a_module_for_that does not exist.');
    $this->assertNull(\Drupal::service('extension.list.module')
      ->getPathname('there_is_a_module_for_that'), 'Searching for an item that does not exist returns NULL.');
  }

  /**
   * @covers ::getPathname
   */
  public function testExtensionPathResolvingWithNonExistingTheme(): void {
    $this->expectException(UnknownExtensionException::class);
    $this->expectExceptionMessage('The theme there_is_a_theme_for_you does not exist.');
    $this->assertNull(\Drupal::service('extension.list.theme')
      ->getPathname('there_is_a_theme_for_you'), 'Searching for an item that does not exist returns NULL.');
  }

  /**
   * @covers ::getPathname
   */
  public function testExtensionPathResolvingWithNonExistingProfile(): void {
    $this->expectException(UnknownExtensionException::class);
    $this->expectExceptionMessage('The profile there_is_an_install_profile_for_you does not exist.');
    $this->assertNull(\Drupal::service('extension.list.profile')
      ->getPathname('there_is_an_install_profile_for_you'), 'Searching for an item that does not exist returns NULL.');
  }

  /**
   * @covers ::getPathname
   */
  public function testExtensionPathResolvingWithNonExistingThemeEngine(): void {
    $this->expectException(UnknownExtensionException::class);
    $this->expectExceptionMessage('The theme_engine there_is_an_theme_engine_for_you does not exist');
    $this->assertNull(\Drupal::service('extension.list.theme_engine')
      ->getPathname('there_is_an_theme_engine_for_you'), 'Searching for an item that does not exist returns NULL.');
  }

}
