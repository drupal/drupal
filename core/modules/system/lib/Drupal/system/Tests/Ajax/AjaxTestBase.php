<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Ajax\AjaxTestBase.
 */

namespace Drupal\system\Tests\Ajax;

use Drupal\simpletest\WebTestBase;

/**
 * Base test system for AJAX tests.
 */
class AjaxTestBase extends WebTestBase {
  function setUp() {
    $modules = func_get_args();
    if (isset($modules[0]) && is_array($modules[0])) {
      $modules = $modules[0];
    }
    parent::setUp(array_unique(array_merge(array('ajax_test', 'ajax_forms_test'), $modules)));
  }

  /**
   * Assert that a command with the required properties exists within the array of Ajax commands returned by the server.
   *
   * The Ajax framework, via the ajax_deliver() and ajax_render() functions,
   * returns an array of commands. This array sometimes includes commands
   * automatically provided by the framework in addition to commands returned by
   * a particular page callback. During testing, we're usually interested that a
   * particular command is present, and don't care whether other commands
   * precede or follow the one we're interested in. Additionally, the command
   * we're interested in may include additional data that we're not interested
   * in. Therefore, this function simply asserts that one of the commands in
   * $haystack contains all of the keys and values in $needle. Furthermore, if
   * $needle contains a 'settings' key with an array value, we simply assert
   * that all keys and values within that array are present in the command we're
   * checking, and do not consider it a failure if the actual command contains
   * additional settings that aren't part of $needle.
   *
   * @param $haystack
   *   An array of Ajax commands returned by the server.
   * @param $needle
   *   Array of info we're expecting in one of those commands.
   * @param $message
   *   An assertion message.
   */
  protected function assertCommand($haystack, $needle, $message) {
    $found = FALSE;
    foreach ($haystack as $command) {
      // If the command has additional settings that we're not testing for, do
      // not consider that a failure.
      if (isset($command['settings']) && is_array($command['settings']) && isset($needle['settings']) && is_array($needle['settings'])) {
        $command['settings'] = array_intersect_key($command['settings'], $needle['settings']);
      }
      // If the command has additional data that we're not testing for, do not
      // consider that a failure. Also, == instead of ===, because we don't
      // require the key/value pairs to be in any particular order
      // (http://www.php.net/manual/language.operators.array.php).
      if (array_intersect_key($command, $needle) == $needle) {
        $found = TRUE;
        break;
      }
    }
    $this->assertTrue($found, $message);
  }
}
