<?php

namespace Drupal\Tests\block\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests display of menu blocks with multiple languages.
 *
 * @group block
 */
class BlockLanguageCacheTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['block', 'language', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * List of langcodes.
   *
   * @var array
   */
  protected $langcodes = [];

  protected function setUp(): void {
    parent::setUp();

    // Create test languages.
    $this->langcodes = [ConfigurableLanguage::load('en')];
    for ($i = 1; $i < 3; ++$i) {
      $language = ConfigurableLanguage::create([
        'id' => 'l' . $i,
        'label' => $this->randomString(),
      ]);
      $language->save();
      $this->langcodes[$i] = $language;
    }
  }

  /**
   * Creates a block in a language, check blocks page in all languages.
   */
  public function testBlockLinks() {
    // Create admin user to be able to access block admin.
    $admin_user = $this->drupalCreateUser([
      'administer blocks',
      'access administration pages',
      'administer menu',
    ]);
    $this->drupalLogin($admin_user);

    // Create the block cache for all languages.
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet('admin/structure/block', ['language' => $langcode]);
      $this->clickLink('Place block');
    }

    // Create a menu in the default language.
    $edit['label'] = $this->randomMachineName();
    $edit['id'] = mb_strtolower($edit['label']);
    $this->drupalGet('admin/structure/menu/add');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Menu ' . $edit['label'] . ' has been added.');

    // Check that the block is listed for all languages.
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet('admin/structure/block', ['language' => $langcode]);
      $this->clickLink('Place block');
      $this->assertSession()->pageTextContains($edit['label']);
    }
  }

}
