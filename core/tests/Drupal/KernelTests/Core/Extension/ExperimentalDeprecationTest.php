<?php

namespace Drupal\KernelTests\Core\Extension;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated update.inc functions.
 *
 * @group legacy
 * @group extension
 *
 * @todo Remove this and all its test themes in
 *   https://www.drupal.org/node/3321634
 */
class ExperimentalDeprecationTest extends KernelTestBase {

  /**
   * Tests \Drupal\Core\Extension\Extension::isExperimental deprecation.
   */
  public function testLegacyIsExperimental(): void {
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = \Drupal::service('theme_installer');
    $theme_installer->install(['legacy_experimental_theme_test']);

    /** @var \Drupal\Core\Extension\ThemeHandler $theme_handler */
    $theme_handler = $this->container->get('theme_handler');
    $theme = $theme_handler->getTheme('legacy_experimental_theme_test');
    $this->expectDeprecation('The key-value pair "experimental: true" is deprecated in drupal:10.1.0 and will be removed before drupal:11.0.0. Use the key-value pair "lifecycle: experimental" instead. See https://www.drupal.org/node/3263585');
    $this->assertTrue($theme->isExperimental());
  }

}
