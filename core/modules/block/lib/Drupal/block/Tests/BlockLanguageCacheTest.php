<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockLanguageCacheTest.
 */

namespace Drupal\block\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests multilingual block definition caching.
 */
class BlockLanguageCacheTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'language', 'menu');

  /**
   * List of langcodes.
   *
   * @var array
   */
  protected $langcodes = array();

  public static function getInfo() {
    return array(
      'name' => 'Multilingual blocks',
      'description' => 'Checks display of menu blocks with multiple languages.',
      'group' => 'Block',
    );
  }

  public function setUp() {
    parent::setUp();

    // Create test languages.
    $this->langcodes = array(language_load('en'));
    for ($i = 1; $i < 3; ++$i) {
      $language = new Language(array(
        'id' => 'l' . $i,
        'name' => $this->randomString(),
      ));
      language_save($language);
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
    }

    // Create a menu in the default language.
    $edit['label'] = $this->randomName();
    $edit['id'] = Unicode::strtolower($edit['label']);
    $this->drupalPostForm('admin/structure/menu/add', $edit, t('Save'));
    $this->assertText(t('Menu @label has been added.', array('@label' => $edit['label'])));

    // Check that the block is listed for all languages.
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet('admin/structure/block', array('language' => $langcode));
      $this->assertText($edit['label']);
    }
  }
}
