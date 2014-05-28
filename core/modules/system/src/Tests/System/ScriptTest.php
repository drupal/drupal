<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\ScriptTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Component\Utility\String;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests core shell scripts.
 */
class ScriptTest extends DrupalUnitTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Shell scripts',
      'description' => 'Tests Core utility shell scripts.',
      'group' => 'System',
    );
  }

  /**
   * Tests password-hash.sh.
   */
  public function testPasswordHashSh() {
    $_SERVER['argv'] = array(
      'core/scripts/password-hash.sh',
      'xyz',
    );
    ob_start();
    include DRUPAL_ROOT . '/core/scripts/password-hash.sh';
    $this->content = ob_get_contents();
    ob_end_clean();
    $this->assertRaw('hash: $S$');
  }

  /**
   * Tests rebuild_token_calculator.sh.
   */
  public function testRebuildTokenCalculatorSh() {
    $_SERVER['argv'] = array(
      'core/scripts/rebuild_token_calculator.sh',
    );
    ob_start();
    include DRUPAL_ROOT . '/core/scripts/rebuild_token_calculator.sh';
    $this->content = ob_get_contents();
    ob_end_clean();
    $this->assertRaw('token=');
  }

  /**
   * Asserts that a given string is found in $this->content.
   *
   * @param string $string
   *   The raw string to assert.
   */
  protected function assertRaw($string) {
    return $this->assert(strpos($this->content, $string) !== FALSE, String::format('Raw @value found in @output.', array(
      '@value' => var_export($string, TRUE),
      '@output' => var_export($this->content, TRUE),
    )));
  }

}
