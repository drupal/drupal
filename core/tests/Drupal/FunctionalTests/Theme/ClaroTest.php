<?php

namespace Drupal\FunctionalTests\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Claro theme.
 *
 * @group claro
 */
class ClaroTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * Install the shortcut module so that claro.settings has its schema checked.
   * There's currently no way for Claro to provide a default and have valid
   * configuration as themes cannot react to a module install.
   *
   * Install dblog and pager_test for testing of pager attributes.
   *
   * @var string[]
   */
  protected static $modules = ['dblog', 'shortcut', 'pager_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * Testing that Claro theme's global library is always attached.
   *
   * @see claro.info.yml
   */
  public function testRegressionMissingElementsCss() {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    // This can be any CSS file from the global library.
    $this->assertSession()->responseContains('claro/css/base/elements.css');
  }

  /**
   * Tests Claro's configuration schema.
   */
  public function testConfigSchema() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/modules');
    $this->assertSession()->elementNotExists('css', '#block-claro-help');

    // Install the block module to ensure Claro's configuration is valid
    // according to schema.
    \Drupal::service('module_installer')->install(['block', 'help']);
    $this->rebuildAll();

    $this->drupalGet('admin/modules');
    $this->assertSession()->elementExists('css', '#block-claro-help');
  }

  /**
   * Tests that the Claro theme can be uninstalled.
   */
  public function testIsUninstallable() {
    $this->drupalLogin($this->drupalCreateUser(['access administration pages', 'administer themes']));

    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install <strong>Test theme</strong> as default theme"]')[0]->click();
    $this->cssSelect('a[title="Uninstall Claro theme"]')[0]->click();
    $this->assertSession()->pageTextContains('The Claro theme has been uninstalled.');
  }

  /**
   * Tests pager attribute is present using pager_test.
   */
  public function testPagerAttribute(): void {
    // Insert 300 log messages.
    $logger = $this->container->get('logger.factory')->get('pager_test');
    for ($i = 0; $i < 300; $i++) {
      $logger->debug($this->randomString());
    }

    $this->drupalLogin($this->drupalCreateUser(['access site reports']));

    $this->drupalGet('admin/reports/dblog', ['query' => ['page' => 1]]);
    $this->assertSession()->statusCodeEquals(200);
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
    $this->assertNotEmpty($elements, 'Pager found.');

    // Check all links for pager-test attribute.
    foreach ($elements as $page => $element) {
      $link = $element->find('css', 'a');
      $this->assertNotEmpty($link, "Link to page $page found.");
      $this->assertTrue($link->hasAttribute('pager-test'), 'Pager item has attribute pager-test');
      $this->assertTrue($link->hasClass('lizards'));
    }
  }

}
