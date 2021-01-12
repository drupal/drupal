<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests autocompletion not loading registry.
 *
 * @group Theme
 */
class FastTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['theme_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    $this->account = $this->drupalCreateUser(['access user profiles']);
  }

  /**
   * Tests access to user autocompletion and verify the correct results.
   */
  public function testUserAutocomplete() {
    $this->drupalLogin($this->account);
    $this->drupalGet('user/autocomplete', ['query' => ['q' => $this->account->getAccountName()]]);
    $this->assertRaw($this->account->getAccountName());
    $this->assertNoText('registry initialized', 'The registry was not initialized');
  }

}
