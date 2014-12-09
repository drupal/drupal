<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Ajax\FrameworkTest.
 */

namespace Drupal\system\Tests\Ajax;

use Drupal\Core\Ajax\AddCssCommand;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\SettingsCommand;

/**
 * Performs tests on AJAX framework functions.
 *
 * @group Ajax
 */
class FrameworkTest extends AjaxTestBase {
  /**
   * Ensures \Drupal\Core\Ajax\AjaxResponse::ajaxRender() returns JavaScript settings from the page request.
   */
  public function testAJAXRender() {
    // Verify that settings command is generated when JavaScript settings are
    // set via _drupal_add_js().
    $commands = $this->drupalGetAJAX('ajax-test/render');
    $expected = new SettingsCommand(array('ajax' => 'test'), TRUE);
    $this->assertCommand($commands, $expected->render(), '\Drupal\Core\Ajax\AjaxResponse::ajaxRender() loads settings added with _drupal_add_js().');
  }

  /**
   * Tests AjaxResponse::prepare() AJAX commands ordering.
   */
  public function testOrder() {
    $expected_commands = array();

    // Expected commands, in a very specific order.
    $expected_commands[0] = new SettingsCommand(array('ajax' => 'test'), TRUE);
    drupal_static_reset('_drupal_add_css');
    $build['#attached']['library'][] = 'ajax_test/order-css-command';
    drupal_process_attached($build);
    $expected_commands[1] = new AddCssCommand(drupal_get_css(_drupal_add_css(), TRUE));
    drupal_static_reset('_drupal_add_js');
    $build['#attached']['library'][] = 'ajax_test/order-js-command';
    drupal_process_attached($build);
    $expected_commands[2] = new PrependCommand('head', drupal_get_js('header', _drupal_add_js(), TRUE));
    $expected_commands[3] = new AppendCommand('body', drupal_get_js('footer', _drupal_add_js(), TRUE));
    $expected_commands[4] = new HtmlCommand('body', 'Hello, world!');

    // Load any page with at least one CSS file, at least one JavaScript file
    // and at least one #ajax-powered element. The latter is an assumption of
    // drupalPostAjaxForm(), the two former are assumptions of
    // AjaxReponse::ajaxRender().
    // @todo refactor AJAX Framework + tests to make less assumptions.
    $this->drupalGet('ajax_forms_test_lazy_load_form');

    // Verify AJAX command order — this should always be the order:
    // 1. JavaScript settings
    // 2. CSS files
    // 3. JavaScript files in the header
    // 4. JavaScript files in the footer
    // 5. Any other AJAX commands, in whatever order they were added.
    $commands = $this->drupalPostAjaxForm(NULL, array(), NULL, 'ajax-test/order', array(), array(), NULL, array());
    $this->assertCommand(array_slice($commands, 0, 1), $expected_commands[0]->render(), 'Settings command is first.');
    $this->assertCommand(array_slice($commands, 1, 1), $expected_commands[1]->render(), 'CSS command is second (and CSS files are ordered correctly).');
    $this->assertCommand(array_slice($commands, 2, 1), $expected_commands[2]->render(), 'Header JS command is third.');
    $this->assertCommand(array_slice($commands, 3, 1), $expected_commands[3]->render(), 'Footer JS command is fourth.');
    $this->assertCommand(array_slice($commands, 4, 1), $expected_commands[4]->render(), 'HTML command is fifth.');
  }

  /**
   * Tests the behavior of an error alert command.
   */
  public function testAJAXRenderError() {
    // Verify custom error message.
    $edit = array(
      'message' => 'Custom error message.',
    );
    $commands = $this->drupalGetAJAX('ajax-test/render-error', array('query' => $edit));
    $expected = new AlertCommand($edit['message']);
    $this->assertCommand($commands, $expected->render(), 'Custom error message is output.');
  }

