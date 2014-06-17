<?php

/**
 * @file
 * Contains Drupal\system\Tests\Installer\InstallerTranslationVersionUnitTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests translation release version fallback.
 */
class InstallerTranslationVersionUnitTest extends DrupalUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Installer translation version fallback',
      'description' => 'Tests the translation version fallback used during site installation to determine available translation files.',
      'group' => 'Installer',
    );
  }

  protected function setUp() {
    parent::setUp();
    require_once DRUPAL_ROOT . '/core/includes/install.core.inc';
  }

  /**
   * Asserts version fallback results of install_get_localization_release().
   *
   * @param $version
   *   Version string for which to determine version fallbacks.
   * @param $fallback
   *   Array of fallback versions ordered for most to least significant.
   * @param string $message
   *   (optional) A message to display with the assertion.
   * @param string $group
   *   (optional) The group this message is in.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertVersionFallback($version, $fallback, $message = '', $group = 'Other') {
    $equal = TRUE;
    $results = install_get_localization_release($version);
    // Check the calculated results with the required results.
    // The $results is an array of arrays, each containing:
    //   'version': A release version (e.g. 8.0)
    //   'core'   : The matching core version (e.g. 8.x)
    if (count($fallback) == count($results)) {
      foreach($results as $key => $result) {
        $equal &= $result['version'] == $fallback[$key];
        list($major_release) = explode('.', $fallback[$key]);
        $equal &= $result['core'] == $major_release . '.x';
      }
    }
    else {
      $equal = FALSE;
    }
    $message = $message ? $message : t('Version fallback for @version.', array('@version' => $version));
    return $this->assert((bool) $equal, $message, $group);
  }

  /**
   * Tests version fallback of install_get_localization_release().
   */
  public function testVersionFallback() {
    $version = '8.0';
    $fallback = array('8.0', '8.0-rc1', '7.0');
    $this->assertVersionFallback($version, $fallback);

    $version = '8.1';
    $fallback = array('8.1', '8.0', '7.0');
    $this->assertVersionFallback($version, $fallback);

    $version = '8.12';
    $fallback = array('8.12', '8.11', '7.0');
    $this->assertVersionFallback($version, $fallback);

    $version = '8.0-dev';
    $fallback = array('8.0-rc1', '8.0-beta1', '8.0-alpha12', '7.0');
    $this->assertVersionFallback($version, $fallback);

    $version = '8.9-dev';
    $fallback = array('8.8', '7.0');
    $this->assertVersionFallback($version, $fallback);

    $version = '8.0-alpha3';
    $fallback = array('8.0-alpha3', '8.0-alpha2', '7.0');
    $this->assertVersionFallback($version, $fallback);

    $version = '8.0-alpha1';
    $fallback = array('8.0-alpha1', '7.0');
    $this->assertVersionFallback($version, $fallback);

    $version = '8.0-beta2';
    $fallback = array('8.0-beta2', '8.0-beta1', '7.0');
    $this->assertVersionFallback($version, $fallback);

    $version = '8.0-beta1';
    $fallback = array('8.0-beta1', '8.0-alpha2', '7.0');
    $this->assertVersionFallback($version, $fallback);

    $version = '8.0-rc8';
    $fallback = array('8.0-rc8', '8.0-rc7', '7.0');
    $this->assertVersionFallback($version, $fallback);

    $version = '8.0-rc1';
    $fallback = array('8.0-rc1', '8.0-beta1', '7.0');
    $this->assertVersionFallback($version, $fallback);

    $version = '8.0-foo2';
    $fallback = array('7.0');
    $this->assertVersionFallback($version, $fallback);

    $version = '99.2';
    $fallback = array('99.2', '99.1', '98.0');
    $this->assertVersionFallback($version, $fallback);
  }
}
