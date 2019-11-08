<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;
use Drupal\language\Entity\ConfigurableLanguage;

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
  public static $modules = ['node', 'views_ui'];

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
    // Show the master display so the override selection is shown.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.show.master_display', TRUE)->save();

    $admin_user = $this->drupalCreateUser(['administer views', 'administer site configuration']);
    $this->drupalLogin($admin_user);
    // Test behavior described in
    //   https://www.drupal.org/node/652712#comment-2354918.

    $this->drupalGet('admin/structure/views/view/test_view/edit');

    $edit = [
      'pager[type]' => 'some',
    ];
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/default/pager', $edit, t('Apply'));

    $this->assertFieldByXPath('//input[@name="pager_options[items_per_page]" and @type="number" and @min="0"]', 10, '"Items per page" field was found.');
    $this->assertFieldByXPath('//input[@name="pager_options[offset]" and @type="number" and @min="0"]', 0, '"Offset" field was found.');

    $edit = [
      'pager[type]' => 'none',
    ];
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/default/pager', $edit, t('Apply'));

    $this->assertFieldByXPath('//input[@name="pager_options[offset]" and @type="number" and @min="0"]', 0, '"Offset" field was found.');

    $edit = [
      'pager[type]' => 'full',
    ];
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/default/pager', $edit, t('Apply'));

    $this->assertFieldByXPath('//input[@name="pager_options[items_per_page]" and @type="number" and @min="0"]', 10, '"Items to display" field was found.');
    $this->assertFieldByXPath('//input[@name="pager_options[offset]" and @type="number" and @min="0"]', 0, '"Offset" field was found.');
    $this->assertFieldByXPath('//input[@name="pager_options[id]" and @type="number" and @min="0"]', 0, '"Pager ID" field was found.');
    $this->assertFieldByXPath('//input[@name="pager_options[total_pages]" and @type="number" and @min="0"]', '', '"Number of pages" field was found.');
    $this->assertFieldByXPath('//input[@name="pager_options[quantity]" and @type="number" and @min="0"]', 9, '"Number of pager links" field was found.');

    $edit = [
      'pager_options[items_per_page]' => 20,
    ];
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/default/pager_options', $edit, t('Apply'));
    $this->assertText('20 items');

    // Change type and check whether the type is new type is stored.
    $edit = [
      'pager[type]' => 'mini',
    ];
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/default/pager', $edit, t('Apply'));
    $this->drupalGet('admin/structure/views/view/test_view/edit');
    $this->assertText('Mini', 'Changed pager plugin, should change some text');

    // Test behavior described in
    //   https://www.drupal.org/node/652712#comment-2354400.
    $view = Views::getView('test_store_pager_settings');
    // Make it editable in the admin interface.
    $view->save();

    $this->drupalGet('admin/structure/views/view/test_store_pager_settings/edit');

    $edit = [
      'pager[type]' => 'full',
    ];
    $this->drupalPostForm('admin/structure/views/nojs/display/test_store_pager_settings/default/pager', $edit, t('Apply'));
    $this->drupalGet('admin/structure/views/view/test_store_pager_settings/edit');
    $this->assertText('Full');

    $edit = [
      'pager_options[items_per_page]' => 20,
    ];
    $this->drupalPostForm('admin/structure/views/nojs/display/test_store_pager_settings/default/pager_options', $edit, t('Apply'));
    $this->assertText('20 items');

    // add new display and test the settings again, by override it.
    $edit = [];
    // Add a display and override the pager settings.
    $this->drupalPostForm('admin/structure/views/view/test_store_pager_settings/edit', $edit, t('Add Page'));
    $edit = [
      'override[dropdown]' => 'page_1',
    ];
    $this->drupalPostForm('admin/structure/views/nojs/display/test_store_pager_settings/page_1/pager', $edit, t('Apply'));

    $edit = [
      'pager[type]' => 'mini',
    ];
    $this->drupalPostForm('admin/structure/views/nojs/display/test_store_pager_settings/page_1/pager', $edit, t('Apply'));
    $this->drupalGet('admin/structure/views/view/test_store_pager_settings/edit/page_1');
    $this->assertText('Mini', 'Changed pager plugin, should change some text');

    $edit = [
      'pager_options[items_per_page]' => 10,
    ];
    $this->drupalPostForm('admin/structure/views/nojs/display/test_store_pager_settings/default/pager_options', $edit, t('Apply'));
    $this->assertText('10 items', 'The default value has been changed.');
    $this->drupalGet('admin/structure/views/view/test_store_pager_settings/edit/page_1');
    $this->assertText('20 items', 'The original value remains unchanged.');

    // Test that the override element is only displayed on pager plugin selection form.
    $this->drupalGet('admin/structure/views/nojs/display/test_store_pager_settings/page_1/pager');
    $this->assertFieldByName('override[dropdown]', 'page_1', 'The override element is displayed on plugin selection form.');
    $this->drupalGet('admin/structure/views/nojs/display/test_store_pager_settings/page_1/pager_options');
    $this->assertNoFieldByName('override[dropdown]', NULL, 'The override element is not displayed on plugin settings form.');

    $this->assertFieldByXPath('//input[@name="pager_options[items_per_page]" and @type="number" and @min="0"]', 20, '"Items per page" field was found.');
    $this->assertFieldByXPath('//input[@name="pager_options[offset]" and @type="number" and @min="0"]', 0, '"Offset" field was found.');
    $this->assertFieldByXPath('//input[@name="pager_options[id]" and @type="number" and @min="0"]', 0, '"Pager ID" field was found.');
    $this->assertFieldByXPath('//input[@name="pager_options[total_pages]" and @type="number" and @min="0"]', '', '"Number of pages" field was found.');
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
    $this->assertEqual(count($view->result), 11, 'Make sure that every item is returned in the result');

    // Setup and test a offset.
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

    $this->assertEqual(count($view->result), 8, 'Make sure that every item beside the first three is returned in the result');

    // Check some public functions.
    $this->assertFalse($view->pager->usePager());
    $this->assertFalse($view->pager->useCountQuery());
    $this->assertEqual($view->pager->getItemsPerPage(), 0);
  }

  public function testViewTotalRowsWithoutPager() {
    $this->drupalCreateContentType(['type' => 'page']);
    for ($i = 0; $i < 23; $i++) {
      $this->drupalCreateNode();
    }

    $view = Views::getView('test_pager_none');
    $view->get_total_rows = TRUE;
    $this->executeView($view);

    $this->assertEqual($view->total_rows, 23, "'total_rows' is calculated when pager type is 'none' and 'get_total_rows' is TRUE.");
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
    $this->assertEqual(count($view->result), 5, 'Make sure that only a certain count of items is returned');

    // Setup and test a offset.
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
    $this->assertEqual(count($view->result), 3, 'Make sure that only a certain count of items is returned');

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
    $this->assertEqual(count($view->result), 5, 'Make sure that only a certain count of items is returned');

    // Setup and test a offset.
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
    $this->assertEqual(count($view->result), 3, 'Make sure that only a certain count of items is returned');

    // Test items per page = 0
    $view = Views::getView('test_view_pager_full_zero_items_per_page');
    $this->executeView($view);

    $this->assertEqual(count($view->result), 11, 'All items are return');

    // TODO test number of pages.

    // Test items per page = 0.
    // Setup and test a offset.
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
    $this->assertEqual($view->pager->getItemsPerPage(), 0);
    $this->assertEqual(count($view->result), 11);

    // Test pager cache contexts.
    $this->drupalGet('test_pager_full');
    $this->assertCacheContexts(['languages:language_interface', 'theme', 'timezone', 'url.query_args', 'user.node_grants:view']);
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
    $this->assertEqual(preg_match('/<ul class="pager">/', $output), 0, 'The pager is not rendered.');
  }

  /**
   * Test the api functions on the view object.
   */
  public function testPagerApi() {
    $view = Views::getView('test_pager_full');
    $view->setDisplay();
    // On the first round don't initialize the pager.

    $this->assertEqual($view->getItemsPerPage(), NULL, 'If the pager is not initialized and no manual override there is no items per page.');
    $rand_number = rand(1, 5);
    $view->setItemsPerPage($rand_number);
    $this->assertEqual($view->getItemsPerPage(), $rand_number, 'Make sure getItemsPerPage uses the settings of setItemsPerPage.');

    $this->assertEqual($view->getOffset(), NULL, 'If the pager is not initialized and no manual override there is no offset.');
    $rand_number = rand(1, 5);
    $view->setOffset($rand_number);
    $this->assertEqual($view->getOffset(), $rand_number, 'Make sure getOffset uses the settings of setOffset.');

    $this->assertEqual($view->getCurrentPage(), NULL, 'If the pager is not initialized and no manual override there is no current page.');
    $rand_number = rand(1, 5);
    $view->setCurrentPage($rand_number);
    $this->assertEqual($view->getCurrentPage(), $rand_number, 'Make sure getCurrentPage uses the settings of set_current_page.');

    $view->destroy();

    // On this round enable the pager.
    $view->initDisplay();
    $view->initQuery();
    $view->initPager();

    $this->assertEqual($view->getItemsPerPage(), 5, 'Per default the view has 5 items per page.');
    $rand_number = rand(1, 5);
    $view->setItemsPerPage($rand_number);
    $rand_number = rand(6, 11);
    $view->pager->setItemsPerPage($rand_number);
    $this->assertEqual($view->getItemsPerPage(), $rand_number, 'Make sure getItemsPerPage uses the settings of setItemsPerPage.');

    $this->assertEqual($view->getOffset(), 0, 'Per default a view has a 0 offset.');
    $rand_number = rand(1, 5);
    $view->setOffset($rand_number);
    $rand_number = rand(6, 11);
    $view->pager->setOffset($rand_number);
    $this->assertEqual($view->getOffset(), $rand_number, 'Make sure getOffset uses the settings of setOffset.');

    $this->assertEqual($view->getCurrentPage(), 0, 'Per default the current page is 0.');
    $rand_number = rand(1, 5);
    $view->setCurrentPage($rand_number);
    $rand_number = rand(6, 11);
    $view->pager->setCurrentPage($rand_number);
    $this->assertEqual($view->getCurrentPage(), $rand_number, 'Make sure getCurrentPage uses the settings of set_current_page.');

    // Set an invalid page and make sure the method takes care about it.
    $view->setCurrentPage(-1);
    $this->assertEqual($view->getCurrentPage(), 0, 'Make sure setCurrentPage always sets a valid page number.');
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

    $admin_user = $this->drupalCreateUser(['access content overview', 'administer nodes', 'bypass node access', 'translate configuration']);
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
    $this->drupalPostForm('admin/structure/views/view/content/translate/nl/edit', $edit, t('Save translation'));

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
      $this->assertRaw($translation);
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
      $this->assertRaw($translation);
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
