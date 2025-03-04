<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the form API textarea element.
 *
 * @group Form
 */
class TextareaTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the textarea element #resizable property.
   */
  public function testFormTextareaResizable(): void {
    $this->drupalGet('form-test/textarea');
    $this->assertNotEmpty($this->cssSelect('#edit-textarea-resizable-vertical.resize-vertical'));
    $this->assertNotEmpty($this->cssSelect('#edit-textarea-resizable-horizontal.resize-horizontal'));
    $this->assertNotEmpty($this->cssSelect('#edit-textarea-resizable-both.resize-both'));
    $this->assertNotEmpty($this->cssSelect('#edit-textarea-resizable-none.resize-none'));
  }

}
