<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\Traits\Core\PathAliasTestTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;

/**
 * Tests the feed display plugin with translated content.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\display\Feed
 */
class DisplayFeedTranslationTest extends ViewTestBase {

  use PathAliasTestTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_display_feed'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'views',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The added languages.
   *
   * @var string[]
   */
  protected $langcodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();

    $this->langcodes = ['es', 'pt-br'];
    foreach ($this->langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer nodes',
      'administer content translation',
      'translate any entity',
      'create content translations',
      'administer languages',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalCreateContentType(['type' => 'page']);

    // Enable translation for page.
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][page][translatable]' => TRUE,
      'settings[node][page][settings][language][language_alterable]' => TRUE,
    ];
    $this->drupalGet('admin/config/regional/content-language');
    $this->submitForm($edit, 'Save configuration');

    // Rebuild the container so that the new languages are picked up by services
    // that hold a list of languages.
    $this->rebuildContainer();
  }

  /**
   * Tests the rendered output for fields display with multiple translations.
   */
  public function testFeedFieldOutput() {
    $node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'en',
      'body' => [
        0 => [
          'value' => 'Something in English.',
          'format' => filter_default_format(),
        ],
      ],
      'langcode' => 'en',
    ]);

    $es_translation = $node->addTranslation('es');
    $es_translation->set('title', 'es');
    $es_translation->set('body', [['value' => 'Algo en Español']]);
    $es_translation->save();

    $pt_br_translation = $node->addTranslation('pt-br');
    $pt_br_translation->set('title', 'pt-br');
    $pt_br_translation->set('body', [['value' => 'Algo em Português']]);
    $pt_br_translation->save();

    // First, check everything with raw node paths (e.g. node/1).
    $this->checkFeedResults('raw-node-path', $node);

    // Now, create path aliases for each translation.
    $node_path = '/node/' . $node->id();
    $this->createPathAlias($node_path, "$node_path/en-alias");
    $this->createPathAlias($node_path, "$node_path/es-alias", 'es');
    $this->createPathAlias($node_path, "$node_path/pt-br-alias", 'pt-br');
    // Save the node again, to clear the cache on the feed.
    $node->save();
    // Assert that all the results are correct using path aliases.
    $this->checkFeedResults('path-alias', $node);
  }

  /**
   * Checks the feed results for the given style of node links.
   *
   * @param string $link_style
   *   What style of links do we expect? Either 'raw-node-path' or 'path-alias'.
   *   Only used for human-readable assert failure messages.
   * @param \Drupal\node\Entity\Node $node
   *   The node entity that's been created.
   */
  protected function checkFeedResults($link_style, Node $node) {
    /** @var \Drupal\Core\Language\LanguageManagerInterface $languageManager */
    $language_manager = \Drupal::languageManager()->reset();

    $node_links = [];
    $node_links['en'] = $node->toUrl()->setAbsolute()->toString();
    foreach ($this->langcodes as $langcode) {
      $node_links[$langcode] = $node->toUrl()
        ->setOption('language', $language_manager->getLanguage($langcode))
        ->setAbsolute()
        ->toString();
    }

    $expected = [
      'pt-br' => [
        'description' => '<p>Algo em Português</p>',
      ],
      'es' => [
        'description' => '<p>Algo en Español</p>',
      ],
      'en' => [
        'description' => '<p>Something in English.</p>',
      ],
    ];
    foreach ($node_links as $langcode => $link) {
      $expected[$langcode]['link'] = $link;
    }

    $this->drupalGet('test-feed-display-fields.xml');
    $this->assertSession()->statusCodeEquals(200);

    $items = $this->getSession()->getDriver()->find('//channel/item');
    // There should only be 3 items in the feed.
    $this->assertCount(3, $items, "$link_style: 3 items in feed");

    // Don't rely on the sort order of the items in the feed. Instead, each
    // item's title is the langcode for that item. Iterate over all the items,
    // get the title text for each one, make sure we're expecting each langcode
    // we find, and then assert that the rest of the content of that item is
    // what we expect for the given langcode.
    foreach ($items as $item) {
      $title_element = $item->findAll('xpath', 'title');
      $this->assertCount(1, $title_element, "$link_style: Missing title element");
      $langcode = $title_element[0]->getText();
      $this->assertArrayHasKey($langcode, $expected, "$link_style: Missing expected output for $langcode");
      foreach ($expected[$langcode] as $key => $expected_value) {
        $elements = $item->findAll('xpath', $key);
        $this->assertCount(1, $elements, "$link_style: Xpath $key missing");
        $this->assertEquals($expected_value, $elements[0]->getText(), "$link_style: Unexpected value for $key");
      }
    }
  }

}
