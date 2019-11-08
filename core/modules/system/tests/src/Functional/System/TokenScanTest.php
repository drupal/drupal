<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Scan token-like patterns in a dummy text to check token scanning.
 *
 * @group system
 */
class TokenScanTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Scans dummy text, then tests the output.
   */
  public function testTokenScan() {
    // Define text with valid and not valid, fake and existing token-like
    // strings.
    $text = 'First a [valid:simple], but dummy token, and a dummy [valid:token with: spaces].';
    $text .= 'Then a [not valid:token].';
    $text .= 'Then an [:empty token type].';
    $text .= 'Then an [empty token:].';
    $text .= 'Then a totally empty token: [:].';
    $text .= 'Last an existing token: [node:author:name].';
    $token_wannabes = \Drupal::token()->scan($text);

    $this->assertTrue(isset($token_wannabes['valid']['simple']), 'A simple valid token has been matched.');
    $this->assertTrue(isset($token_wannabes['valid']['token with: spaces']), 'A valid token with space characters in the token name has been matched.');
    $this->assertFalse(isset($token_wannabes['not valid']), 'An invalid token with spaces in the token type has not been matched.');
    $this->assertFalse(isset($token_wannabes['empty token']), 'An empty token has not been matched.');
    $this->assertFalse(isset($token_wannabes['']['empty token type']), 'An empty token type has not been matched.');
    $this->assertFalse(isset($token_wannabes['']['']), 'An empty token and type has not been matched.');
    $this->assertTrue(isset($token_wannabes['node']), 'An existing valid token has been matched.');
  }

}
