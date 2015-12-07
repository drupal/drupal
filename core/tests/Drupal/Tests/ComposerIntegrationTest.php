<?php

/**
 * @file
 * Contains \Drupal\Tests\ComposerIntegrationTest.
 */

namespace Drupal\Tests;

/**
 * Tests Composer integration.
 *
 * @group Composer
 */
class ComposerIntegrationTest extends UnitTestCase {

  /**
   * Gets human-readable JSON error messages.
   *
   * @return string[]
   *   Keys are JSON_ERROR_* constants.
   */
  protected function getErrorMessages() {
    $messages = [
      0 => 'No errors found',
      JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
      JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
      JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
      JSON_ERROR_SYNTAX => 'Syntax error',
      JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
    ];

    if (version_compare(phpversion(), '5.5.0', '>=')) {
      $messages[JSON_ERROR_RECURSION] = 'One or more recursive references in the value to be encoded';
      $messages[JSON_ERROR_INF_OR_NAN] = 'One or more NAN or INF values in the value to be encoded';
      $messages[JSON_ERROR_UNSUPPORTED_TYPE] = 'A value of a type that cannot be encoded was given';
    }

    return $messages;
  }

  /**
   * Gets the paths to the folders that contain the Composer integration.
   *
   * @return string[]
   *   The paths.
   */
  protected function getPaths() {
    return [
      $this->root,
      $this->root . '/core',
      $this->root . '/core/lib/Drupal/Component/Bridge',
      $this->root . '/core/lib/Drupal/Component/Gettext',
      $this->root . '/core/lib/Drupal/Component/Plugin',
      $this->root . '/core/lib/Drupal/Component/ProxyBuilder',
      $this->root . '/core/lib/Drupal/Component/Utility',
    ];
  }

  /**
   * Tests composer.json.
   */
  public function testComposerJson() {
    foreach ($this->getPaths() as $path) {
      $json = file_get_contents($path . '/composer.json');

      $result = json_decode($json);
      $this->assertNotNull($result, $this->getErrorMessages()[json_last_error()]);
    }
  }

  /**
   * Tests core's composer.json replace section.
   *
   * Verify that all core modules are also listed in the 'replace' section of
   * core's composer.json.
   */
  public function testAllModulesReplaced() {
    // Assemble a path to core modules.
    $module_path = $this->root . '/core/modules';

    // Grab the 'replace' section of the core composer.json file.
    $json = json_decode(file_get_contents($this->root . '/core/composer.json'));
    $composer_replace_packages = (array) $json->replace;

    // Get a list of all the files in the module path.
    $folders = scandir($module_path);

    // Make sure we only deal with directories that aren't . or ..
    $module_names = [];
    $discard = ['.', '..'];
    foreach ($folders as $file_name) {
      if ((!in_array($file_name, $discard)) && is_dir($module_path . '/' . $file_name)) {
        $module_names[] = $file_name;
      }
    }

    // Assert that each core module has a corresponding 'replace' in
    // composer.json.
    foreach ($module_names as $module_name) {
      $this->assertArrayHasKey(
        'drupal/' . $module_name,
        $composer_replace_packages,
        'Unable to find ' . $module_name . ' in replace list of composer.json'
      );
    }
  }

}
