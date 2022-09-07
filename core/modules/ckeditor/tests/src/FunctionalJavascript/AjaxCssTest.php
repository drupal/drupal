<?php

namespace Drupal\Tests\ckeditor\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests delivery of CSS to CKEditor via AJAX.
 *
 * @group ckeditor
 * @group legacy
 */
class AjaxCssTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ckeditor', 'ckeditor_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'test_format',
      'name' => $this->randomMachineName(),
    ])->save();

    Editor::create([
      'editor' => 'ckeditor',
      'format' => 'test_format',
    ])->save();

    user_role_grant_permissions('anonymous', ['use text format test_format']);
  }

  /**
   * Tests adding style sheets dynamically to CKEditor.
   */
  public function testCkeditorAjaxAddCss() {
    $this->drupalGet('/ckeditor_test/ajax_css');

    $session = $this->getSession();
    $page = $session->getPage();

    $this->waitOnCkeditorInstance('edit-iframe-value');
    $this->waitOnCkeditorInstance('edit-inline');

    $style_color = 'rgb(255, 0, 0)';

    // Add the inline CSS and assert that the style is applied to the main body,
    // but not the iframe.
    $page->pressButton('Add CSS to inline CKEditor instance');

    $result = $page->waitFor(10, function () use ($style_color) {
      return ($this->getEditorStyle('edit-inline', 'color') == $style_color)
        && ($this->getEditorStyle('edit-iframe-value', 'color') != $style_color);
    });
    $this->assertTrue($result);

    $session->reload();

    $this->waitOnCkeditorInstance('edit-iframe-value');
    $this->waitOnCkeditorInstance('edit-inline');

    // Add the iframe CSS and assert that the style is applied to the iframe,
    // but not the main body.
    $page->pressButton('Add CSS to iframe CKEditor instance');

    $result = $page->waitFor(10, function () use ($style_color) {
      return ($this->getEditorStyle('edit-inline', 'color') != $style_color)
        && ($this->getEditorStyle('edit-iframe-value', 'color') == $style_color);
    });

    $this->assertTrue($result);
  }

  /**
   * Gets a computed style value for a CKEditor instance.
   *
   * @param string $instance_id
   *   The CKEditor instance ID.
   * @param string $attribute
   *   The style attribute.
   *
   * @return string
   *   The computed style value.
   */
  protected function getEditorStyle($instance_id, $attribute) {
    $js = sprintf(
      'CKEDITOR.instances["%s"].document.getBody().getComputedStyle("%s")',
      $instance_id,
      $attribute
    );
    return $this->getSession()->evaluateScript($js);
  }

  /**
   * Wait for a CKEditor instance to finish loading and initializing.
   *
   * @param string $instance_id
   *   The CKEditor instance ID.
   * @param int $timeout
   *   (optional) Timeout in milliseconds, defaults to 10000.
   */
  protected function waitOnCkeditorInstance($instance_id, $timeout = 10000) {
    $condition = <<<JS
      (function() {
        return (
          typeof CKEDITOR !== 'undefined'
          && typeof CKEDITOR.instances["$instance_id"] !== 'undefined'
          && CKEDITOR.instances["$instance_id"].instanceReady
        );
      }())
JS;

    $this->getSession()->wait($timeout, $condition);
  }

}
