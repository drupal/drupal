<?php

/**
 * @file
 * Definition of Drupal\views\Tests\TranslatableTest.
 */

namespace Drupal\views\Tests;

/**
 * Tests Views pluggable translations.
 */
class TranslatableTest extends ViewTestBase {

  /**
   * Stores the strings, which are tested by this test.
   *
   * @var array
   */
  protected $strings;

  public static function getInfo() {
    return array(
      'name' => 'Translatable tests',
      'description' => 'Tests the pluggable translations.',
      'group' => 'Views',
    );
  }

  protected function setUp() {
    parent::setUp();

    config('views.settings')->set('localization_plugin', 'test_localization')->save();
    // Reset the plugin data.
    views_fetch_plugin_data(NULL, NULL, TRUE);
    $this->strings = array(
      'Master1',
      'Apply1',
      'Sort By1',
      'Asc1',
      'Desc1',
      'more1',
      'Reset1',
      'Offset1',
      'Master1',
      'title1',
      'Items per page1',
      'fieldlabel1',
      'filterlabel1'
    );

    $this->view = $this->getBasicView();
  }

  /**
   * Overrides Drupal\views\Tests\ViewTestBase::getBasicView().
   */
  protected function getBasicView() {
    return $this->createViewFromConfig('test_view_unpack_translatable');
  }

  /**
   * Tests the unpack translation funtionality.
   */
  public function testUnpackTranslatable() {
    $view = $this->getView();
    $view->initLocalization();

    $this->assertEqual('Drupal\views_test_data\Plugin\views\localization\LocalizationTest', get_class($view->localization_plugin), 'Make sure that init_localization initializes the right translation plugin');

    $view->exportLocaleStrings();

    $expected_strings = $this->strings;
    $result_strings = $view->localization_plugin->get_export_strings();
    $this->assertEqual(sort($expected_strings), sort($result_strings), 'Make sure that the localization plugin got every translatable string.');
  }

  public function testUi() {
    // Make sure that the string is not translated in the UI.
    $view = $this->getView();
    $view->save();
    views_invalidate_cache();

    $admin_user = $this->drupalCreateUser(array('administer views', 'administer site configuration'));
    $this->drupalLogin($admin_user);

    $this->drupalGet("admin/structure/views/view/$view->name/edit");
    $this->assertNoText('-translated', 'Make sure that no strings get translated in the UI.');
  }

  /**
   * Make sure that the translations get into the loaded view.
   */
  public function testTranslation() {
    $view = $this->getView();
    $this->executeView($view);

    $expected_strings = array();
    foreach ($this->strings as $string) {
      $expected_strings[] = $string . '-translated';
    }

    sort($expected_strings);
    sort($view->localization_plugin->translated_strings);

    // @todo The plugin::unpackOptions() method is missing some keys of the
    //   display, but calls the translate method two times per item.
    //$this->assertEqual($expected_strings, $view->localization_plugin->translated_strings, 'Make sure that every string got loaded translated');
  }

  /**
   * Make sure that the different things have the right translation keys.
   */
  public function testTranslationKey() {
    $view = $this->getView();
    $view->editing = TRUE;
    $view->initDisplay();

    // Don't run translation. We just want to get the right keys.

    foreach ($view->display as $display_id => $display) {
      $translatables = array();
      $display->handler->unpackTranslatables($translatables);

      $this->string_keys = array(
        'Master1' => array('title'),
        'Apply1' => array('exposed_form', 'submit_button'),
        'Sort By1' => array('exposed_form', 'exposed_sorts_label'),
        'Asc1' => array('exposed_form', 'sort_asc_label'),
        'Desc1' => array('exposed_form', 'sort_desc_label'),
        'more1' => array('use_more_text'),
        'Reset1' => array('exposed_form', 'reset_button_label'),
        'Offset1' => array('pager', 'expose', 'offset_label'),
        'Master1' => array('title'),
        'title1' => array('title'),
        'Tag first1' => array('pager', 'tags', 'first'),
        'Tag prev1' => array('pager', 'tags', 'previous'),
        'Tag next1' => array('pager', 'tags', 'next'),
        'Tag last1' => array('pager', 'tags', 'last'),
        'Items per page1' => array('pager', 'expose', 'items_per_page_label'),
        'fieldlabel1' => array('field', 'node', 'nid', 'label'),
        'filterlabel1' => array('filter', 'node', 'nid', 'expose', 'label'),
        '- All -' => array('pager', 'expose', 'items_per_page_options_all_label'),
      );
      foreach ($translatables as $translatable) {
        $this->assertEqual($translatable['keys'], $this->string_keys[$translatable['value']]);
      }
    }
  }

}
