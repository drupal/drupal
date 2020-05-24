<?php

namespace Drupal\Tests\help_topics\Functional;

use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\help_topics\Plugin\Search\HelpSearch;

/**
 * Verifies help topic search.
 *
 * @group help_topics
 */
class HelpTopicSearchTest extends HelpTopicTranslatedTestBase {

  use CronRunTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'search',
    'locale',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Log in.
    $this->drupalLogin($this->createUser([
      'access administration pages',
      'administer site configuration',
      'view the administration theme',
      'administer permissions',
      'administer languages',
      'administer search',
      'access test help',
      'search content',
    ]));

    // Add English language and set to default.
    $this->drupalPostForm('admin/config/regional/language/add', [
      'predefined_langcode' => 'en',
    ], 'Add language');
    $this->drupalPostForm('admin/config/regional/language', [
      'site_default_language' => 'en',
    ], 'Save configuration');
    // When default language is changed, the container is rebuilt in the child
    // site, so a rebuild in the main site is required to use the new container
    // here.
    $this->rebuildContainer();

    // Before running cron, verify that a search returns no results.
    $this->drupalPostForm('search/help', ['keys' => 'notawordenglish'], 'Search');
    $this->assertSearchResultsCount(0);

    // Run cron until the topics are fully indexed, with a limit of 100 runs
    // to avoid infinite loops.
    $num_runs = 100;
    $plugin = HelpSearch::create($this->container, [], 'help_search', []);
    do {
      $this->cronRun();
      $remaining = $plugin->indexStatus()['remaining'];
    } while (--$num_runs && $remaining);
    $this->assertNotEmpty($num_runs);
    $this->assertEmpty($remaining);

