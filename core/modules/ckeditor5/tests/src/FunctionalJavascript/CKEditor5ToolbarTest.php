<?php

declare(strict_types = 1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\user\Entity\User;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Tests for CKEditor 5 editor UI with Toolbar module.
 *
 * @group ckeditor5
 * @internal
 */
class CKEditor5ToolbarTest extends CKEditor5TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'toolbar',
  ];

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [],
    ])->save();
    Editor::create([
      'editor' => 'ckeditor5',
      'format' => 'test_format',
      'settings' => [],
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));

    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $this->user = $this->drupalCreateUser([
      'use text format test_format',
      'access toolbar',
      'edit any article content',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Ensures that CKEditor 5 toolbar renders below Drupal Toolbar.
   */
  public function test(): void {
    $assert_session = $this->assertSession();

    // Create test content to ensure that CKEditor 5 text editor can be
    // scrolled.
    $body = '';
    for ($i = 0; $i < 10; $i++) {
      $body .= '<p>' . $this->randomMachineName(32) . '</p>';
    }
    $edit_url = $this->drupalCreateNode(['type' => 'article', 'body' => ['value' => $body, 'format' => 'test_format']])->toUrl('edit-form');
    $this->drupalGet($edit_url);
    $this->assertNotEmpty($assert_session->waitForElement('css', '#toolbar-bar'));
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));

    // Ensure the body has enough height to enable scrolling. Scroll 110px from
    // top of body field to ensure CKEditor 5 toolbar is sticky.
    $this->getSession()->evaluateScript('document.body.style.height = "10000px";');
    $this->getSession()->evaluateScript('location.hash = "#edit-body-0-value";');
    $this->getSession()->evaluateScript('scroll(0, document.documentElement.scrollTop + 110);');
    // Focus CKEditor 5 text editor.
    $javascript = <<<JS
      Drupal.CKEditor5Instances.get(document.getElementById("edit-body-0-value").dataset["ckeditor5Id"]).editing.view.focus();
JS;
    $this->getSession()->evaluateScript($javascript);

    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-sticky-panel__placeholder'));
    $toolbar_height = (int) $this->getSession()->evaluateScript('document.getElementById("toolbar-bar").offsetHeight');
    $ckeditor5_toolbar_position = (int) $this->getSession()->evaluateScript("document.querySelector('.ck-toolbar').getBoundingClientRect().top");
    $this->assertEqualsWithDelta($toolbar_height, $ckeditor5_toolbar_position, 2);
  }

}
