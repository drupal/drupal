<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\ScriptTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests core shell scripts.
 */
class ScriptTest extends UnitTestBase {

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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $path_parts = explode(DIRECTORY_SEPARATOR, __DIR__);
    // This file is 8 levels below the Drupal root.
    $root = implode(DIRECTORY_SEPARATOR, array_slice($path_parts, 0, -8));
    chdir($root);
  }

  /**
   * Tests password-hash.sh.
   */
  public function testPasswordHashSh() {
    $cmd = 'core/scripts/password-hash.sh xyz';
    exec($cmd, $output, $exit_code);
    $this->assertIdentical(0, $exit_code, 'Exit code');
    $this->assertTrue(strpos(implode(' ', $output), 'hash: $S$') !== FALSE);
  }

  /**
   * Tests rebuild_token_calculator.sh.
   */
  public function testRebuildTokenCalculatorSh() {
    $cmd = 'core/scripts/rebuild_token_calculator.sh';
    exec($cmd, $output, $exit_code);
    $this->assertIdentical(0, $exit_code, 'Exit code');
    $this->assertTrue(strpos(implode(' ', $output), 'token=') !== FALSE);
  }

}
