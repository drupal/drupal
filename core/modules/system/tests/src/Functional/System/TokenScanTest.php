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

    $this->assertArrayHasKey('simple', $token_wannabes['valid']);
    $this->assertNotNull($token_wannabes['valid']['simple'], 'A simple valid token has been matched.');
    $this->assertArrayHasKey('token with: spaces', $token_wannabes['valid']);
    $this->assertNotNull($token_wannabes['valid']['token with: spaces'], 'A valid token with space characters in the token name has been matched.');
    // Verify that an invalid token with spaces in the token type has not been
    // matched.
    $this->assertArrayNotHasKey('not valid', $token_wannabes);
    // Verify that an empty token has not been matched.
    $this->assertArrayNotHasKey('empty token', $token_wannabes);
    $this->assertFalse(isset($token_wannabes['']['empty token type']), 'An empty token type has not been matched.');
    $this->assertFalse(isset($token_wannabes['']['']), 'An empty token and type has not been matched.');
    // Verify that an existing valid token has been matched.
    $this->assertArrayHasKey('node', $token_wannabes);
    $this->assertNotNull($token_wannabes['node']);
  }

}
