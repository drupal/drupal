<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\Extension\ThemeEngineExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the extension path resolver deprecations.
 *
 * @group legacy
 * @group Module
 */
class LegacyExtensionPathResolverTest extends KernelTestBase {

  /**
   * @group legacy
   */
  public function testDeprecatedWarning() {
    $this->expectDeprecation('Calling getPathname() with an invalid $type parameter is deprecated in drupal:9.3.0 and will throw an \Drupal\Core\Extension\Exception\UnknownExtensionTypeException in drupal:10.0.0. See https://www.drupal.org/node/2940438');
    $module_extension_list = $this->prophesize(ModuleExtensionList::class);
    $profile_extension_list = $this->prophesize(ProfileExtensionList::class);
    $theme_extension_list = $this->prophesize(ThemeExtensionList::class);
    $theme_engine_extension_list = $this->prophesize(ThemeEngineExtensionList::class);
    $resolver = new ExtensionPathResolver(
      $module_extension_list->reveal(),
      $profile_extension_list->reveal(),
      $theme_extension_list->reveal(),
      $theme_engine_extension_list->reveal()
    );

    $this->assertEmpty($resolver->getPath('foo', 'bar'));

  }

}
