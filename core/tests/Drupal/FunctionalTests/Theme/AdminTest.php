<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Theme;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Admin theme.
 */
#[Group('default_admin')]
#[RunTestsInSeparateProcesses]
class AdminTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * Install dblog and pager_test for testing of pager attributes.
   *
   * @var string[]
   */
  protected static $modules = ['dblog', 'pager_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'default_admin';

  /**
   * Testing that Admin theme's global library is always attached.
   *
   * @see default_admin.info.yml
   */
  public function testRegressionMissingElementsCss(): void {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    // This can be any CSS file from the global library.
    $this->assertSession()->responseContains('default_admin/css/base/elements.css');
  }

  /**
   * Tests Admin's configuration schema.
   */
  public function testConfigSchema(): void {
    $permissions = [
      'administer modules',
    ];
    $this->drupalLogin($this->drupalCreateUser($permissions));
    $this->drupalGet('admin/modules');
    $this->assertSession()->elementNotExists('css', '#block-default-admin-help');

    // Install the block module to ensure Admin's configuration is valid
    // according to schema.
    \Drupal::service('module_installer')->install(['block', 'help']);
    $this->rebuildAll();

    $this->drupalGet('admin/modules');
    $this->assertSession()->elementExists('css', '#block-default-admin-help');
  }

  /**
   * Tests that the Admin theme can be uninstalled.
   */
  public function testIsUninstallable(): void {
    $this->drupalLogin($this->drupalCreateUser(['access administration pages', 'administer themes']));

    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install <strong>Test theme</strong> as default theme"]')[0]->click();

    $this->cssSelect('a[title="Uninstall Default Admin theme"]')[0]->click();
    $this->submitForm([], 'Uninstall');
    $this->assertSession()->pageTextContains('The Default Admin theme has been uninstalled.');

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
    $elements = $this->getNodeElementsByXpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
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
