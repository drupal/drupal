<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;
use Drupal\language\Entity\ConfigurableLanguage;

// cspell:ignore eerste laatste volgende vorige
/**
 * Tests the pluggable pager system.
 *
 * @group views
 */
class PagerTest extends ViewTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_store_pager_settings', 'test_pager_none', 'test_pager_some', 'test_pager_full', 'test_view_pager_full_zero_items_per_page', 'test_view', 'content'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * String translation storage object.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $localeStorage;

  /**
   * Pagers was sometimes not stored.
   *
   * @see https://www.drupal.org/node/652712
   */
  public function testStorePagerSettings() {
    // Show the default display so the override selection is shown.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.show.default_display', TRUE)->save();

    $admin_user = $this->drupalCreateUser([
      'administer views',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);
    // Test behavior described in
    // https://www.drupal.org/node/652712#comment-2354918.

    $this->drupalGet('admin/structure/views/view/test_view/edit');

    $edit = [
      'pager[type]' => 'some',
    ];
    $this->drupalGet('admin/structure/views/nojs/display/test_view/default/pager');
    $this->submitForm($edit, 'Apply');

    $items_per_page = $this->assertSession()->fieldExists("pager_options[items_per_page]");
    $this->assertSession()->fieldValueEquals("pager_options[items_per_page]", 10);
    $this->assertSame('number', $items_per_page->getAttribute('type'));
    $this->assertEquals(0, $items_per_page->getAttribute('min'));

    $offset = $this->assertSession()->fieldExists("pager_options[offset]");
    $this->assertSession()->fieldValueEquals("pager_options[offset]", 0);
    $this->assertSame('number', $offset->getAttribute('type'));
    $this->assertEquals(0, $offset->getAttribute('min'));

    $edit = [
      'pager[type]' => 'none',
    ];
    $this->drupalGet('admin/structure/views/nojs/display/test_view/default/pager');
    $this->submitForm($edit, 'Apply');

    $offset = $this->assertSession()->fieldExists("pager_options[offset]");
    $this->assertSession()->fieldValueEquals("pager_options[offset]", 0);
    $this->assertSame('number', $offset->getAttribute('type'));
    $this->assertEquals(0, $offset->getAttribute('min'));

    $edit = [
      'pager[type]' => 'full',
    ];
    $this->drupalGet('admin/structure/views/nojs/display/test_view/default/pager');
    $this->submitForm($edit, 'Apply');

    $items_per_page = $this->assertSession()->fieldExists("pager_options[items_per_page]");
    $this->assertSession()->fieldValueEquals("pager_options[items_per_page]", 10);
    $this->assertSame('number', $items_per_page->getAttribute('type'));
    $this->assertEquals(0, $items_per_page->getAttribute('min'));

    $offset = $this->assertSession()->fieldExists("pager_options[offset]");
    $this->assertSession()->fieldValueEquals("pager_options[offset]", 0);
    $this->assertSame('number', $offset->getAttribute('type'));
    $this->assertEquals(0, $offset->getAttribute('min'));

    $id = $this->assertSession()->fieldExists("pager_options[id]");
    $this->assertSession()->fieldValueEquals("pager_options[id]", 0);
    $this->assertSame('number', $id->getAttribute('type'));
    $this->assertEquals(0, $id->getAttribute('min'));

    $total_pages = $this->assertSession()->fieldExists("pager_options[total_pages]");
    $this->assertSession()->fieldValueEquals("pager_options[total_pages]", '');
    $this->assertSame('number', $total_pages->getAttribute('type'));
    $this->assertEquals(0, $total_pages->getAttribute('min'));

    $quantity = $this->assertSession()->fieldExists("pager_options[quantity]");
    $this->assertSession()->fieldValueEquals("pager_options[quantity]", 9);
    $this->assertSame('number', $quantity->getAttribute('type'));
    $this->assertEquals(0, $quantity->getAttribute('min'));

    $edit = [
      'pager_options[items_per_page]' => 20,
    ];
    $this->drupalGet('admin/structure/views/nojs/display/test_view/default/pager_options');
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('20 items');

    // Change type and check whether the type is new type is stored.
    $edit = [
      'pager[type]' => 'mini',
    ];
    $this->drupalGet('admin/structure/views/nojs/display/test_view/default/pager');
    $this->submitForm($edit, 'Apply');
    $this->drupalGet('admin/structure/views/view/test_view/edit');
    $this->assertSession()->pageTextContains('Mini');

    // Test behavior described in
    // https://www.drupal.org/node/652712#comment-2354400.
    $view = Views::getView('test_store_pager_settings');
    // Make it editable in the admin interface.
    $view->save();

    $this->drupalGet('admin/structure/views/view/test_store_pager_settings/edit');

    $edit = [
      'pager[type]' => 'full',
    ];
    $this->drupalGet('admin/structure/views/nojs/display/test_store_pager_settings/default/pager');
    $this->submitForm($edit, 'Apply');
    $this->drupalGet('admin/structure/views/view/test_store_pager_settings/edit');
    $this->assertSession()->pageTextContains('Full');

    $edit = [
      'pager_options[items_per_page]' => 20,
    ];
    $this->drupalGet('admin/structure/views/nojs/display/test_store_pager_settings/default/pager_options');
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('20 items');

    // add new display and test the settings again, by override it.
    $edit = [];
    // Add a display and override the pager settings.
    $this->drupalGet('admin/structure/views/view/test_store_pager_settings/edit');
    $this->submitForm($edit, 'Add Page');
    $edit = [
      'override[dropdown]' => 'page_1',
    ];
    $this->drupalGet('admin/structure/views/nojs/display/test_store_pager_settings/page_1/pager');
    $this->submitForm($edit, 'Apply');

    $edit = [
      'pager[type]' => 'mini',
    ];
    $this->drupalGet('admin/structure/views/nojs/display/test_store_pager_settings/page_1/pager');
    $this->submitForm($edit, 'Apply');
    $this->drupalGet('admin/structure/views/view/test_store_pager_settings/edit/page_1');
    $this->assertSession()->pageTextContains('Mini');

    $edit = [
      'pager_options[items_per_page]' => 10,
    ];
    $this->drupalGet('admin/structure/views/nojs/display/test_store_pager_settings/default/pager_options');
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->pageTextContains('10 items');
    $this->drupalGet('admin/structure/views/view/test_store_pager_settings/edit/page_1');
    $this->assertSession()->pageTextContains('20 items');

    // Test that the override element is only displayed on pager plugin selection form.
    $this->drupalGet('admin/structure/views/nojs/display/test_store_pager_settings/page_1/pager');
    $this->assertSession()->fieldValueEquals('override[dropdown]', 'page_1');
    $this->drupalGet('admin/structure/views/nojs/display/test_store_pager_settings/page_1/pager_options');
    $this->assertSession()->fieldNotExists('override[dropdown]');

    $items_per_page = $this->assertSession()->fieldExists("pager_options[items_per_page]");
    $this->assertSession()->fieldValueEquals("pager_options[items_per_page]", 20);
    $this->assertSame('number', $items_per_page->getAttribute('type'));
    $this->assertEquals(0, $items_per_page->getAttribute('min'));

    $offset = $this->assertSession()->fieldExists("pager_options[offset]");
    $this->assertSession()->fieldValueEquals("pager_options[offset]", 0);
    $this->assertSame('number', $offset->getAttribute('type'));
    $this->assertEquals(0, $offset->getAttribute('min'));

    $id = $this->assertSession()->fieldExists("pager_options[id]");
    $this->assertSession()->fieldValueEquals("pager_options[id]", 0);
    $this->assertSame('number', $id->getAttribute('type'));
    $this->assertEquals(0, $id->getAttribute('min'));

    $total_pages = $this->assertSession()->fieldExists("pager_options[total_pages]");
    $this->assertSession()->fieldValueEquals("pager_options[total_pages]", '');
    $this->assertSame('number', $total_pages->getAttribute('type'));
    $this->assertEquals(0, $total_pages->getAttribute('min'));
  }

  /**
   * Tests the none-pager-query.
   */
  public function testNoLimit() {
    // Create 11 nodes and make sure that everyone is returned.
    // We create 11 nodes, because the default pager plugin had 10 items per page.
    $this->drupalCreateContentType(['type' => 'page']);
    for ($i = 0; $i < 11; $i++) {
      $this->drupalCreateNode();
    }
    $view = Views::getView('test_pager_none');
    $this->executeView($view);
    $this->assertCount(11, $view->result, 'Make sure that every item is returned in the result');

    // Setup and test an offset.
    $view = Views::getView('test_pager_none');
    $view->setDisplay();
    $pager = [
      'type' => 'none',
      'options' => [
        'offset' => 3,
      ],
    ];
    $view->display_handler->setOption('pager', $pager);
    $this->executeView($view);

    $this->assertCount(8, $view->result, 'Make sure that every item beside the first three is returned in the result');

    // Check some public functions.
    $this->assertFalse($view->pager->usePager());
    $this->assertFalse($view->pager->useCountQuery());
    $this->assertEquals(0, $view->pager->getItemsPerPage());
  }

  public function testViewTotalRowsWithoutPager() {
    $this->drupalCreateContentType(['type' => 'page']);
    for ($i = 0; $i < 23; $i++) {
      $this->drupalCreateNode();
    }

    $view = Views::getView('test_pager_none');
    $view->get_total_rows = TRUE;
    $this->executeView($view);

    $this->assertEquals(23, $view->total_rows, "'total_rows' is calculated when pager type is 'none' and 'get_total_rows' is TRUE.");
  }

  /**
   * Tests the some pager plugin.
   */
  public function testLimit() {
    // Create 11 nodes and make sure that everyone is returned.
    // We create 11 nodes, because the default pager plugin had 10 items per page.
    $this->drupalCreateContentType(['type' => 'page']);
    for ($i = 0; $i < 11; $i++) {
      $this->drupalCreateNode();
    }

    $view = Views::getView('test_pager_some');
    $this->executeView($view);
    $this->assertCount(5, $view->result, 'Make sure that only a certain count of items is returned');

    // Setup and test an offset.
    $view = Views::getView('test_pager_some');
    $view->setDisplay();
    $pager = [
      'type' => 'none',
      'options' => [
        'offset' => 8,
        'items_per_page' => 5,
      ],
    ];
    $view->display_handler->setOption('pager', $pager);
    $this->executeView($view);
    $this->assertCount(3, $view->result, 'Make sure that only a certain count of items is returned');

    // Check some public functions.
    $this->assertFalse($view->pager->usePager());
    $this->assertFalse($view->pager->useCountQuery());
  }

  /**
   * Tests the normal pager.
   */
  public function testNormalPager() {
    // Create 11 nodes and make sure that everyone is returned.
    // We create 11 nodes, because the default pager plugin had 10 items per page.
    $this->drupalCreateContentType(['type' => 'page']);
    for ($i = 0; $i < 11; $i++) {
      $this->drupalCreateNode();
    }

    $view = Views::getView('test_pager_full');
    $this->executeView($view);
    $this->assertCount(5, $view->result, 'Make sure that only a certain count of items is returned');

    // Setup and test an offset.
    $view = Views::getView('test_pager_full');
    $view->setDisplay();
    $pager = [
      'type' => 'full',
      'options' => [
        'offset' => 8,
        'items_per_page' => 5,
      ],
    ];
    $view->display_handler->setOption('pager', $pager);
    $this->executeView($view);
    $this->assertCount(3, $view->result, 'Make sure that only a certain count of items is returned');

    // Test items per page = 0
    $view = Views::getView('test_view_pager_full_zero_items_per_page');
    $this->executeView($view);

    $this->assertCount(11, $view->result, 'All items are return');

    // TODO test number of pages.

    // Test items per page = 0.
    // Setup and test an offset.
    $view = Views::getView('test_pager_full');
    $view->setDisplay();
    $pager = [
      'type' => 'full',
      'options' => [
        'offset' => 0,
        'items_per_page' => 0,
      ],
    ];

    $view->display_handler->setOption('pager', $pager);
    $this->executeView($view);
    $this->assertEquals(0, $view->pager->getItemsPerPage());
    $this->assertCount(11, $view->result);

    // Test pager cache contexts.
    $this->drupalGet('test_pager_full');
    $this->assertCacheContexts(['languages:language_interface', 'theme', 'timezone', 'url.query_args', 'user.node_grants:view']);

    // Set "Number of pager links visible" to 1 and check the active page number
    // on the last page.
    $view = Views::getView('test_pager_full');
    $view->setDisplay();
    $pager = [
      'type' => 'full',
      'options' => [
        'items_per_page' => 5,
        'quantity' => 1,
      ],
    ];
    $view->display_handler->setOption('pager', $pager);
    $view->save();
    $this->drupalGet('test_pager_full', ['query' => ['page' => 2]]);
    $this->assertEquals('Current page 3', $this->assertSession()->elementExists('css', '.pager__items li.is-active')->getText());
  }

  /**
   * Tests rendering with NULL pager.
   */
  public function testRenderNullPager() {
    // Create 11 nodes and make sure that everyone is returned.
    // We create 11 nodes, because the default pager plugin had 10 items per page.
    $this->drupalCreateContentType(['type' => 'page']);
    for ($i = 0; $i < 11; $i++) {
      $this->drupalCreateNode();
    }
    $view = Views::getView('test_pager_full');
    $this->executeView($view);
    // Force the value again here.
    $view->setAjaxEnabled(TRUE);
    $view->pager = NULL;
    $output = $view->render();
    $output = \Drupal::service('renderer')->renderRoot($output);
    $this->assertEquals(0, preg_match('/<ul class="pager">/', $output), 'The pager is not rendered.');
  }

  /**
   * Tests the api functions on the view object.
   */
  public function testPagerApi() {
    $view = Views::getView('test_pager_full');
    $view->setDisplay();
    // On the first round don't initialize the pager.

    $this->assertNull($view->getItemsPerPage(), 'If the pager is not initialized and no manual override there is no items per page.');
    $rand_number = rand(1, 5);
    $view->setItemsPerPage($rand_number);
    $this->assertEquals($rand_number, $view->getItemsPerPage(), 'Make sure getItemsPerPage uses the settings of setItemsPerPage.');

    $this->assertNull($view->getOffset(), 'If the pager is not initialized and no manual override there is no offset.');
    $rand_number = rand(1, 5);
    $view->setOffset($rand_number);
    $this->assertEquals($rand_number, $view->getOffset(), 'Make sure getOffset uses the settings of setOffset.');

    $this->assertNull($view->getCurrentPage(), 'If the pager is not initialized and no manual override there is no current page.');
    $rand_number = rand(1, 5);
    $view->setCurrentPage($rand_number);
    $this->assertEquals($rand_number, $view->getCurrentPage(), 'Make sure getCurrentPage uses the settings of set_current_page.');

    $view->destroy();

    // On this round enable the pager.
    $view->initDisplay();
    $view->initQuery();
    $view->initPager();

    $this->assertEquals(5, $view->getItemsPerPage(), 'Per default the view has 5 items per page.');
    $rand_number = rand(1, 5);
    $view->setItemsPerPage($rand_number);
    $rand_number = rand(6, 11);
    $view->pager->setItemsPerPage($rand_number);
    $this->assertEquals($rand_number, $view->getItemsPerPage(), 'Make sure getItemsPerPage uses the settings of setItemsPerPage.');

    $this->assertEquals(0, $view->getOffset(), 'Per default a view has a 0 offset.');
    $rand_number = rand(1, 5);
    $view->setOffset($rand_number);
    $rand_number = rand(6, 11);
    $view->pager->setOffset($rand_number);
    $this->assertEquals($rand_number, $view->getOffset(), 'Make sure getOffset uses the settings of setOffset.');

    $this->assertEquals(0, $view->getCurrentPage(), 'Per default the current page is 0.');
    $rand_number = rand(1, 5);
    $view->setCurrentPage($rand_number);
    $rand_number = rand(6, 11);
    $view->pager->setCurrentPage($rand_number);
    $this->assertEquals($rand_number, $view->getCurrentPage(), 'Make sure getCurrentPage uses the settings of set_current_page.');

    // Set an invalid page and make sure the method takes care about it.
    $view->setCurrentPage(-1);
    $this->assertEquals(0, $view->getCurrentPage(), 'Make sure setCurrentPage always sets a valid page number.');
  }

  /**
   * Tests translating the pager using config_translation.
   */
  public function testPagerConfigTranslation() {
    $view = Views::getView('content');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['pager']['options']['items_per_page'] = 5;
    $view->save();

    // Enable locale, config_translation and language module.
    $this->container->get('module_installer')->install(['locale', 'language', 'config_translation']);
    $this->resetAll();

    $admin_user = $this->drupalCreateUser([
      'access content overview',
      'administer nodes',
      'bypass node access',
      'translate configuration',
    ]);
    $this->drupalLogin($admin_user);

    $langcode = 'nl';

    // Add a default locale storage for this test.
    $this->localeStorage = $this->container->get('locale.storage');

    // Add Dutch language programmatically.
    ConfigurableLanguage::createFromLangcode($langcode)->save();

    $edit = [
      'translation[config_names][views.view.content][display][default][display_options][pager][options][tags][first]' => '« Eerste',
      'translation[config_names][views.view.content][display][default][display_options][pager][options][tags][previous]' => '‹ Vorige',
      'translation[config_names][views.view.content][display][default][display_options][pager][options][tags][next]' => 'Volgende ›',
      'translation[config_names][views.view.content][display][default][display_options][pager][options][tags][last]' => 'Laatste »',
    ];
    $this->drupalGet('admin/structure/views/view/content/translate/nl/edit');
    $this->submitForm($edit, 'Save translation');

    // We create 11 nodes, this will give us 3 pages.
    $this->drupalCreateContentType(['type' => 'page']);
    for ($i = 0; $i < 11; $i++) {
      $this->drupalCreateNode();
    }

    // Go to the second page so we see both previous and next buttons.
    $this->drupalGet('nl/admin/content', ['query' => ['page' => 1]]);
    // Translation mapping..
    $labels = [
      '« First' => '« Eerste',
      '‹ Previous' => '‹ Vorige',
      'Next ›' => 'Volgende ›',
      'Last »' => 'Laatste »',
    ];
    foreach ($labels as $label => $translation) {
      // Check if we can find the translation.
      $this->assertSession()->pageTextContains($translation);
    }
  }

  /**
   * Tests translating the pager using locale.
   */
  public function testPagerLocale() {
    // Enable locale and language module.
    $this->container->get('module_installer')->install(['locale', 'language']);
    $this->resetAll();
    $langcode = 'nl';

    // Add a default locale storage for this test.
    $this->localeStorage = $this->container->get('locale.storage');

    // Add Dutch language programmatically.
    ConfigurableLanguage::createFromLangcode($langcode)->save();

    // Labels that need translations.
    $labels = [
      '« First' => '« Eerste',
      '‹ Previous' => '‹ Vorige',
      'Next ›' => 'Volgende ›',
      'Last »' => 'Laatste »',
    ];
    foreach ($labels as $label => $translation) {
      // Create source string.
      $source = $this->localeStorage->createString(
        [
          'source' => $label,
        ]
      );
      $source->save();
      $this->createTranslation($source, $translation, $langcode);
    }

    // We create 11 nodes, this will give us 3 pages.
    $this->drupalCreateContentType(['type' => 'page']);
    for ($i = 0; $i < 11; $i++) {
      $this->drupalCreateNode();
    }

    // Go to the second page so we see both previous and next buttons.
    $this->drupalGet('nl/test_pager_full', ['query' => ['page' => 1]]);
    foreach ($labels as $label => $translation) {
      // Check if we can find the translation.
      $this->assertSession()->pageTextContains($translation);
    }
  }

  /**
   * Creates single translation for source string.
   */
  protected function createTranslation($source, $translation, $langcode) {
    $values = [
      'lid' => $source->lid,
      'language' => $langcode,
      'translation' => $translation,
    ];
    return $this->localeStorage->createTranslation($values)->save();
  }

}
