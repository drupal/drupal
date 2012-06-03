<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Ajax\FrameworkTest.
 */

namespace Drupal\system\Tests\Ajax;

/**
 * Tests primary Ajax framework functions.
 */
class FrameworkTest extends AjaxTestBase {
  public static function getInfo() {
    return array(
      'name' => 'AJAX framework',
      'description' => 'Performs tests on AJAX framework functions.',
      'group' => 'AJAX',
    );
  }

  /**
   * Test that ajax_render() returns JavaScript settings generated during the page request.
   *
   * @todo Add tests to ensure that ajax_render() returns commands for new CSS
   *   and JavaScript files to be loaded by the page. See
   *   http://drupal.org/node/561858.
   */
  function testAJAXRender() {
    $commands = $this->drupalGetAJAX('ajax-test/render');

    // Verify that there is a command to load settings added with
    // drupal_add_js().
    $expected = array(
      'command' => 'settings',
      'settings' => array('ajax' => 'test'),
    );
    $this->assertCommand($commands, $expected, t('ajax_render() loads settings added with drupal_add_js().'));

    // Verify that Ajax settings are loaded for #type 'link'.
    $this->drupalGet('ajax-test/link');
    $settings = $this->drupalGetSettings();
    $this->assertEqual($settings['ajax']['ajax-link']['url'], url('filter/tips'));
    $this->assertEqual($settings['ajax']['ajax-link']['wrapper'], 'block-system-main');
  }

  /**
   * Test behavior of ajax_render_error().
   */
  function testAJAXRenderError() {
    // Verify default error message.
    $commands = $this->drupalGetAJAX('ajax-test/render-error');
    $expected = array(
      'command' => 'alert',
      'text' => t('An error occurred while handling the request: The server received invalid input.'),
    );
    $this->assertCommand($commands, $expected, t('ajax_render_error() invokes alert command.'));

    // Verify custom error message.
    $edit = array(
      'message' => 'Custom error message.',
    );
    $commands = $this->drupalGetAJAX('ajax-test/render-error', array('query' => $edit));
    $expected = array(
      'command' => 'alert',
      'text' => $edit['message'],
    );
     $this->assertCommand($commands, $expected, t('Custom error message is output.'));
  }

  /**
   * Test that new JavaScript and CSS files added during an AJAX request are returned.
   */
  function testLazyLoad() {
    $expected = array(
      'setting_name' => 'ajax_forms_test_lazy_load_form_submit',
      'setting_value' => 'executed',
      'css' => drupal_get_path('module', 'system') . '/system.admin.css',
      'js' => drupal_get_path('module', 'system') . '/system.js',
    );
    // @todo D8: Add a drupal_css_defaults() helper function.
    $expected_css_html = drupal_get_css(array($expected['css'] => array(
      'type' => 'file',
      'group' => CSS_DEFAULT,
      'weight' => 0,
      'every_page' => FALSE,
      'media' => 'all',
      'preprocess' => TRUE,
      'data' => $expected['css'],
      'browsers' => array('IE' => TRUE, '!IE' => TRUE),
    )), TRUE);
    $expected_js_html = drupal_get_js('header', array($expected['js'] => drupal_js_defaults($expected['js'])), TRUE);

    // Get the base page.
    $this->drupalGet('ajax_forms_test_lazy_load_form');
    $original_settings = $this->drupalGetSettings();
    $original_css = $original_settings['ajaxPageState']['css'];
    $original_js = $original_settings['ajaxPageState']['js'];

    // Verify that the base page doesn't have the settings and files that are to
    // be lazy loaded as part of the next requests.
    $this->assertTrue(!isset($original_settings[$expected['setting_name']]), t('Page originally lacks the %setting, as expected.', array('%setting' => $expected['setting_name'])));
    $this->assertTrue(!isset($original_settings[$expected['css']]), t('Page originally lacks the %css file, as expected.', array('%css' => $expected['css'])));
    $this->assertTrue(!isset($original_settings[$expected['js']]), t('Page originally lacks the %js file, as expected.', array('%js' => $expected['js'])));

    // Submit the AJAX request without triggering files getting added.
    $commands = $this->drupalPostAJAX(NULL, array('add_files' => FALSE), array('op' => t('Submit')));
    $new_settings = $this->drupalGetSettings();

    // Verify the setting was not added when not expected.
    $this->assertTrue(!isset($new_settings['setting_name']), t('Page still lacks the %setting, as expected.', array('%setting' => $expected['setting_name'])));
    // Verify a settings command does not add CSS or scripts to Drupal.settings
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
    $this->assertFalse($found_settings_command, t('Page state still lacks the %css and %js files, as expected.', array('%css' => $expected['css'], '%js' => $expected['js'])));
    $this->assertFalse($found_markup_command, t('Page still lacks the %css and %js files, as expected.', array('%css' => $expected['css'], '%js' => $expected['js'])));

    // Submit the AJAX request and trigger adding files.
    $commands = $this->drupalPostAJAX(NULL, array('add_files' => TRUE), array('op' => t('Submit')));
    $new_settings = $this->drupalGetSettings();
    $new_css = $new_settings['ajaxPageState']['css'];
    $new_js = $new_settings['ajaxPageState']['js'];

    // Verify the expected setting was added.
    $this->assertIdentical($new_settings[$expected['setting_name']], $expected['setting_value'], t('Page now has the %setting.', array('%setting' => $expected['setting_name'])));

    // Verify the expected CSS file was added, both to Drupal.settings, and as
    // an AJAX command for inclusion into the HTML.
    $this->assertEqual($new_css, $original_css + array($expected['css'] => 1), t('Page state now has the %css file.', array('%css' => $expected['css'])));
    $this->assertCommand($commands, array('data' => $expected_css_html), t('Page now has the %css file.', array('%css' => $expected['css'])));

    // Verify the expected JS file was added, both to Drupal.settings, and as
    // an AJAX command for inclusion into the HTML. By testing for an exact HTML
    // string containing the SCRIPT tag, we also ensure that unexpected
    // JavaScript code, such as a jQuery.extend() that would potentially clobber
    // rather than properly merge settings, didn't accidentally get added.
    $this->assertEqual($new_js, $original_js + array($expected['js'] => 1), t('Page state now has the %js file.', array('%js' => $expected['js'])));
    $this->assertCommand($commands, array('data' => $expected_js_html), t('Page now has the %js file.', array('%js' => $expected['js'])));
  }

  /**
   * Tests that overridden CSS files are not added during lazy load.
   */
  function testLazyLoadOverriddenCSS() {
    // The test theme overrides system.base.css without an implementation,
    // thereby removing it.
    theme_enable(array('test_theme'));
    variable_set('theme_default', 'test_theme');

    // This gets the form, and emulates an Ajax submission on it, including
    // adding markup to the HEAD and BODY for any lazy loaded JS/CSS files.
    $this->drupalPostAJAX('ajax_forms_test_lazy_load_form', array('add_files' => TRUE), array('op' => t('Submit')));

    // Verify that the resulting HTML does not load the overridden CSS file.
    // We add a "?" to the assertion, because Drupal.settings may include
    // information about the file; we only really care about whether it appears
    // in a LINK or STYLE tag, for which Drupal always adds a query string for
    // cache control.
    $this->assertNoText('system.base.css?', 'Ajax lazy loading does not add overridden CSS files.');
  }
}
