<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\ScriptTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests core shell scripts.
 *
 * @group system
 */
class ScriptTest extends DrupalUnitTestBase {

  /**
   * Tests password-hash.sh.
   */
  public function testPasswordHashSh() {
    // The script requires a settings.php with a hash salt setting.
    $filename = $this->siteDirectory . '/settings.php';
    touch($filename);
    $settings['settings']['hash_salt'] = (object) array(
      'value' => 'some_random_key',
      'required' => TRUE,
    );
    drupal_rewrite_settings($settings, $filename);
    $_SERVER['argv'] = array(
      'core/scripts/password-hash.sh',
      'xyz',
    );
    ob_start();
    include DRUPAL_ROOT . '/core/scripts/password-hash.sh';
    $this->setRawContent(ob_get_contents());
    ob_end_clean();
    $this->assertRaw('hash: $S$');
  }

  /**
   * Tests rebuild_token_calculator.sh.
   */
  public function testRebuildTokenCalculatorSh() {
    // The script requires a settings.php with a hash salt setting.
    $filename = $this->siteDirectory . '/settings.php';
    touch($filename);
    $settings['settings']['hash_salt'] = (object) array(
      'value' => 'some_random_key',
      'required' => TRUE,
    );
    drupal_rewrite_settings($settings, $filename);
    $_SERVER['argv'] = array(
      'core/scripts/rebuild_token_calculator.sh',
    );
    ob_start();
    include DRUPAL_ROOT . '/core/scripts/rebuild_token_calculator.sh';
    $this->setRawContent(ob_get_contents());
    ob_end_clean();
    $this->assertRaw('token=');
  }

}
