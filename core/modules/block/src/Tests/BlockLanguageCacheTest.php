<?php

namespace Drupal\block\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests display of menu blocks with multiple languages.
 *
 * @group block
 */
class BlockLanguageCacheTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('block', 'language', 'menu_ui');

  /**
   * List of langcodes.
   *
   * @var array
   */
  protected $langcodes = array();

  protected function setUp() {
    parent::setUp();

    // Create test languages.
    $this->langcodes = array(ConfigurableLanguage::load('en'));
    for ($i = 1; $i < 3; ++$i) {
      $language = ConfigurableLanguage::create(array(
        'id' => 'l' . $i,
        'label' => $this->randomString(),
      ));
      $language->save();
      $this->langcodes[$i] = $language;
    }
  }

  /**
   * Creates a block in a language, check blocks page in all languages.
   */
  public function testBlockLinks() {
    // Create admin user to be able to access block admin.
    $admin_user = $this->drupalCreateUser(array(
      'administer blocks',
      'access administration pages',
      'administer menu',
    ));
    $this->drupalLogin($admin_user);

    // Create the block cache for all languages.
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet('admin/structure/block', array('language' => $langcode));
      $this->clickLinkPartialName('Place block');
    }

    // Create a menu in the default language.
    $edit['label'] = $this->randomMachineName();
    $edit['id'] = Unicode::strtolower($edit['label']);
    $this->drupalPostForm('admin/structure/menu/add', $edit, t('Save'));
    $this->assertText(t('Menu @label has been added.', array('@label' => $edit['label'])));

    // Check that the block is listed for all languages.
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet('admin/structure/block', array('language' => $langcode));
      $this->clickLinkPartialName('Place block');
      $this->assertText($edit['label']);
    }
  }
}
