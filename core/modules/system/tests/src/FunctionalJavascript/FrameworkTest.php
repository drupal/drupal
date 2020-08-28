<?php

namespace Drupal\Tests\system\FunctionalJavascript;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the off-canvas dialog functionality.
 *
 * @group system
 */
class FrameworkTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'ajax_test', 'ajax_forms_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Tests that new JavaScript and CSS files are lazy-loaded on an AJAX request.
   */
  public function testLazyLoad() {
    $expected = [
      'setting_name' => 'ajax_forms_test_lazy_load_form_submit',
      'setting_value' => 'executed',
      'library_1' => 'system/admin',
      'library_2' => 'system/drupal.system',
    ];

    // Get the base page.
    $this->drupalGet('ajax_forms_test_lazy_load_form');
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    $original_settings = $this->getDrupalSettings();
    $original_libraries = explode(',', $original_settings['ajaxPageState']['libraries']);

    // Verify that the base page doesn't have the settings and files that are to
    // be lazy loaded as part of the next requests.
    $this->assertTrue(!isset($original_settings[$expected['setting_name']]), new FormattableMarkup('Page originally lacks the %setting, as expected.', ['%setting' => $expected['setting_name']]));
    $this->assertNotContains($expected['library_1'], $original_libraries, new FormattableMarkup('Page originally lacks the %library library, as expected.', ['%library' => $expected['library_1']]));
    $this->assertNotContains($expected['library_2'], $original_libraries, new FormattableMarkup('Page originally lacks the %library library, as expected.', ['%library' => $expected['library_2']]));

    // Submit the AJAX request without triggering files getting added.
    $page->pressButton('Submit');
    $assert->assertWaitOnAjaxRequest();
    $new_settings = $this->getDrupalSettings();
    $new_libraries = explode(',', $new_settings['ajaxPageState']['libraries']);

    // Verify the setting was not added when not expected.
    $this->assertTrue(!isset($new_settings[$expected['setting_name']]), new FormattableMarkup('Page still lacks the %setting, as expected.', ['%setting' => $expected['setting_name']]));
    $this->assertNotContains($expected['library_1'], $new_libraries, new FormattableMarkup('Page still lacks the %library library, as expected.', ['%library' => $expected['library_1']]));
    $this->assertNotContains($expected['library_2'], $new_libraries, new FormattableMarkup('Page still lacks the %library library, as expected.', ['%library' => $expected['library_2']]));

    // Submit the AJAX request and trigger adding files.
    $page->checkField('add_files');
    $page->pressButton('Submit');
    $assert->assertWaitOnAjaxRequest();
    $new_settings = $this->getDrupalSettings();
    $new_libraries = explode(',', $new_settings['ajaxPageState']['libraries']);

    // Verify the expected setting was added, both to drupalSettings, and as
    // the first AJAX command.
    $this->assertIdentical($new_settings[$expected['setting_name']], $expected['setting_value'], new FormattableMarkup('Page now has the %setting.', ['%setting' => $expected['setting_name']]));

    // Verify the expected CSS file was added, both to drupalSettings, and as
    // the second AJAX command for inclusion into the HTML.
    $this->assertContains($expected['library_1'], $new_libraries, new FormattableMarkup('Page state now has the %library library.', ['%library' => $expected['library_1']]));

    // Verify the expected JS file was added, both to drupalSettings, and as
    // the third AJAX command for inclusion into the HTML. By testing for an
    // exact HTML string containing the SCRIPT tag, we also ensure that
    // unexpected JavaScript code, such as a jQuery.extend() that would
    // potentially clobber rather than properly merge settings, didn't
    // accidentally get added.
    $this->assertContains($expected['library_2'], $new_libraries, new FormattableMarkup('Page state now has the %library library.', ['%library' => $expected['library_2']]));
  }

  /**
   * Tests that drupalSettings.currentPath is not updated on AJAX requests.
   */
  public function testCurrentPathChange() {
    $this->drupalGet('ajax_forms_test_lazy_load_form');
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    $old_settings = $this->getDrupalSettings();
    $page->pressButton('Submit');
    $assert->assertWaitOnAjaxRequest();
    $new_settings = $this->getDrupalSettings();
    $this->assertEquals($old_settings['path']['currentPath'], $new_settings['path']['currentPath']);
  }

  /**
   * Tests that overridden CSS files are not added during lazy load.
   */
  public function testLazyLoadOverriddenCSS() {
    // The test theme overrides js.module.css without an implementation,
    // thereby removing it.
    \Drupal::service('theme_installer')->install(['test_theme']);
    $this->config('system.theme')
      ->set('default', 'test_theme')
      ->save();

    // This gets the form, and does an Ajax submission on it.
    $this->drupalGet('ajax_forms_test_lazy_load_form');
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    $page->checkField('add_files');
    $page->pressButton('Submit');
    $assert->assertWaitOnAjaxRequest();

    // Verify that the resulting HTML does not load the overridden CSS file.
    // We add a "?" to the assertion, because drupalSettings may include
    // information about the file; we only really care about whether it appears
    // in a LINK or STYLE tag, for which Drupal always adds a query string for
    // cache control.
    $assert->responseNotContains('js.module.css?');
  }

}
