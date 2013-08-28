<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockLanguageCacheTest.
 */

namespace Drupal\block\Tests;

use Drupal\Core\Language\Language;
use Drupal\aggregator\Tests\AggregatorTestBase;

/**
 * Tests multilingual block definition caching.
 */
class BlockLanguageCacheTest extends AggregatorTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'language');

  /**
   * List of langcodes.
   *
   * @var array
   */
  protected $langcodes = array();

  public static function getInfo() {
    return array(
      'name' => 'Multilingual blocks',
      'description' => 'Checks display of aggregator blocks with multiple languages.',
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
      'administer news feeds',
      'access news feeds',
      'create article content',
      'administer languages',
    ));
    $this->drupalLogin($admin_user);

    // Create the block cache for all languages.
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet('admin/structure/block', array('language' => $langcode));
    }

    // Create a feed in the default language.
    $this->createSampleNodes();
    $feed = $this->createFeed();

    // Check that the block is listed for all languages.
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet('admin/structure/block', array('language' => $langcode));
      $this->assertText($feed->label());
    }
  }
}
