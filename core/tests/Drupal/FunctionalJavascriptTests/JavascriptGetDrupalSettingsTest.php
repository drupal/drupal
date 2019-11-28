<?php

namespace Drupal\FunctionalJavascriptTests;

/**
 * Tests Drupal settings retrieval in WebDriverTestBase tests.
 *
 * @group javascript
 */
class JavascriptGetDrupalSettingsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['test_page_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests retrieval of Drupal settings.
   *
   * @see \Drupal\FunctionalJavascriptTests\WebDriverTestBase::getDrupalSettings()
   */
  public function testGetDrupalSettings() {
    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet('test-page');

    // Check that we can read the JS settings.
    $js_settings = $this->getDrupalSettings();
    $this->assertSame('azAZ09();.,\\\/-_{}', $js_settings['test-setting']);

    // Dynamically change the setting using Javascript.
    $script = <<<EndOfScript
(function () {
  drupalSettings['test-setting'] = 'foo';
})();
EndOfScript;

    $this->getSession()->evaluateScript($script);

    // Check that the setting has been changed.
    $js_settings = $this->getDrupalSettings();
    $this->assertSame('foo', $js_settings['test-setting']);
  }

}
