<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;

/**
 * Tests ckeditor height respects field rows config.
 *
 * @group ckeditor5
 * @internal
 */
class CKEditor5HeightTest extends CKEditor5TestBase {

  use CKEditor5TestTrait;

  /**
   * Tests editor height respects rows config.
   */
  public function testCKEditor5Height(): void {
    $this->addNewTextFormat();
    /** @var \Drupal\editor\Entity\Editor $editor */
    $editor = Editor::load('ckeditor5');
    $editor->setSettings([
      'toolbar' => [
        'items' => [
          'sourceEditing',
        ],
      ],
      'plugins' => [
        'ckeditor5_sourceEditing' => [
          'allowed_tags' => [],
        ],
      ],
    ])->save();
    $this->drupalGet('/node/add/page');
    $this->waitForEditor();

    // We expect height to be 320, but test to ensure that it's greater
    // than 300. We want to ensure that we don't hard code a very specific
    // value because tests might break if styles change (line-height, etc).
    // Note that the default height for CKEditor5 is 47px.
    $this->assertGreaterThan(300, $this->getEditorHeight());
    // Check source editing height.
    $this->pressEditorButton('Source');
    $assert = $this->assertSession();
    $this->assertNotNull($assert->waitForElementVisible('css', '.ck-source-editing-area'));
    $this->assertGreaterThan(300, $this->getEditorHeight(TRUE));

    // Test the max height of the editor is less that the window height.
    $body = \str_repeat('<p>Llamas are cute.</p>', 100);
    $node = $this->drupalCreateNode([
      'body' => $body,
    ]);
    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertLessThan($this->getWindowHeight(), $this->getEditorHeight());

    // Check source editing has a scroll bar.
    $this->pressEditorButton('Source');
    $this->assertNotNull($assert->waitForElementVisible('css', '.ck-source-editing-area'));
    $this->assertTrue($this->isSourceEditingScrollable());

    // Double the editor row count.
    \Drupal::service('entity_display.repository')->getFormDisplay('node', 'page')
      ->setComponent('body', [
        'type' => 'text_textarea_with_summary',
        'settings' => [
          'rows' => 18,
        ],
      ])
      ->save();
    // Check the height of the editor again.
    $this->drupalGet('/node/add/page');
    $this->waitForEditor();
    // We expect height to be 640, but test to ensure that it's greater
    // than 600. We want to ensure that we don't hard code a very specific
    // value because tests might break if styles change (line-height, etc).
    // Note that the default height for CKEditor5 is 47px.
    $this->assertGreaterThan(600, $this->getEditorHeight());
  }

  /**
   * Gets the height of ckeditor.
   */
  private function getEditorHeight(bool $sourceEditing = FALSE): int {
    $selector = $sourceEditing ? '.ck-source-editing-area' : '.ck-editor__editable';
    $javascript = <<<JS
      return document.querySelector('$selector').clientHeight;
    JS;
    return $this->getSession()->evaluateScript($javascript);
  }

  /**
   * Gets the window height.
   */
  private function getWindowHeight(): int {
    $javascript = <<<JS
      return window.innerHeight;
    JS;
    return $this->getSession()->evaluateScript($javascript);
  }

  /**
   * Checks that the source editing element is scrollable.
   */
  private function isSourceEditingScrollable(): bool {
    $javascript = <<<JS
      (function () {
        const element = document.querySelector('.ck-source-editing-area textarea');
        const style = window.getComputedStyle(element);
        if (
          element.scrollHeight > element.clientHeight &&
          style.overflow !== 'hidden' &&
          style['overflow-y'] !== 'hidden' &&
          style.overflow !== 'clip' &&
          style['overflow-y'] !== 'clip'
        ) {
          if (
            element === document.scrollingElement ||
            (style.overflow !== 'visible' &&
              style['overflow-y'] !== 'visible')
          ) {
            return true;
          }
        }

        return false;
      })();
    JS;
    $evaluateScript = $this->getSession()->evaluateScript($javascript);
    return $evaluateScript;
  }

}
