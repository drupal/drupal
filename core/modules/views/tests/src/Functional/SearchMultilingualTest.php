<?php

namespace Drupal\Tests\views\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\NodeInterface;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests search integration filters with multilingual nodes.
 *
 * @group views
 */
class SearchMultilingualTest extends ViewTestBase {

  use CronRunTrait;

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
  public function testMultilingualSearchFilter() {
    // Create a user with admin for languages, content, and content types, plus
    // the ability to access content and searches.
    $user = $this->drupalCreateUser([
      'administer nodes',
      'administer content types',
      'administer languages',
      'administer content translation',
      'access content',
      'search content',
    ]);
    $this->drupalLogin($user);

    // Add Spanish language programmatically.
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Create a content type and make it translatable.
    $type = $this->drupalCreateContentType();
    $edit = [
      'language_configuration[language_alterable]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/types/manage/' . $type->id(), $edit, 'Save content type');
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][' . $type->id() . '][translatable]' => TRUE,
      'settings[node][' . $type->id() . '][fields][title]' => TRUE,
      'settings[node][' . $type->id() . '][fields][body]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/regional/content-language', $edit, 'Save configuration');

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
