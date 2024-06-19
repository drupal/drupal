<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\language\Traits\LanguageTestTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests search integration filters with multilingual nodes.
 *
 * @group views
 */
class SearchMultilingualTest extends ViewTestBase {

  use CronRunTrait;
  use LanguageTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'search',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_search'];

  /**
   * Tests search with multilingual nodes.
   */
  public function testMultilingualSearchFilter(): void {
    // Add Spanish language programmatically.
    static::createLanguageFromLangcode('es');

    // Create a content type and make it translatable.
    $type = $this->drupalCreateContentType();
    static::enableBundleTranslation('node', $type->id());

    // Add a node in English, with title "sandwich".
    $values = [
      'title' => 'sandwich',
      'type' => $type->id(),
    ];
    $node = $this->drupalCreateNode($values);

    // "Translate" this node into Spanish, with title "pizza".
    $node->addTranslation('es', ['title' => 'pizza', 'status' => NodeInterface::PUBLISHED]);
    $node->save();

    // Run cron so that the search index tables are updated.
    $this->cronRun();

    // Test the keyword filter by visiting the page.
    // The views are in the test view 'test_search', and they just display the
    // titles of the nodes in the result, as links.

    // Page with a keyword filter of 'pizza'. This should find the Spanish
    // translated node, which has 'pizza' in the title, but not the English
    // one, which does not have the word 'pizza' in it.
    $this->drupalGet('test-filter');
    $this->assertSession()->linkExists('pizza', 0, 'Found translation with matching title');
    $this->assertSession()->linkNotExists('sandwich', 'Did not find translation with non-matching title');
  }

}
