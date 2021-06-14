<?php

namespace Drupal\Tests\aggregator\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\aggregator\Entity\Feed;
use Drupal\Component\Utility\Html;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\aggregator\FeedInterface;

/**
 * Defines a base class for testing the Aggregator module.
 */
abstract class AggregatorTestBase extends BrowserTestBase {

  /**
   * A user with permission to administer feeds and create content.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'node',
    'aggregator',
    'aggregator_test',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an Article node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer news feeds',
      'access news feeds',
      'create article content',
    ]);
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Creates an aggregator feed.
   *
   * This method simulates the form submission on path aggregator/sources/add.
   *
   * @param string $feed_url
   *   (optional) If given, feed will be created with this URL, otherwise
   *   /rss.xml will be used. Defaults to NULL.
   * @param array $edit
   *   Array with additional form fields.
   *
   * @return \Drupal\aggregator\FeedInterface
   *   Full feed object if possible.
   *
   * @see getFeedEditArray()
   */
  public function createFeed($feed_url = NULL, array $edit = []) {
    $edit = $this->getFeedEditArray($feed_url, $edit);
    $this->drupalGet('aggregator/sources/add');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The feed ' . $edit['title[0][value]'] . ' has been added.');

    // Verify that the creation message contains a link to a feed.
    $this->assertSession()->elementExists('xpath', '//div[@data-drupal-messages]//a[contains(@href, "aggregator/sources/")]');

    $fids = \Drupal::entityQuery('aggregator_feed')
      ->accessCheck(FALSE)
      ->condition('title', $edit['title[0][value]'])
      ->condition('url', $edit['url[0][value]'])
      ->execute();
    $this->assertNotEmpty($fids, 'The feed found in database.');
    return Feed::load(array_values($fids)[0]);
  }

  /**
   * Deletes an aggregator feed.
   *
   * @param \Drupal\aggregator\FeedInterface $feed
   *   Feed object representing the feed.
   */
  public function deleteFeed(FeedInterface $feed) {
    $this->drupalGet('aggregator/sources/' . $feed->id() . '/delete');
    $this->submitForm([], 'Delete');
    $this->assertRaw(t('The feed %title has been deleted.', ['%title' => $feed->label()]));
  }

  /**
   * Returns a randomly generated feed edit array.
   *
   * @param string $feed_url
   *   (optional) If given, feed will be created with this URL, otherwise
   *   /rss.xml will be used. Defaults to NULL.
   * @param array $edit
   *   Array with additional form fields.
   *
   * @return array
   *   A feed array.
   */
  public function getFeedEditArray($feed_url = NULL, array $edit = []) {
    $feed_name = $this->randomMachineName(10);
    if (!$feed_url) {
      $feed_url = Url::fromRoute('view.frontpage.feed_1', [], [
        'query' => ['feed' => $feed_name],
        'absolute' => TRUE,
      ])->toString();
    }
    $edit += [
      'title[0][value]' => $feed_name,
      'url[0][value]' => $feed_url,
      'refresh' => '900',
    ];
    return $edit;
  }

  /**
   * Returns a randomly generated feed edit object.
   *
   * @param string $feed_url
   *   (optional) If given, feed will be created with this URL, otherwise
   *   /rss.xml will be used. Defaults to NULL.
   * @param array $values
   *   (optional) Default values to initialize object properties with.
   *
   * @return \Drupal\aggregator\FeedInterface
   *   A feed object.
   */
  public function getFeedEditObject($feed_url = NULL, array $values = []) {
    $feed_name = $this->randomMachineName(10);
    if (!$feed_url) {
      $feed_url = Url::fromRoute('view.frontpage.feed_1', [
        'query' => ['feed' => $feed_name],
        'absolute' => TRUE,
      ])->toString();
    }
    $values += [
      'title' => $feed_name,
      'url' => $feed_url,
      'refresh' => '900',
    ];
    return Feed::create($values);
  }

