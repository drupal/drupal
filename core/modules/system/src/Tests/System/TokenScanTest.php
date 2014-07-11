<?php

/**
 * @file
 * Contains Drupal\system\Tests\System\TokenScanTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Scan token-like patterns in a dummy text to check token scanning.
 *
 * @group system
 */
class TokenScanTest extends WebTestBase {

  /**
   * Scans dummy text, then tests the output.
   */
  function testTokenScan() {
    // Define text with valid and not valid, fake and existing token-like
    // strings.
    $text = 'First a [valid:simple], but dummy token, and a dummy [valid:token with: spaces].';
    $text .= 'Then a [not valid:token].';
    $text .= 'Last an existing token: [node:author:name].';
    $token_wannabes = \Drupal::token()->scan($text);

    $this->assertTrue(isset($token_wannabes['valid']['simple']), 'A simple valid token has been matched.');
    $this->assertTrue(isset($token_wannabes['valid']['token with: spaces']), 'A valid token with space characters in the token name has been matched.');
    $this->assertFalse(isset($token_wannabes['not valid']), 'An invalid token with spaces in the token type has not been matched.');
    $this->assertTrue(isset($token_wannabes['node']), 'An existing valid token has been matched.');
  }

}
