<?php

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

// cspell:ignore sourceediting

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Style
 * @group ckeditor5
 * @internal
 */
class StyleTest extends CKEditor5TestBase {

  use CKEditor5TestTrait;

  /**
   * @covers \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Style::buildConfigurationForm
   */
  public function testStyleSettingsForm() {
    $this->drupalLogin($this->drupalCreateUser(['administer filters']));

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->createNewTextFormat($page, $assert_session);
    $assert_session->assertWaitOnAjaxRequest();

    // The Style plugin settings form should not be present.
    $assert_session->elementNotExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-style"]');

    $this->assertNotEmpty($assert_session->waitForElement('css', '.ckeditor5-toolbar-item-style'));
    $this->triggerKeyUp('.ckeditor5-toolbar-item-style', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();

    // No validation error upon enabling the Style plugin.
    $this->assertNoRealtimeValidationErrors();
    $assert_session->pageTextContains('No styles configured');

    // Still no validation error when configuring other functionality first.
    $this->triggerKeyUp('.ckeditor5-toolbar-item-undo', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNoRealtimeValidationErrors();

    // The Style plugin settings form should now be present and should have no
    // styles configured.
    $page->clickLink('Style');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-style-styles"]'));

    $javascript = <<<JS
      const allowedTags = document.querySelector('[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-style-styles"]');
      allowedTags.value = 'p.foo.bar  | Foobar paragraph';
      allowedTags.dispatchEvent(new Event('input'));
JS;
    $this->getSession()->executeScript($javascript);

    // Immediately save the configuration. Intentionally do nothing that would
    // trigger an AJAX rebuild.
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('Added text format');

    // Verify that the configuration was saved.
    $this->drupalGet('admin/config/content/formats/manage/ckeditor5');
    $page->clickLink('Style');
    $this->assertNotNull($styles_textarea = $assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-style-styles"]'));

    $this->assertSame("p.foo.bar|Foobar paragraph\n", $styles_textarea->getValue());
    $assert_session->pageTextContains('One style configured');
    $allowed_html_field = $assert_session->fieldExists('filters[filter_html][settings][allowed_html]');
    $this->assertStringContainsString('<p class="foo bar">', $allowed_html_field->getValue());

    // Attempt to use an unsupported HTML5 tag.
    $javascript = <<<JS
      const allowedTags = document.querySelector('[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-style-styles"]');
      allowedTags.value = 's.redacted|Redacted';
      allowedTags.dispatchEvent(new Event('change'));
JS;
    $this->getSession()->executeScript($javascript);

    // The CKEditor 5 module should refuse to specify styles on tags that cannot
    // (yet) be created.
    // @see \Drupal\ckeditor5\Plugin\Validation\Constraint\FundamentalCompatibilityConstraintValidator::checkAllHtmlTagsAreCreatable()
    $assert_session->waitForElement('css', '[role=alert][data-drupal-message-type="error"]:contains("The Style plugin needs another plugin to create <s>, for it to be able to create the following attributes: <s class="redacted">. Enable a plugin that supports creating this tag. If none exists, you can configure the Source Editing plugin to support it.")');
    // The entire vertical tab for "Style" settings should be marked up as the
    // cause of the error, which means the "Styles" text area in there is marked
    // too.
    $assert_session->elementExists('css', '.vertical-tabs__pane[data-ckeditor5-plugin-id="ckeditor5_style"][aria-invalid="true"]');
    $assert_session->elementExists('css', '.vertical-tabs__pane[data-ckeditor5-plugin-id="ckeditor5_style"] textarea[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-style-styles"][aria-invalid="true"]');

    // Attempt to save anyway: the warning should become an error.
    $page->pressButton('Save configuration');
    $assert_session->pageTextNotContains('Added text format');
    $assert_session->elementExists('css', '[aria-label="Error message"]:contains("The Style plugin needs another plugin to create <s>, for it to be able to create the following attributes: <s class="redacted">. Enable a plugin that supports creating this tag. If none exists, you can configure the Source Editing plugin to support it.")');

    // Now, attempt to use a supported non-HTML5 tag.
    // @see \Drupal\ckeditor5\Plugin\Validation\Constraint\StyleSensibleElementConstraintValidator
    $javascript = <<<JS
      const allowedTags = document.querySelector('[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-style-styles"]');
      allowedTags.value = 'drupal-media.sensational|Sensational media';
      allowedTags.dispatchEvent(new Event('change'));
JS;
    $this->getSession()->executeScript($javascript);

    // The CKEditor 5 module should refuse to allow styles on non-HTML5 tags.
    $assert_session->waitForElement('css', '[role=alert][data-drupal-message-type="error"]:contains("A style can only be specified for an HTML 5 tag. <drupal-media> is not an HTML5 tag.")');
    // The vertical tab for "Style" settings should not be marked up as the cause
    // of the error, but only the "Styles" text area in the vertical tab.
    $assert_session->elementNotExists('css', '.vertical-tabs__pane[data-ckeditor5-plugin-id="ckeditor5_style"][aria-invalid="true"]');
    $assert_session->elementExists('css', '.vertical-tabs__pane[data-ckeditor5-plugin-id="ckeditor5_style"] textarea[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-style-styles"][aria-invalid="true"]');

    // Test configuration overlaps across plugins.
    $this->drupalGet('admin/config/content/formats/manage/ckeditor5');
    $this->assertNotEmpty($assert_session->elementExists('css', '.ckeditor5-toolbar-item-sourceEditing'));
    $this->triggerKeyUp('.ckeditor5-toolbar-item-sourceEditing', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();
    // The Source Editing plugin settings form should now be present and should
    // have no allowed tags configured.
    $page->clickLink('Source editing');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-sourceediting-allowed-tags"]'));

    // Make `<aside class>` creatable.
    $javascript = <<<JS
      const allowedTags = document.querySelector('[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-sourceediting-allowed-tags"]');
      allowedTags.value = '<aside class>';
      allowedTags.dispatchEvent(new Event('change'));
JS;
    $this->getSession()->executeScript($javascript);
    $assert_session->assertWaitOnAjaxRequest();

    // Create a style with `aside` and a class name.
    $javascript = <<<JS
      const allowedTags = document.querySelector('[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-style-styles"]');
      allowedTags.value = 'aside.error|Aside';
      allowedTags.dispatchEvent(new Event('change'));
JS;
    $this->getSession()->executeScript($javascript);
    $assert_session->assertWaitOnAjaxRequest();

    // The CKEditor 5 module should refuse to create configuration overlaps
    // across plugins.
    // @see \Drupal\ckeditor5\Plugin\Validation\Constraint\StyleSensibleElementConstraintValidator::findStyleConflictingPluginLabel()
    $assert_session->waitForElement('css', '[role=alert][data-drupal-message-type="error"]:contains("A style must only specify classes not supported by other plugins.")');
  }

  /**
   * Tests Style functionality: setting a class, expected style choices.
   */
  public function testStyleFunctionality() {
    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p class="highlighted interesting"> <br> <a href class="reliable"> <blockquote class="famous"> <h2 class="red-heading">',
          ],
        ],
      ],
    ])->save();
    Editor::create([
      'editor' => 'ckeditor5',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'items' => [
            'heading',
            'link',
            'blockQuote',
            'style',
          ],
        ],
        'plugins' => [
          'ckeditor5_heading' => [
            'enabled_headings' => [
              'heading2',
            ],
          ],
          'ckeditor5_style' => [
            'styles' => [
              [
                'label' => 'Highlighted & interesting',
                'element' => '<p class="highlighted interesting">',
              ],
              [
                'label' => 'Red heading',
                'element' => '<h2 class="red-heading">',
              ],
              [
                'label' => 'Reliable source',
                'element' => '<a class="reliable">',
              ],
              [
                'label' => 'Famous',
                'element' => '<blockquote class="famous">',
              ],
            ],
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
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

    // Create a sample entity to test CKEditor 5.
    $node = $this->createNode([
      'type' => 'page',
      'title' => 'A selection of the history of Drupal',
      'body' => [
        'value' => '<h2>Upgrades</h2><p class="history">Drupal has historically been difficult to upgrade from one major version to the next.</p><p class="highlighted interesting">This changed with Drupal 8.</p><blockquote class="famous"><p>Updating from Drupal 8\'s latest version to Drupal 9.0.0 should be as easy as updating between minor versions of Drupal 8.</p></blockquote><p> — <a class="reliable" href="https://dri.es/making-drupal-upgrades-easy-forever">Dries</a></p>',
        'format' => 'test_format',
      ],
    ]);
    $node->save();

    // Observe.
    $this->drupalLogin($this->drupalCreateUser([
      'use text format test_format',
      'bypass node access',
    ]));
    $this->drupalGet($node->toUrl('edit-form'));
    $this->waitForEditor();

    // Select the <h2>, assert that no style is active currently..
    $this->selectTextInsideElement('h2');
    $assert_session = $this->assertSession();
    $style_dropdown = $assert_session->elementExists('css', '.ck-style-dropdown');
    $this->assertSame('Styles', $style_dropdown->getText());

    // Click the dropdown, check the available styles.
    $style_dropdown->click();
    $buttons = $style_dropdown->findAll('css', '.ck-dropdown__panel button');
    $this->assertCount(4, $buttons);
    $this->assertSame('Highlighted & interesting', $buttons[0]->find('css', '.ck-button__label')->getText());
    $this->assertSame('Red heading', $buttons[1]->find('css', '.ck-button__label')->getText());
    $this->assertSame('Famous', $buttons[2]->find('css', '.ck-button__label')->getText());
    $this->assertSame('Reliable source', $buttons[3]->find('css', '.ck-button__label')->getText());
    $this->assertSame('true', $buttons[0]->getAttribute('aria-disabled'));
    $this->assertFalse($buttons[1]->hasAttribute('aria-disabled'));
    $this->assertSame('true', $buttons[2]->getAttribute('aria-disabled'));
    // @todo Uncomment this after https://github.com/ckeditor/ckeditor5/issues/11709 is fixed.
    // $this->assertSame('true', $buttons[3]->getAttribute('aria-disabled'));
    $this->assertTrue($buttons[0]->hasClass('ck-off'));
    $this->assertTrue($buttons[1]->hasClass('ck-off'));
    $this->assertTrue($buttons[2]->hasClass('ck-off'));
    $this->assertTrue($buttons[3]->hasClass('ck-off'));

    // Apply the "Red heading" style and verify it has the expected effect.
    $assert_session->elementExists('css', '.ck-editor__main h2:not(.red-heading)');
    $buttons[1]->click();
    $assert_session->elementExists('css', '.ck-editor__main h2.red-heading');
    $this->assertTrue($buttons[0]->hasClass('ck-off'));
    $this->assertTrue($buttons[1]->hasClass('ck-on'));
    $this->assertTrue($buttons[2]->hasClass('ck-off'));
    $this->assertTrue($buttons[3]->hasClass('ck-off'));
    $this->assertSame('Red heading', $style_dropdown->getText());

    // Select the first paragraph and observe changes in:
    // - styles dropdown label
    // - button states
    $this->selectTextInsideElement('p');
    $this->assertSame('Styles', $style_dropdown->getText());
    $style_dropdown->click();
    $this->assertTrue($buttons[0]->hasClass('ck-off'));
    $this->assertTrue($buttons[1]->hasClass('ck-off'));
    $this->assertTrue($buttons[2]->hasClass('ck-off'));
    $this->assertTrue($buttons[3]->hasClass('ck-off'));
    $this->assertFalse($buttons[0]->hasAttribute('aria-disabled'));
    $this->assertSame('true', $buttons[1]->getAttribute('aria-disabled'));
    $this->assertSame('true', $buttons[2]->getAttribute('aria-disabled'));
    // @todo Uncomment this after https://github.com/ckeditor/ckeditor5/issues/11709 is fixed.
    // $this->assertSame('true', $buttons[3]->getAttribute('aria-disabled'));
    // Close the dropdown.
    $style_dropdown->click();

    // Select the blockquote and observe changes in:
    // - styles dropdown label
    // - button states
    $this->selectTextInsideElement('blockquote');
    $this->assertSame('Famous', $style_dropdown->getText());
    $style_dropdown->click();
    $this->assertTrue($buttons[0]->hasClass('ck-off'));
    $this->assertTrue($buttons[1]->hasClass('ck-off'));
    $this->assertTrue($buttons[2]->hasClass('ck-on'));
    $this->assertTrue($buttons[3]->hasClass('ck-off'));
    $this->assertFalse($buttons[0]->hasAttribute('aria-disabled'));
    $this->assertSame('true', $buttons[1]->getAttribute('aria-disabled'));
    $this->assertFalse($buttons[2]->hasAttribute('aria-disabled'));
    // @todo Uncomment this after https://github.com/ckeditor/ckeditor5/issues/11709 is fixed.
    // $this->assertSame('true', $buttons[3]->getAttribute('aria-disabled'));
    // Close the dropdown.
    $style_dropdown->click();

    // The resulting markup should be identical to the starting markup, with two
    // changes:
    // 1. the `red-heading` class has been added to the `<h2>`
    // 2. the `history` class has been removed from the `<p>`, because CKEditor
    //    5 has not been configured for this: if a Style had configured for it,
    //    it would have been retained.
    $this->assertSame('<h2 class="red-heading">Upgrades</h2><p>Drupal has historically been difficult to upgrade from one major version to the next.</p><p class="highlighted interesting">This changed with Drupal 8.</p><blockquote class="famous"><p>Updating from Drupal 8\'s latest version to Drupal 9.0.0 should be as easy as updating between minor versions of Drupal 8.</p></blockquote><p>— <a class="reliable" href="https://dri.es/making-drupal-upgrades-easy-forever">Dries</a></p>', $this->getEditorDataAsHtmlString());
  }

}