  /**
   * Returns the count of the randomly created feed array.
   *
   * @return int
   *   Number of feed items on default feed created by createFeed().
   */
  public function getDefaultFeedItemCount() {
    // Our tests are based off of rss.xml, so let's find out how many elements
    // should be related.
    return \Drupal::entityQuery('node')
      ->condition('promote', NodeInterface::PROMOTED)
      ->condition('status', NodeInterface::PUBLISHED)
      ->accessCheck(FALSE)
      ->range(0, 10)
      ->count()
      ->execute();
  }

  /**
   * Updates the feed items.
   *
   * This method simulates a click to
   * admin/config/services/aggregator/update/$fid.
   *
   * @param \Drupal\aggregator\FeedInterface $feed
   *   Feed object representing the feed.
   * @param int|null $expected_count
   *   Expected number of feed items. If omitted no check will happen.
   */
  public function updateFeedItems(FeedInterface $feed, $expected_count = NULL) {
    // First, let's ensure we can get to the rss xml.
    $this->drupalGet($feed->getUrl());
    $this->assertSession()->statusCodeEquals(200);

    // Attempt to access the update link directly without an access token.
    $this->drupalGet('admin/config/services/aggregator/update/' . $feed->id());
    $this->assertSession()->statusCodeEquals(403);

    // Refresh the feed (simulated link click).
    $this->drupalGet('admin/config/services/aggregator');
    $this->clickLink('Update items');

    // Ensure we have the right number of items.
    $item_ids = \Drupal::entityQuery('aggregator_item')
      ->accessCheck(FALSE)
      ->condition('fid', $feed->id())
      ->execute();
    $feed->items = array_values($item_ids);

    if ($expected_count !== NULL) {
      $feed->item_count = count($feed->items);
      $this->assertEquals($expected_count, $feed->item_count, new FormattableMarkup('Total items in feed equal to the total items in database (@val1 != @val2)', ['@val1' => $expected_count, '@val2' => $feed->item_count]));
    }
  }

  /**
   * Confirms an item removal from a feed.
   *
   * @param \Drupal\aggregator\FeedInterface $feed
   *   Feed object representing the feed.
   */
  public function deleteFeedItems(FeedInterface $feed) {
    $this->drupalGet('admin/config/services/aggregator/delete/' . $feed->id());
    $this->submitForm([], 'Delete items');
    $this->assertRaw(t('The news items from %title have been deleted.', ['%title' => $feed->label()]));
  }

  /**
   * Adds and deletes feed items and ensure that the count is zero.
   *
   * @param \Drupal\aggregator\FeedInterface $feed
   *   Feed object representing the feed.
   * @param int $expected_count
   *   Expected number of feed items.
   */
  public function updateAndDelete(FeedInterface $feed, $expected_count) {
    $count_query = \Drupal::entityQuery('aggregator_item')
      ->accessCheck(FALSE)
      ->condition('fid', $feed->id())
      ->count();
    $this->updateFeedItems($feed, $expected_count);
    $count = $count_query->execute();
    $this->assertGreaterThan(0, $count);
    $this->deleteFeedItems($feed);
    $count = $count_query->execute();
    $this->assertEquals(0, $count);
  }

  /**
   * Checks whether the feed name and URL are unique.
   *
   * @param string $feed_name
   *   String containing the feed name to check.
   * @param string $feed_url
   *   String containing the feed url to check.
   *
   * @return bool
   *   TRUE if feed is unique.
   */
  public function uniqueFeed($feed_name, $feed_url) {
    $result = \Drupal::entityQuery('aggregator_feed')
      ->accessCheck(FALSE)
      ->condition('title', $feed_name)
      ->condition('url', $feed_url)
      ->count()
      ->execute();
    return (1 == $result);
  }

