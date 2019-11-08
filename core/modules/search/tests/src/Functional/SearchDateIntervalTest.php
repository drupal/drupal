<?php

namespace Drupal\Tests\search\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests searching with date filters that exclude some translations.
 *
 * @group search
 */
class SearchDateIntervalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'search_date_query_alter', 'node', 'search'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create and log in user.
    $test_user = $this->drupalCreateUser(['access content', 'search content', 'use advanced search', 'administer nodes', 'administer languages', 'access administration pages', 'administer site configuration']);
    $this->drupalLogin($test_user);

    // Add a new language.
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Set up times to be applied to the English and Spanish translations of the
    // node create time, so that they are filtered in/out in the
    // search_date_query_alter test module.
    $created_time_en = new \DateTime('February 10 2016 10PM');
    $created_time_es = new \DateTime('March 19 2016 10PM');
    $default_format = filter_default_format();

    $node = $this->drupalCreateNode([
      'title' => 'Node EN',
      'type' => 'page',
      'body' => [
        'value' => $this->randomMachineName(32),
        'format' => $default_format,
      ],
      'langcode' => 'en',
      'created' => $created_time_en->getTimestamp(),
    ]);

    // Add Spanish translation to the node.
    $translation = $node->addTranslation('es', ['title' => 'Node ES']);
    $translation->body->value = $this->randomMachineName(32);
    $translation->created->value = $created_time_es->getTimestamp();
    $node->save();

    // Update the index.
    $plugin = $this->container->get('plugin.manager.search')->createInstance('node_search');
    $plugin->updateIndex();
  }

  /**
   * Tests searching with date filters that exclude some translations.
   */
  public function testDateIntervalQueryAlter() {
    // Search for keyword node.
    $edit = ['keys' => 'node'];
    $this->drupalPostForm('search/node', $edit, t('Search'));

    // The nodes must have the same node ID but the created date is different.
    // So only the Spanish translation must appear.
    $this->assertLink('Node ES', 0, 'Spanish translation found in search results');
    $this->assertNoLink('Node EN', 'Search results do not contain English node');
  }

}
