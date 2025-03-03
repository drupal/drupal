<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Functional;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests advanced search with different languages added.
 *
 * @group search
 */
class SearchLanguageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'node', 'search'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Array of nodes available to search.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $searchableNodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create and log in user.
    $test_user = $this->drupalCreateUser([
      'access content',
      'search content',
      'use advanced search',
      'administer nodes',
      'administer languages',
      'access administration pages',
      'administer site configuration',
    ]);
    $this->drupalLogin($test_user);

    // Add a new language.
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Make the body field translatable. The title is already translatable by
    // definition. The parent class has already created the article and page
    // content types.
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $field_storage->setTranslatable(TRUE);
    $field_storage->save();

    // Create a few page nodes with multilingual body values.
    $default_format = filter_default_format();
    $nodes = [
      [
        'title' => 'First node en',
        'type' => 'page',
        'body' => [['value' => $this->randomMachineName(32), 'format' => $default_format]],
        'langcode' => 'en',
      ],
      [
        'title' => 'Second node this is the Spanish title',
        'type' => 'page',
        'body' => [['value' => $this->randomMachineName(32), 'format' => $default_format]],
        'langcode' => 'es',
      ],
      [
        'title' => 'Third node en',
        'type' => 'page',
        'body' => [['value' => $this->randomMachineName(32), 'format' => $default_format]],
        'langcode' => 'en',
      ],
    ];
    $this->searchableNodes = [];
    foreach ($nodes as $setting) {
      $this->searchableNodes[] = $this->drupalCreateNode($setting);
    }

    // Add English translation to the second node.
    $translation = $this->searchableNodes[1]->addTranslation('en', ['title' => 'Second node en']);
    $translation->body->value = $this->randomMachineName(32);
    $this->searchableNodes[1]->save();

    // Add Spanish translation to the third node.
    $translation = $this->searchableNodes[2]->addTranslation('es', ['title' => 'Third node es']);
    $translation->body->value = $this->randomMachineName(32);
    $this->searchableNodes[2]->save();

    // Update the index and then run the shutdown method.
    $plugin = $this->container->get('plugin.manager.search')->createInstance('node_search');
    $plugin->updateIndex();
  }

  /**
   * Tests language management in the search interface.
   */
  public function testLanguages(): void {
    // Add predefined language.
    $edit = ['predefined_langcode' => 'fr'];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');
    $this->assertSession()->pageTextContains('French');

    // Now we should have languages displayed.
    $this->drupalGet('search/node');
    $this->assertSession()->pageTextContains('Languages');
    $this->assertSession()->pageTextContains('English');
    $this->assertSession()->pageTextContains('French');

    // Ensure selecting no language does not make the query different.
    $this->drupalGet('search/node');
    $this->submitForm([], 'edit-submit--2');
    $this->assertSession()->addressEquals(Url::fromRoute('search.view_node_search', [], ['query' => ['keys' => '']]));

    // Pick French and ensure it is selected.
    $edit = ['language[fr]' => TRUE];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'edit-submit--2');
    // Get the redirected URL.
    $url = $this->getUrl();
    $parts = parse_url($url);
    $query_string = isset($parts['query']) ? rawurldecode($parts['query']) : '';
    $this->assertStringContainsString('=language:fr', $query_string, 'Language filter language:fr add to the query string.');

    // Search for keyword node and language filter as Spanish.
    $edit = ['keys' => 'node', 'language[es]' => TRUE];
    $this->drupalGet('search/node');
    $this->submitForm($edit, 'edit-submit--2');
    // Check for Spanish results.
    $this->assertSession()->linkExists('Second node this is the Spanish title', 0, 'Second node Spanish title found in search results');
    $this->assertSession()->linkExists('Third node es', 0, 'Third node Spanish found in search results');
    // Ensure that results don't contain other language nodes.
    $this->assertSession()->linkNotExists('First node en', 'Search results do not contain first English node');
    $this->assertSession()->linkNotExists('Second node en', 'Search results do not contain second English node');
    $this->assertSession()->linkNotExists('Third node en', 'Search results do not contain third English node');

    // Change the default language and delete English.
    $path = 'admin/config/regional/language';
    $this->drupalGet($path);
    $this->assertSession()->checkboxChecked('edit-site-default-language-en');
    $edit = [
      'site_default_language' => 'fr',
    ];
    $this->drupalGet($path);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->checkboxNotChecked('edit-site-default-language-en');
    $this->drupalGet('admin/config/regional/language/delete/en');
    $this->submitForm([], 'Delete');
  }

  /**
   * Test language attribute "lang" for the search results.
   */
  public function testLanguageAttributes(): void {
    $this->drupalGet('search/node');
    $this->submitForm(['keys' => 'the Spanish title'], 'Search');

    $node = $this->searchableNodes[1]->getTranslation('es');
    $this->assertSession()->elementExists('xpath', '//div[@class="layout-content"]//ol/li/h3[contains(@lang, "es")]');
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//ol/li/h3[contains(@lang, "es")]/a', $node->getTitle());
    $this->assertSession()->elementExists('xpath', '//div[@class="layout-content"]//ol/li/p[contains(@lang, "es")]');

    // Visit the search form in Spanish language.
    $this->drupalGet('es/search/node');
    $this->submitForm(['keys' => 'First node'], 'Search');
    $this->assertSession()->elementExists('xpath', '//div[@class="layout-content"]//ol/li/h3[contains(@lang, "en")]');
    $node = $this->searchableNodes[0]->getTranslation('en');
    $this->assertSession()->elementTextEquals('xpath', '//div[@class="layout-content"]//ol/li/h3[contains(@lang, "en")]/a', $node->getTitle());
    $this->assertSession()->elementExists('xpath', '//div[@class="layout-content"]//ol/li/p[contains(@lang, "en")]');
  }

}