  /**
   * Creates a valid OPML file from an array of feeds.
   *
   * @param array $feeds
   *   An array of feeds.
   *
   * @return string
   *   Path to valid OPML file.
   */
  public function getValidOpml(array $feeds) {
    // Properly escape URLs so that XML parsers don't choke on them.
    foreach ($feeds as &$feed) {
      $feed['url[0][value]'] = Html::escape($feed['url[0][value]']);
    }
    /**
     * Does not have an XML declaration, must pass the parser.
     */
    $opml = <<<EOF
<opml version="1.0">
  <head></head>
  <body>
    <!-- First feed to be imported. -->
    <outline text="{$feeds[0]['title[0][value]']}" xmlurl="{$feeds[0]['url[0][value]']}" />

    <!-- Second feed. Test string delimitation and attribute order. -->
    <outline xmlurl='{$feeds[1]['url[0][value]']}' text='{$feeds[1]['title[0][value]']}'/>

    <!-- Test for duplicate URL and title. -->
    <outline xmlurl="{$feeds[0]['url[0][value]']}" text="Duplicate URL"/>
    <outline xmlurl="http://duplicate.title" text="{$feeds[1]['title[0][value]']}"/>

    <!-- Test that feeds are only added with required attributes. -->
    <outline text="{$feeds[2]['title[0][value]']}" />
    <outline xmlurl="{$feeds[2]['url[0][value]']}" />
  </body>
</opml>
EOF;

    $path = 'public://valid-opml.xml';
    // Add the UTF-8 byte order mark.
    return \Drupal::service('file_system')->saveData(chr(239) . chr(187) . chr(191) . $opml, $path);
  }

  /**
   * Creates an invalid OPML file.
   *
   * @return string
   *   Path to invalid OPML file.
   */
  public function getInvalidOpml() {
    $opml = <<<EOF
<opml>
  <invalid>
</opml>
EOF;

    $path = 'public://invalid-opml.xml';
    return \Drupal::service('file_system')->saveData($opml, $path);
  }

  /**
   * Creates a valid but empty OPML file.
   *
   * @return string
   *   Path to empty OPML file.
   */
  public function getEmptyOpml() {
    $opml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<opml version="1.0">
  <head></head>
  <body>
    <outline text="Sample text" />
    <outline text="Sample text" url="Sample URL" />
  </body>
</opml>
EOF;

    $path = 'public://empty-opml.xml';
    return \Drupal::service('file_system')->saveData($opml, $path);
  }

  /**
   * Returns an example RSS091 feed.
   *
   * @return string
   *   Path to the feed.
   */
  public function getRSS091Sample() {
    return $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'aggregator') . '/tests/modules/aggregator_test/aggregator_test_rss091.xml';
  }

  /**
   * Returns an example Atom feed.
   *
   * @return string
   *   Path to the feed.
   */
  public function getAtomSample() {
    // The content of this sample ATOM feed is based directly off of the
    // example provided in RFC 4287.
    return $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'aggregator') . '/tests/modules/aggregator_test/aggregator_test_atom.xml';
  }

  /**
   * Returns an example feed.
   *
   * @return string
   *   Path to the feed.
   */
  public function getHtmlEntitiesSample() {
    return $GLOBALS['base_url'] . '/' . drupal_get_path('module', 'aggregator') . '/tests/modules/aggregator_test/aggregator_test_title_entities.xml';
  }

  /**
   * Creates sample article nodes.
   *
   * @param int $count
   *   (optional) The number of nodes to generate. Defaults to five.
   */
  public function createSampleNodes($count = 5) {
    // Post $count article nodes.
    for ($i = 0; $i < $count; $i++) {
      $edit = [];
      $edit['title[0][value]'] = $this->randomMachineName();
      $edit['body[0][value]'] = $this->randomMachineName();
      $this->drupalGet('node/add/article');
      $this->submitForm($edit, 'Save');
    }
  }

  /**
   * Enable the plugins coming with aggregator_test module.
   */
  public function enableTestPlugins() {
    $this->config('aggregator.settings')
      ->set('fetcher', 'aggregator_test_fetcher')
      ->set('parser', 'aggregator_test_parser')
      ->set('processors', [
        'aggregator_test_processor' => 'aggregator_test_processor',
        'aggregator' => 'aggregator',
      ])
      ->save();
  }

}
