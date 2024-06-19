<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Theme;

use Drupal\Tests\BrowserTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Tests the Olivero theme.
 *
 * @group olivero
 * @group #slow
 */
class OliveroTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'olivero';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'olivero_test',
    'pager_test',
    'dblog',
  ];

  /**
   * Tests that the Olivero theme always adds base library files.
   *
   * @see olivero.libraries.yml
   */
  public function testBaseLibraryAvailable(): void {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('olivero/css/base/base.css');
    $this->assertSession()->responseContains('olivero/js/navigation-utils.js');
  }

  /**
   * Test Olivero's configuration schema.
   */
  public function testConfigSchema(): void {
    // Required configuration.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', '#block-olivero-content');
    $this->assertSession()->elementNotExists('css', '#block-olivero-search-form-wide');

    // Optional configuration.
    \Drupal::service('module_installer')->install(
      ['search', 'image', 'help', 'node']
    );
    $this->rebuildAll();
    $this->drupalLogin(
      $this->drupalCreateUser(['search content'])
    );

    // Confirm search block was installed.
    $this->assertSession()->elementExists('css', '#block-olivero-search-form-wide');
  }

  /**
   * Tests that olivero_preprocess_block is functioning as expected.
   *
   * @see olivero.libraries.yml
   */
  public function testPreprocessBlock(): void {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);

    // Confirm that search narrow and search wide libraries haven't yet been
    // added.
    $this->assertSession()->responseNotContains('olivero/css/components/header-search-wide.css');
    $this->assertSession()->responseNotContains('olivero/css/components/header-search-narrow.css');

    // Enable modules that will exercise preprocess block logic.
    \Drupal::service('module_installer')->install(
      ['search', 'menu_link_content']
    );

    // Add at least one link to the main menu.
    $parent_menu_link_content = MenuLinkContent::create([
      'title' => 'Home',
      'menu_name' => 'main',
      'link' => ['uri' => 'route:<front>'],
    ]);
    $parent_menu_link_content->save();

    // Set branding color.
    $system_theme_config = $this->container->get('config.factory')->getEditable('olivero.settings');
    $system_theme_config
      ->set('site_branding_bg_color', 'gray')
      ->save();

    $this->rebuildAll();
    $this->drupalLogin(
      $this->drupalCreateUser(['search content'])
    );

    // Confirm that search narrow and search wide libraries were added by
    // preprocess.
    $this->assertSession()->responseContains('olivero/css/components/header-search-wide.css');
    $this->assertSession()->responseContains('olivero/css/components/header-search-narrow.css');

    // Confirm primary-nav class was added to main menu navigation block.
    $this->assertSession()->elementExists('css', '#block-olivero-main-menu.primary-nav');

    // Ensure branding background color class was added.
    $this->assertSession()->elementExists('css', '#block-olivero-site-branding.site-branding--bg-gray');
  }

  /**
   * Tests that the Olivero theme can be uninstalled.
   */
  public function testIsUninstallable(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer themes',
    ]));

    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Install <strong>Test theme</strong> as default theme"]')[0]->click();
    $this->cssSelect('a[title="Uninstall Olivero theme"]')[0]->click();
    $this->assertSession()->pageTextContains('The Olivero theme has been uninstalled.');
  }

  /**
   * Tests pager attribute is present using pager_test.
   */
  public function testPagerAttribute(): void {
    // Insert 300 log messages.
    $logger = \Drupal::logger('pager_test');
    for ($i = 0; $i < 300; $i++) {
      $logger->debug($this->randomString());
    }

    $this->drupalLogin($this->drupalCreateUser(['access site reports']));

    $this->drupalGet('pager-test/multiple-pagers', ['query' => ['page' => 1]]);
    $this->assertSession()->statusCodeEquals(200);
    $elements = $this->xpath('//ul[contains(@class, :class)]/li', [':class' => 'pager__items']);
    $this->assertNotEmpty($elements, 'Pager found.');

    // Check all links for pager-test attribute.
    foreach ($elements as $element) {
      $link = $element->find('css', 'a');
      // Current page does not have a link.
      if (empty($link)) {
        continue;
      }
      $this->assertTrue($link->hasAttribute('pager-test'), 'Pager item has attribute pager-test');
      $this->assertTrue($link->hasClass('lizards'));
    }
  }

}
