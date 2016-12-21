<?php

namespace Drupal\Tests\ckeditor\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests delivery of CSS to CKEditor via AJAX.
 *
 * @group ckeditor
 */
class AjaxCssTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ckeditor', 'ckeditor_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
    $assert = $this->assertSession();

    $style_color = 'rgb(255, 0, 0)';

    // Add the inline CSS and assert that the style is applied to the main body,
    // but not the iframe.
    $session->getPage()->pressButton('Add CSS to inline CKEditor instance');
    $assert->assertWaitOnAjaxRequest();
    $this->assertEquals($style_color, $this->getEditorStyle('edit-inline', 'color'));
    $this->assertNotEquals($style_color, $this->getEditorStyle('edit-iframe-value', 'color'));

    $session->reload();

    // Add the iframe CSS and assert that the style is applied to the iframe,
    // but not the main body.
    $session->getPage()->pressButton('Add CSS to iframe CKEditor instance');
    $assert->assertWaitOnAjaxRequest();
    $this->assertNotEquals($style_color, $this->getEditorStyle('edit-inline', 'color'));
    $this->assertEquals($style_color, $this->getEditorStyle('edit-iframe-value', 'color'));
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

}
