<?php

namespace Drupal\Tests\locale\Functional;

/**
 * Tests installing in a different language with a dev version string.
 *
 * @group locale
 */
class LocaleNonInteractiveDevInstallTest extends LocaleNonInteractiveInstallTest {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function getVersionStringToTest() {
    include_once $this->root . '/core/includes/install.core.inc';
    $version = _install_get_version_info(\Drupal::VERSION);
    return $version['major'] . '.' . $version['minor'] . '.x';
  }

}
