<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\user\RoleInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Tests for CKEditor 5 dialog behavior.
 *
 * @internal
 */
#[Group('ckeditor5')]
#[RunTestsInSeparateProcesses]
class CKEditor5DialogTest extends CKEditor5TestBase {

  use CKEditor5TestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ckeditor5',
    'ckeditor5_test',
  ];

  /**
   * Tests if CKEditor 5 tooltips can be interacted with in dialogs.
   */
  public function testCKEditor5FocusInTooltipsInDialog(): void {
    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'CKEditor 5 with link',
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ])->save();
    Editor::create([
      'format' => 'test_format',
      'editor' => 'ckeditor5',
      'image_upload' => [
        'status' => FALSE,
      ],
      'settings' => [
        'toolbar' => [
          'items' => ['link'],
        ],
      ],
    ])->save();

    $this->assertSame([], array_map(
      function (ConstraintViolationInterface $v): string {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/ckeditor5_test/dialog');
    $page->clickLink('Add Node');
    $assert_session->waitForElementVisible('css', '[role="dialog"]');
    $assert_session->assertWaitOnAjaxRequest();

    $content_area = $assert_session->waitForElementVisible('css', '.ck-editor__editable');
    // Focus the editable area first.
    $content_area->click();
    // Then press the button to add a link.
    $this->pressEditorButton('Link');

    $link_url = '/ckeditor5_test/dialog';
    $input = $assert_session->waitForElementVisible('css', '.ck-balloon-panel input.ck-input-text');
    // Make sure the input field can have focus and we can type into it.
    $input->setValue($link_url);
    // Save the new link.
    $page->find('xpath', "//button[span[text()='Insert']]")->click();
    // Make sure something was added to the text.
    $this->assertNotEmpty($content_area->getText());
  }

  /**
   * Tests that openDialog() applies defaults only when settings are undefined.
   */
  public function testOpenDialogSettings(): void {
    FilterFormat::create([
      'format' => 'ckeditor5',
      'name' => 'CKEditor 5',
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ])->save();
    Editor::create([
      'format' => 'ckeditor5',
      'editor' => 'ckeditor5',
      'image_upload' => ['status' => FALSE],
      'settings' => [
        'toolbar' => ['items' => ['link']],
      ],
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolationInterface $v): string {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('ckeditor5'),
        FilterFormat::load('ckeditor5')
      ))
    ));

    $this->drupalGet('/node/add/page');
    $this->waitForEditor();

    // When neither width nor autoResize is set, defaults should be applied.
    $result = $this->getSession()->evaluateScript(<<<JS
      (function() {
        let captured = null;
        const originalAjax = Drupal.ajax;
        Drupal.ajax = function(settings) { captured = settings; return { execute: function() {} }; };
        Drupal.ckeditor5.openDialog('/test', function() {}, {});
        Drupal.ajax = originalAjax;
        return { width: captured.dialog.width, autoResizeType: typeof captured.dialog.autoResize };
      })()
    JS);
    $this->assertSame('auto', $result['width']);
    $this->assertSame('boolean', $result['autoResizeType']);

    // A pre-defined width must not be overwritten by the 'auto' default.
    $result = $this->getSession()->evaluateScript(<<<JS
      (function() {
        let captured = null;
        const originalAjax = Drupal.ajax;
        Drupal.ajax = function(settings) { captured = settings; return { execute: function() {} }; };
        Drupal.ckeditor5.openDialog('/test', function() {}, { width: '500px' });
        Drupal.ajax = originalAjax;
        return captured.dialog.width;
      })()
    JS);
    $this->assertSame('500px', $result);

    // A pre-defined autoResize: false must not be overwritten.
    $result = $this->getSession()->evaluateScript(<<<JS
      (function() {
        let captured = null;
        const originalAjax = Drupal.ajax;
        Drupal.ajax = function(settings) { captured = settings; return { execute: function() {} }; };
        Drupal.ckeditor5.openDialog('/test', function() {}, { autoResize: false });
        Drupal.ajax = originalAjax;
        return captured.dialog.autoResize;
      })()
    JS);
    $this->assertFalse($result);

    // A pre-defined autoResize: true must not be overwritten.
    $result = $this->getSession()->evaluateScript(<<<JS
      (function() {
        let captured = null;
        const originalAjax = Drupal.ajax;
        Drupal.ajax = function(settings) { captured = settings; return { execute: function() {} }; };
        Drupal.ckeditor5.openDialog('/test', function() {}, { autoResize: true });
        Drupal.ajax = originalAjax;
        return captured.dialog.autoResize;
      })()
    JS);
    $this->assertTrue($result);

    // The ui-dialog--narrow class must always be appended, even alongside
    // custom existing classes.
    $result = $this->getSession()->evaluateScript(<<<JS
      (function() {
        let captured = null;
        const originalAjax = Drupal.ajax;
        Drupal.ajax = function(settings) { captured = settings; return { execute: function() {} }; };
        Drupal.ckeditor5.openDialog('/test', function() {}, { classes: { 'ui-dialog': 'my-custom-class' } });
        Drupal.ajax = originalAjax;
        return captured.dialog.classes['ui-dialog'];
      })()
    JS);
    $this->assertStringContainsString('ui-dialog--narrow', $result);
    $this->assertStringContainsString('my-custom-class', $result);
  }

}