    // Visit the Search settings page and verify it says 100% indexed.
    $this->drupalGet('admin/config/search/pages');
    $this->assertSession()->pageTextContains('100% of the site has been indexed');
  }

  /**
   * Tests help topic search.
   */
  public function testHelpSearch() {
    $german = \Drupal::languageManager()->getLanguage('de');
    $session = $this->assertSession();

    // Verify that when we search in English for a word that is only in
    // English text, we find the topic. Note that these "words" are provided
    // by the topics that come from
    // \Drupal\help_topics_test\Plugin\HelpSection\TestHelpSection.
    $this->drupalPostForm('search/help', ['keys' => 'notawordenglish'], 'Search');
    $this->assertSearchResultsCount(1);
    $session->linkExists('Foo in English title wcsrefsdf');

    // Same for German.
    $this->drupalPostForm('search/help', ['keys' => 'notawordgerman'], 'Search', [
      'language' => $german,
    ]);
    $this->assertSearchResultsCount(1);
    $session->linkExists('Foomm Foreign heading');

    // Verify when we search in English for a word that only exists in German,
    // we get no results.
    $this->drupalPostForm('search/help', ['keys' => 'notawordgerman'], 'Search');
    $this->assertSearchResultsCount(0);
    $session->pageTextContains('no results');

    // Same for German.
    $this->drupalPostForm('search/help', ['keys' => 'notawordenglish'], 'Search', [
      'language' => $german,
    ]);
    $this->assertSearchResultsCount(0);
    $session->pageTextContains('no results');

    // Verify when we search in English for a word that exists in one topic
    // in English and a different topic in German, we only get the one English
    // topic.
    $this->drupalPostForm('search/help', ['keys' => 'sqruct'], 'Search');
    $this->assertSearchResultsCount(1);
    $session->linkExists('Foo in English title wcsrefsdf');

    // Same for German.
    $this->drupalPostForm('search/help', ['keys' => 'asdrsad'], 'Search', [
      'language' => $german,
    ]);
    $this->assertSearchResultsCount(1);
    $session->linkExists('Foomm Foreign heading');

    // All of the above tests used the TestHelpSection plugin. Also verify
    // that we can search for translated regular help topics, in both English
    // and German.
    $this->drupalPostForm('search/help', ['keys' => 'nonworditem'], 'Search');
    $this->assertSearchResultsCount(1);
    $session->linkExists('ABC Help Test module');
    // Click the link and verify we ended up on the topic page.
    $this->clickLink('ABC Help Test module');
    $session->pageTextContains('This is a test');

    $this->drupalPostForm('search/help', ['keys' => 'nonwordgerman'], 'Search', [
      'language' => $german,
    ]);
    $this->assertSearchResultsCount(1);
    $session->linkExists('ABC-Hilfetestmodul');
    $this->clickLink('ABC-Hilfetestmodul');
    $session->pageTextContains('Übersetzung testen.');

    // Verify that we can search from the admin/help page.
    $this->drupalGet('admin/help');
    $session->pageTextContains('Search help');
    $this->drupalPostForm(NULL, ['keys' => 'nonworditem'], 'Search');
    $this->assertSearchResultsCount(1);
    $session->linkExists('ABC Help Test module');

    // Same for German.
    $this->drupalPostForm('admin/help', ['keys' => 'nonwordgerman'], 'Search', [
      'language' => $german,
    ]);
    $this->assertSearchResultsCount(1);
    $session->linkExists('ABC-Hilfetestmodul');

    // Verify we can search for title text (other searches used text
    // that was part of the body).
    $this->drupalPostForm('search/help', ['keys' => 'wcsrefsdf'], 'Search');
    $this->assertSearchResultsCount(1);
    $session->linkExists('Foo in English title wcsrefsdf');

    $this->drupalPostForm('admin/help', ['keys' => 'sdeeeee'], 'Search', [
      'language' => $german,
    ]);
    $this->assertSearchResultsCount(1);
    $session->linkExists('Barmm Foreign sdeeeee');

    // Just changing the title and running cron is not enough to reindex so
    // 'sdeeeee' still hits a match. The content is updated because the help
    // topic is rendered each time.
    \Drupal::state()->set('help_topics_test:translated_title', 'Updated translated title');
    $this->cronRun();
    $this->drupalPostForm('admin/help', ['keys' => 'sdeeeee'], 'Search', [
      'language' => $german,
    ]);
    $this->assertSearchResultsCount(1);
    $session->linkExists('Updated translated title');
    // Searching for the updated test shouldn't produce a match.
    $this->drupalPostForm('admin/help', ['keys' => 'translated title'], 'Search', [
      'language' => $german,
    ]);
    $this->assertSearchResultsCount(0);

    // Clear the caches and re-run cron - this should re-index the help.
    $this->rebuildAll();
    $this->cronRun();
    $this->drupalPostForm('admin/help', ['keys' => 'sdeeeee'], 'Search', [
      'language' => $german,
    ]);
    $this->assertSearchResultsCount(0);
    $this->drupalPostForm('admin/help', ['keys' => 'translated title'], 'Search', [
      'language' => $german,
    ]);
    $this->assertSearchResultsCount(1);
    $session->linkExists('Updated translated title');

    // Verify the cache tags and contexts.
    $session->responseHeaderContains('X-Drupal-Cache-Tags', 'config:search.page.help_search');
    $session->responseHeaderContains('X-Drupal-Cache-Tags', 'search_index:help_search');
    $session->responseHeaderContains('X-Drupal-Cache-Contexts', 'user.permissions');
    $session->responseHeaderContains('X-Drupal-Cache-Contexts', 'languages:language_interface');

    // Log in as a user that does not have permission to see TestHelpSection
    // items, and verify they can still search for help topics but not see these
    // items.
    $this->drupalLogin($this->createUser([
      'access administration pages',
      'administer site configuration',
      'view the administration theme',
      'administer permissions',
      'administer languages',
      'administer search',
      'search content',
    ]));

    $this->drupalGet('admin/help');
    $session->pageTextContains('Search help');

    $this->drupalPostForm('search/help', ['keys' => 'nonworditem'], 'Search');
    $this->assertSearchResultsCount(1);
    $session->linkExists('ABC Help Test module');

    $this->drupalPostForm('search/help', ['keys' => 'notawordenglish'], 'Search');
    $this->assertSearchResultsCount(0);
    $session->pageTextContains('no results');

    // Uninstall the test module and verify its topics are immediately not
    // searchable.
    \Drupal::service('module_installer')->uninstall(['help_topics_test']);
    $this->drupalPostForm('search/help', ['keys' => 'nonworditem'], 'Search');
    $this->assertSearchResultsCount(0);
  }

  /**
   * Tests uninstalling the help_topics module.
   */
  public function testUninstall() {
    \Drupal::service('module_installer')->uninstall(['help_topics_test']);
    // Ensure we can uninstall help_topics and use the help system without
    // breaking.
    $this->drupalLogin($this->rootUser);
    $edit = [];
    $edit['uninstall[help_topics]'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');
    $this->drupalGet('admin/help');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Asserts that help search returned the expected number of results.
   *
   * @param int $count
   *   The expected number of search results.
   */
  protected function assertSearchResultsCount($count) {
    $this->assertSession()->elementsCount('css', '.help_search-results > li', $count);
  }

}