  /**
   * Tests that new JavaScript and CSS files are lazy-loaded on an AJAX request.
   */
  public function testLazyLoad() {
    $expected = array(
      'setting_name' => 'ajax_forms_test_lazy_load_form_submit',
      'setting_value' => 'executed',
      'css' => drupal_get_path('module', 'system') . '/css/system.admin.css',
      'js' => drupal_get_path('module', 'system') . '/system.js',
    );
    // CSS files are stored by basename, see _drupal_add_css().
    $expected_css_basename = drupal_basename($expected['css']);

    // @todo D8: Add a drupal_css_defaults() helper function.
    $expected_css_html = drupal_get_css(array($expected_css_basename => array(
      'type' => 'file',
      'group' => CSS_AGGREGATE_DEFAULT,
      'weight' => 0,
      'every_page' => FALSE,
      'media' => 'all',
      'preprocess' => TRUE,
      'data' => $expected['css'],
      'browsers' => array('IE' => TRUE, '!IE' => TRUE),
    )), TRUE);
    $expected_js_html = drupal_get_js('header', array($expected['js'] => ['version' => \Drupal::VERSION] + drupal_js_defaults($expected['js'])), TRUE);

    // Get the base page.
    $this->drupalGet('ajax_forms_test_lazy_load_form');
    $original_settings = $this->getDrupalSettings();
    $original_css = $original_settings['ajaxPageState']['css'];
    $original_js = $original_settings['ajaxPageState']['js'];

    // Verify that the base page doesn't have the settings and files that are to
    // be lazy loaded as part of the next requests.
    $this->assertTrue(!isset($original_settings[$expected['setting_name']]), format_string('Page originally lacks the %setting, as expected.', array('%setting' => $expected['setting_name'])));
    $this->assertTrue(!isset($original_css[$expected['css']]), format_string('Page originally lacks the %css file, as expected.', array('%css' => $expected['css'])));
    $this->assertTrue(!isset($original_js[$expected['js']]), format_string('Page originally lacks the %js file, as expected.', array('%js' => $expected['js'])));

    // Submit the AJAX request without triggering files getting added.
    $commands = $this->drupalPostAjaxForm(NULL, array('add_files' => FALSE), array('op' => t('Submit')));
    $new_settings = $this->getDrupalSettings();
    $new_css = $new_settings['ajaxPageState']['css'];
    $new_js = $new_settings['ajaxPageState']['js'];

    // Verify the setting was not added when not expected.
    $this->assertTrue(!isset($new_settings[$expected['setting_name']]), format_string('Page still lacks the %setting, as expected.', array('%setting' => $expected['setting_name'])));
    $this->assertTrue(!isset($new_css[$expected['css']]), format_string('Page still lacks the %css file, as expected.', array('%css' => $expected['css'])));
    $this->assertTrue(!isset($new_js[$expected['js']]), format_string('Page still lacks the %js file, as expected.', array('%js' => $expected['js'])));
    // Verify a settings command does not add CSS or scripts to drupalSettings
    // and no command inserts the corresponding tags on the page.
    $found_settings_command = FALSE;
    $found_markup_command = FALSE;
    foreach ($commands as $command) {
      if ($command['command'] == 'settings' && (array_key_exists('css', $command['settings']['ajaxPageState']) || array_key_exists('js', $command['settings']['ajaxPageState']))) {
        $found_settings_command = TRUE;
      }
      if (isset($command['data']) && ($command['data'] == $expected_js_html || $command['data'] == $expected_css_html)) {
        $found_markup_command = TRUE;
      }
    }
    $this->assertFalse($found_settings_command, format_string('Page state still lacks the %css and %js files, as expected.', array('%css' => $expected['css'], '%js' => $expected['js'])));
    $this->assertFalse($found_markup_command, format_string('Page still lacks the %css and %js files, as expected.', array('%css' => $expected['css'], '%js' => $expected['js'])));

    // Submit the AJAX request and trigger adding files.
    $commands = $this->drupalPostAjaxForm(NULL, array('add_files' => TRUE), array('op' => t('Submit')));
    $new_settings = $this->getDrupalSettings();
    $new_css = $new_settings['ajaxPageState']['css'];
    $new_js = $new_settings['ajaxPageState']['js'];

    // Verify the expected setting was added, both to drupalSettings, and as
    // the first AJAX command.
    $this->assertIdentical($new_settings[$expected['setting_name']], $expected['setting_value'], format_string('Page now has the %setting.', array('%setting' => $expected['setting_name'])));
    $expected_command = new SettingsCommand(array($expected['setting_name'] => $expected['setting_value']), TRUE);
    $this->assertCommand(array_slice($commands, 0, 1), $expected_command->render(), format_string('The settings command was first.'));

    // Verify the expected CSS file was added, both to drupalSettings, and as
    // the second AJAX command for inclusion into the HTML.
    $this->assertEqual($new_css, $original_css + array($expected_css_basename => 1), format_string('Page state now has the %css file.', array('%css' => $expected['css'])));
    $this->assertCommand(array_slice($commands, 1, 1), array('data' => $expected_css_html), format_string('Page now has the %css file.', array('%css' => $expected['css'])));

    // Verify the expected JS file was added, both to drupalSettings, and as
    // the third AJAX command for inclusion into the HTML. By testing for an
    // exact HTML string containing the SCRIPT tag, we also ensure that
    // unexpected JavaScript code, such as a jQuery.extend() that would
    // potentially clobber rather than properly merge settings, didn't
    // accidentally get added.
    $this->assertEqual($new_js, $original_js + array($expected['js'] => 1), format_string('Page state now has the %js file.', array('%js' => $expected['js'])));
    $this->assertCommand(array_slice($commands, 2, 1), array('data' => $expected_js_html), format_string('Page now has the %js file.', array('%js' => $expected['js'])));
  }

  /**
   * Tests that drupalSettings.currentPath is not updated on AJAX requests.
   */
  public function testCurrentPathChange() {
    $commands = $this->drupalPostAjaxForm('ajax_forms_test_lazy_load_form', array('add_files' => FALSE), array('op' => t('Submit')));
    foreach ($commands as $command) {
      if ($command['command'] == 'settings') {
        $this->assertFalse(isset($command['settings']['currentPath']), 'Value of drupalSettings.currentPath is not updated after an AJAX request.');
      }
    }
  }

  /**
   * Tests that overridden CSS files are not added during lazy load.
   */
  public function testLazyLoadOverriddenCSS() {
    // The test theme overrides system.module.css without an implementation,
    // thereby removing it.
    \Drupal::service('theme_handler')->install(array('test_theme'));
    \Drupal::config('system.theme')
      ->set('default', 'test_theme')
      ->save();

    // This gets the form, and emulates an Ajax submission on it, including
    // adding markup to the HEAD and BODY for any lazy loaded JS/CSS files.
    $this->drupalPostAjaxForm('ajax_forms_test_lazy_load_form', array('add_files' => TRUE), array('op' => t('Submit')));

    // Verify that the resulting HTML does not load the overridden CSS file.
    // We add a "?" to the assertion, because drupalSettings may include
    // information about the file; we only really care about whether it appears
    // in a LINK or STYLE tag, for which Drupal always adds a query string for
    // cache control.
    $this->assertNoText('system.module.css?', 'Ajax lazy loading does not add overridden CSS files.');
  }
}
