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
use Drupal\Core\Asset\AttachedAssets;

/**
 * Performs tests on AJAX framework functions.
 *
 * @group Ajax
 */
class FrameworkTest extends AjaxTestBase {
  /**
   * Verifies the Ajax rendering of a command in the settings.
   */
  public function testAJAXRender() {
    // Verify that settings command is generated if JavaScript settings exist.
    $commands = $this->drupalGetAjax('ajax-test/render');
    $expected = new SettingsCommand(array('ajax' => 'test'), TRUE);
    $this->assertCommand($commands, $expected->render(), 'JavaScript settings command is present.');
  }

  /**
   * Tests AjaxResponse::prepare() AJAX commands ordering.
   */
  public function testOrder() {
    $expected_commands = array();

    // Expected commands, in a very specific order.
    $asset_resolver = \Drupal::service('asset.resolver');
    $css_collection_renderer = \Drupal::service('asset.css.collection_renderer');
    $js_collection_renderer = \Drupal::service('asset.js.collection_renderer');
    $renderer = \Drupal::service('renderer');
    $expected_commands[0] = new SettingsCommand(array('ajax' => 'test'), TRUE);
    $build['#attached']['library'][] = 'ajax_test/order-css-command';
    $assets = AttachedAssets::createFromRenderArray($build);
    $css_render_array = $css_collection_renderer->render($asset_resolver->getCssAssets($assets, FALSE));
    $expected_commands[1] = new AddCssCommand($renderer->renderRoot($css_render_array));
    $build['#attached']['library'][] = 'ajax_test/order-header-js-command';
    $build['#attached']['library'][] = 'ajax_test/order-footer-js-command';
    $assets = AttachedAssets::createFromRenderArray($build);
    list($js_assets_header, $js_assets_footer) = $asset_resolver->getJsAssets($assets, FALSE);
    $js_header_render_array = $js_collection_renderer->render($js_assets_header);
    $js_footer_render_array = $js_collection_renderer->render($js_assets_footer);
    $expected_commands[2] = new PrependCommand('head', $js_header_render_array);
    $expected_commands[3] = new AppendCommand('body', $js_footer_render_array);
    $expected_commands[4] = new HtmlCommand('body', 'Hello, world!');

    // Load any page with at least one CSS file, at least one JavaScript file
    // and at least one #ajax-powered element. The latter is an assumption of
    // drupalPostAjaxForm(), the two former are assumptions of the Ajax
    // renderer.
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
    $commands = $this->drupalGetAjax('ajax-test/render-error', array('query' => $edit));
    $expected = new AlertCommand($edit['message']);
    $this->assertCommand($commands, $expected->render(), 'Custom error message is output.');
  }

