<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\PagerTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views\Views;

/**
 * Tests the pluggable pager system.
 *
 * @group views
 */
class PagerTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_store_pager_settings', 'test_pager_none', 'test_pager_some', 'test_pager_full', 'test_view_pager_full_zero_items_per_page', 'test_view');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'views_ui');

  /**
   * Pagers was sometimes not stored.
   *
   * @see http://drupal.org/node/652712
   */
  public function testStorePagerSettings() {
    $admin_user = $this->drupalCreateUser(array('administer views', 'administer site configuration'));
    $this->drupalLogin($admin_user);
    // Test behavior described in http://drupal.org/node/652712#comment-2354918.

    $this->drupalGet('admin/structure/views/view/test_view/edit');

    $edit = array(
      'pager[type]' => 'full',
    );
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/default/pager', $edit, t('Apply'));
    $edit = array(
      'pager_options[items_per_page]' => 20,
    );
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/default/pager_options', $edit, t('Apply'));
    $this->assertText('20 items');

    // Change type and check whether the type is new type is stored.
    $edit = array(
      'pager[type]' => 'mini',
    );
    $this->drupalPostForm('admin/structure/views/nojs/display/test_view/default/pager', $edit, t('Apply'));
    $this->drupalGet('admin/structure/views/view/test_view/edit');
    $this->assertText('Mini', 'Changed pager plugin, should change some text');

    // Test behavior described in http://drupal.org/node/652712#comment-2354400
    $view = Views::getView('test_store_pager_settings');
    // Make it editable in the admin interface.
    $view->save();

    $this->drupalGet('admin/structure/views/view/test_store_pager_settings/edit');

    $edit = array(
      'pager[type]' => 'full',
    );
    $this->drupalPostForm('admin/structure/views/nojs/display/test_store_pager_settings/default/pager', $edit, t('Apply'));
    $this->drupalGet('admin/structure/views/view/test_store_pager_settings/edit');
    $this->assertText('Full');

    $edit = array(
      'pager_options[items_per_page]' => 20,
    );
    $this->drupalPostForm('admin/structure/views/nojs/display/test_store_pager_settings/default/pager_options', $edit, t('Apply'));
    $this->assertText('20 items');

    // add new display and test the settings again, by override it.
    $edit = array( );
    // Add a display and override the pager settings.
    $this->drupalPostForm('admin/structure/views/view/test_store_pager_settings/edit', $edit, t('Add Page'));
    $edit = array(
      'override[dropdown]' => 'page_1',
    );
    $this->drupalPostForm('admin/structure/views/nojs/display/test_store_pager_settings/page_1/pager', $edit, t('Apply'));

    $edit = array(
      'pager[type]' => 'mini',
    );
    $this->drupalPostForm('admin/structure/views/nojs/display/test_store_pager_settings/page_1/pager', $edit, t('Apply'));
    $this->drupalGet('admin/structure/views/view/test_store_pager_settings/edit');
    $this->assertText('Mini', 'Changed pager plugin, should change some text');

    $edit = array(
      'pager_options[items_per_page]' => 10,
    );
    $this->drupalPostForm('admin/structure/views/nojs/display/test_store_pager_settings/default/pager_options', $edit, t('Apply'));
    $this->assertText('10 items', 'The default value has been changed.');
    $this->drupalGet('admin/structure/views/view/test_store_pager_settings/edit/page_1');
    $this->assertText('20 items', 'The original value remains unchanged.');
  }

  /**
   * Tests the none-pager-query.
   */
  public function testNoLimit() {
    // Create 11 nodes and make sure that everyone is returned.
    // We create 11 nodes, because the default pager plugin had 10 items per page.
    for ($i = 0; $i < 11; $i++) {
      $this->drupalCreateNode();
    }
    $view = Views::getView('test_pager_none');
    $this->executeView($view);
    $this->assertEqual(count($view->result), 11, 'Make sure that every item is returned in the result');

    // Setup and test a offset.
    $view = Views::getView('test_pager_none');
    $view->setDisplay();
    $pager = array(
      'type' => 'none',
      'options' => array(
        'offset' => 3,
      ),
    );
    $view->display_handler->setOption('pager', $pager);
    $this->executeView($view);

    $this->assertEqual(count($view->result), 8, 'Make sure that every item beside the first three is returned in the result');

    // Check some public functions.
    $this->assertFalse($view->pager->usePager());
    $this->assertFalse($view->pager->useCountQuery());
    $this->assertEqual($view->pager->getItemsPerPage(), 0);
  }

  public function testViewTotalRowsWithoutPager() {
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
    for ($i = 0; $i < 11; $i++) {
      $this->drupalCreateNode();
    }

    $view = Views::getView('test_pager_some');
    $this->executeView($view);
    $this->assertEqual(count($view->result), 5, 'Make sure that only a certain count of items is returned');

    // Setup and test a offset.
    $view = Views::getView('test_pager_some');
    $view->setDisplay();
    $pager = array(
      'type' => 'none',
      'options' => array(
        'offset' => 8,
        'items_per_page' => 5,
      ),
    );
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
    for ($i = 0; $i < 11; $i++) {
      $this->drupalCreateNode();
    }

    $view = Views::getView('test_pager_full');
    $this->executeView($view);
    $this->assertEqual(count($view->result), 5, 'Make sure that only a certain count of items is returned');

    // Setup and test a offset.
    $view = Views::getView('test_pager_full');
    $view->setDisplay();
    $pager = array(
      'type' => 'full',
      'options' => array(
        'offset' => 8,
        'items_per_page' => 5,
      ),
    );
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
    $pager = array(
      'type' => 'full',
      'options' => array(
        'offset' => 0,
        'items_per_page' => 0,
      ),
    );

    $view->display_handler->setOption('pager', $pager);
    $this->executeView($view);
    $this->assertEqual($view->pager->getItemsPerPage(), 0);
    $this->assertEqual(count($view->result), 11);
  }

  /**
   * Tests rendering with NULL pager.
   */
  public function testRenderNullPager() {
    // Create 11 nodes and make sure that everyone is returned.
    // We create 11 nodes, because the default pager plugin had 10 items per page.
    for ($i = 0; $i < 11; $i++) {
      $this->drupalCreateNode();
    }
    $view = Views::getView('test_pager_full');
    $this->executeView($view);
    // Force the value again here.
    $view->setAjaxEnabled(TRUE);
    $view->pager = NULL;
    $output = $view->render();
    $output = drupal_render($output);
    $this->assertEqual(preg_match('/<ul class="pager">/', $output), 0, 'The pager is not rendered.');
  }

  /**
   * Test the api functions on the view object.
   */
  function testPagerApi() {
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

}
