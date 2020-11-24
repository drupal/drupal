<?php

namespace Drupal\Tests\aggregator\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests aggregator feeds in multiple languages.
 *
 * @group aggregator
 */
class FeedLanguageTest extends AggregatorTestBase {

  use CronRunTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * List of langcodes.
   *
   * @var string[]
   */
  protected $langcodes = [];

  /**
   * {@inheritdoc}
   */
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
      $this->langcodes[$i] = $language->id();
    }
  }

  /**
   * Tests creation of feeds with a language.
   */
  public function testFeedLanguage() {
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      'administer news feeds',
      'access news feeds',
      'create article content',
    ]);
    $this->drupalLogin($admin_user);

    // Enable language selection for feeds.
    $edit['entity_types[aggregator_feed]'] = TRUE;
    $edit['settings[aggregator_feed][aggregator_feed][settings][language][language_alterable]'] = TRUE;

    $this->drupalPostForm('admin/config/regional/content-language', $edit, 'Save configuration');

    /** @var \Drupal\aggregator\FeedInterface[] $feeds */
    $feeds = [];
    // Create feeds.
    $feeds[1] = $this->createFeed(NULL, ['langcode[0][value]' => $this->langcodes[1]]);
    $feeds[2] = $this->createFeed(NULL, ['langcode[0][value]' => $this->langcodes[2]]);

    // Make sure that the language has been assigned.
    $this->assertEqual($feeds[1]->language()->getId(), $this->langcodes[1]);
    $this->assertEqual($feeds[2]->language()->getId(), $this->langcodes[2]);

    // Create example nodes to create feed items from and then update the feeds.
    $this->createSampleNodes();
    $this->cronRun();

    // Loop over the created feed items and verify that their language matches
    // the one from the feed.
    foreach ($feeds as $feed) {
      /** @var \Drupal\aggregator\ItemInterface[] $items */
      $items = \Drupal::entityTypeManager()->getStorage('aggregator_item')->loadByProperties(['fid' => $feed->id()]);
      // Verify that the feed items were created.
      $this->assertNotEmpty($items);
      foreach ($items as $item) {
        $this->assertEqual($item->language()->getId(), $feed->language()->getId());
      }
    }
  }

}