  /**
   * Tests that new JavaScript and CSS files are lazy-loaded on an AJAX request.
   */
  public function testLazyLoad() {
    $asset_resolver = \Drupal::service('asset.resolver');
    $css_collection_renderer = \Drupal::service('asset.css.collection_renderer');
    $js_collection_renderer = \Drupal::service('asset.js.collection_renderer');
    $renderer = \Drupal::service('renderer');

    $expected = array(
      'setting_name' => 'ajax_forms_test_lazy_load_form_submit',
      'setting_value' => 'executed',
      'library_1' => 'system/admin',
      'library_2' => 'system/drupal.system',
    );

    // Get the base page.
    $this->drupalGet('ajax_forms_test_lazy_load_form');
    $original_settings = $this->getDrupalSettings();
    $original_libraries = explode(',', $original_settings['ajaxPageState']['libraries']);

    // Verify that the base page doesn't have the settings and files that are to
    // be lazy loaded as part of the next requests.
    $this->assertTrue(!isset($original_settings[$expected['setting_name']]), format_string('Page originally lacks the %setting, as expected.', array('%setting' => $expected['setting_name'])));
    $this->assertTrue(!in_array($expected['library_1'], $original_libraries), format_string('Page originally lacks the %library library, as expected.', array('%library' => $expected['library_1'])));
    $this->assertTrue(!in_array($expected['library_2'], $original_libraries), format_string('Page originally lacks the %library library, as expected.', array('%library' => $expected['library_2'])));

    // Calculate the expected CSS and JS.
    $assets = new AttachedAssets();
    $assets->setLibraries([$expected['library_1']])
      ->setAlreadyLoadedLibraries($original_libraries);
    $css_render_array = $css_collection_renderer->render($asset_resolver->getCssAssets($assets, FALSE));
    $expected_css_html = $renderer->renderRoot($css_render_array);

    $assets->setLibraries([$expected['library_2']])
      ->setAlreadyLoadedLibraries($original_libraries);
    $js_assets = $asset_resolver->getJsAssets($assets, FALSE)[1];
    unset($js_assets['drupalSettings']);
    $js_render_array = $js_collection_renderer->render($js_assets);
    $expected_js_html = $renderer->renderRoot($js_render_array);

    // Submit the AJAX request without triggering files getting added.
    $commands = $this->drupalPostAjaxForm(NULL, array('add_files' => FALSE), array('op' => t('Submit')));
    $new_settings = $this->getDrupalSettings();
    $new_libraries = explode(',', $new_settings['ajaxPageState']['libraries']);

    // Verify the setting was not added when not expected.
    $this->assertTrue(!isset($new_settings[$expected['setting_name']]), format_string('Page still lacks the %setting, as expected.', array('%setting' => $expected['setting_name'])));
    $this->assertTrue(!in_array($expected['library_1'], $new_libraries), format_string('Page still lacks the %library library, as expected.', array('%library' => $expected['library_1'])));
    $this->assertTrue(!in_array($expected['library_2'], $new_libraries), format_string('Page still lacks the %library library, as expected.', array('%library' => $expected['library_2'])));
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
    $this->assertFalse($found_settings_command, format_string('Page state still lacks the %library_1 and %library_2 libraries, as expected.', array('%library_1' => $expected['library_1'], '%library_2' => $expected['library_2'])));
    $this->assertFalse($found_markup_command, format_string('Page still lacks the %library_1 and %library_2 libraries, as expected.', array('%library_1' => $expected['library_1'], '%library_2' => $expected['library_2'])));

    // Submit the AJAX request and trigger adding files.
    $commands = $this->drupalPostAjaxForm(NULL, array('add_files' => TRUE), array('op' => t('Submit')));
    $new_settings = $this->getDrupalSettings();
    $new_libraries = explode(',', $new_settings['ajaxPageState']['libraries']);

    // Verify the expected setting was added, both to drupalSettings, and as
    // the first AJAX command.
    $this->assertIdentical($new_settings[$expected['setting_name']], $expected['setting_value'], format_string('Page now has the %setting.', array('%setting' => $expected['setting_name'])));
    $expected_command = new SettingsCommand(array($expected['setting_name'] => $expected['setting_value']), TRUE);
    $this->assertCommand(array_slice($commands, 0, 1), $expected_command->render(), 'The settings command was first.');

    // Verify the expected CSS file was added, both to drupalSettings, and as
    // the second AJAX command for inclusion into the HTML.
    $this->assertTrue(in_array($expected['library_1'], $new_libraries), format_string('Page state now has the %library library.', array('%library' => $expected['library_1'])));
    $this->assertCommand(array_slice($commands, 1, 1), array('data' => $expected_css_html), format_string('Page now has the %library library.', array('%library' => $expected['library_1'])));

    // Verify the expected JS file was added, both to drupalSettings, and as
    // the third AJAX command for inclusion into the HTML. By testing for an
    // exact HTML string containing the SCRIPT tag, we also ensure that
    // unexpected JavaScript code, such as a jQuery.extend() that would
    // potentially clobber rather than properly merge settings, didn't
    // accidentally get added.
    $this->assertTrue(in_array($expected['library_2'], $new_libraries), format_string('Page state now has the %library library.', array('%library' => $expected['library_2'])));
    $this->assertCommand(array_slice($commands, 2, 1), array('data' => $expected_js_html), format_string('Page now has the %library library.', array('%library' => $expected['library_2'])));
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
    $this->config('system.theme')
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
