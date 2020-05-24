<?php

namespace Drupal\Tests\migrate_drupal_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that only user 1 can access the migrate UI.
 *
 * @group migrate_drupal_ui
 */
class MigrateAccessTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['migrate_drupal_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that only user 1 can access the migrate UI.
   */
  public function testAccess() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('upgrade');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertText(t('Upgrade'));

    $user = $this->createUser(['administer software updates']);
    $this->drupalLogin($user);
    $this->drupalGet('upgrade');
    $this->assertSession()->statusCodeEquals(403);
    $this->assertNoText(t('Upgrade'));
  }

}
