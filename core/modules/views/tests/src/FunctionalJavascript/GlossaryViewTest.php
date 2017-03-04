<?php

namespace Drupal\Tests\views\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the basic AJAX functionality of the Glossary View.
 *
 * @group node
 */
class GlossaryViewTest extends JavascriptTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language', 'node', 'views', 'views_test_config'];

  /**
   * @var array
   * The test Views to enable.
   */
  public static $testViews = ['test_glossary'];

  /**
   * @var
   * The additional language to use.
   */
  protected $language;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), ['views_test_config']);

    // Create a Content type and some test nodes with titles that start with
    // different letters.
    $this->createContentType(['type' => 'page']);

    $titles = [
      'Page One',
      'Page Two',
      'Another page',
    ];
    foreach ($titles as $title) {
      $this->createNode([
        'title' => $title,
        'language' => 'en',
      ]);
      $this->createNode([
        'title' => $title,
        'language' => 'nl',
      ]);
    }

    // Create a user privileged enough to use exposed filters and view content.
    $user = $this->drupalCreateUser([
      'administer site configuration',
      'access content',
      'access content overview',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests the AJAX callbacks for the glossary view.
   */
  public function testGlossaryDefault() {
    // Visit the default Glossary page.
    $url = Url::fromRoute('view.test_glossary.page_1');
    $this->drupalGet($url);

    $session = $this->getSession();
    $web_assert = $this->assertSession();

    $page = $session->getPage();
    $rows = $page->findAll('css', '.view-test-glossary tr');
    // We expect 2 rows plus the header row.
    $this->assertCount(3, $rows);
    // Click on the P link, this should show 4 rows plus the header row.
    $page->clickLink('P');
    $web_assert->assertWaitOnAjaxRequest();
    $rows = $page->findAll('css', '.view-test-glossary tr');
    $this->assertCount(5, $rows);
  }

  /**
   * Test that the glossary also works on a language prefixed URL.
   */
  public function testGlossaryLanguagePrefix() {
    $this->language = ConfigurableLanguage::createFromLangcode('nl')->save();

    $config = $this->config('language.negotiation');
    $config->set('url.prefixes', ['en' => 'en', 'nl' => 'nl'])
      ->save();

    \Drupal::service('kernel')->rebuildContainer();

    $url = Url::fromRoute('view.test_glossary.page_1');
    $this->drupalGet($url);

    $session = $this->getSession();
    $web_assert = $this->assertSession();

    $page = $session->getPage();

    $rows = $page->findAll('css', '.view-test-glossary tr');
    // We expect 2 rows plus the header row.
    $this->assertCount(3, $rows);
    // Click on the P link, this should show 4 rows plus the header row.
    $page->clickLink('P');
    $web_assert->assertWaitOnAjaxRequest();

    $rows = $page->findAll('css', '.view-test-glossary tr');
    $this->assertCount(5, $rows);
  }

}
