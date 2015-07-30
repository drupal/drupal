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
      if (is_null($result)) {
        $this->fail($this->getErrorMessages()[json_last_error()]);
      }
    }
  }

}
