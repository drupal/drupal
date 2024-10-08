<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Media
 * @group ckeditor5
 * @internal
 */
class MediaLinkabilityTest extends MediaTestBase {

  /**
   * Ensures arbitrary attributes can be added on links wrapping media via GHS.
   *
   * @dataProvider providerLinkability
   */
  public function testLinkedMediaArbitraryHtml(bool $unrestricted): void {
    $assert_session = $this->assertSession();

    $editor = Editor::load('test_format');
    $settings = $editor->getSettings();
    $filter_format = $editor->getFilterFormat();
    if ($unrestricted) {
      $filter_format
        ->setFilterConfig('filter_html', ['status' => FALSE]);
    }
    else {
      // Allow the data-foo attribute in <a> via GHS. Also, add support for div's
      // with data-foo attribute to ensure that linked drupal-media elements can
      // be wrapped with <div>.
      $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags'] = ['<a data-foo>', '<div data-bar>'];
      $editor->setSettings($settings);
      $filter_format->setFilterConfig('filter_html', [
        'status' => TRUE,
        'settings' => [
          'allowed_html' => '<p> <br> <strong> <em> <a href data-foo> <drupal-media data-entity-type data-entity-uuid data-align data-caption alt data-view-mode> <div data-bar>',
        ],
      ]);
    }
    $editor->save();
    $filter_format->save();
    $this->assertSame([], array_map(
      function (ConstraintViolationInterface $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));

    // Wrap the existing drupal-media tag with a div and an a that include
    // attributes allowed via GHS.
    $original_value = $this->host->body->value;
    $this->host->body->value = '<div data-bar="baz"><a href="https://example.com" data-foo="bar">' . $original_value . '</a></div>';
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));

    // Confirm data-foo is present in the editing view.
    $this->assertNotEmpty($link = $assert_session->waitForElementVisible('css', 'a[href="https://example.com"]'));
    $this->assertEquals('bar', $link->getAttribute('data-foo'));

    // Confirm that the media is wrapped by the div on the editing view.
    $assert_session->elementExists('css', 'div[data-bar="baz"] > .drupal-media > a[href="https://example.com"] > div[data-drupal-media-preview]');

    // Confirm that drupal-media is wrapped by the div and a, and that GHS has
    // retained arbitrary HTML allowed by source editing.
    $editor_dom = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($editor_dom->query('//div[@data-bar="baz"]/a[@data-foo="bar"]/drupal-media'));
  }

  /**
   * Tests linkability of the media CKEditor widget.
   *
   * Due to the very different HTML markup generated for the editing view and
   * the data view, this is explicitly testing the "editingDowncast" and
   * "dataDowncast" results. These are CKEditor 5 concepts.
   *
   * @see https://ckeditor.com/docs/ckeditor5/latest/framework/guides/architecture/editing-engine.html#conversion
   *
   * @dataProvider providerLinkability
   */
  public function testLinkability(bool $unrestricted): void {
    // Disable filter_html.
    if ($unrestricted) {
      FilterFormat::load('test_format')
        ->setFilterConfig('filter_html', ['status' => FALSE])
        ->save();
    }

    $page = $this->getSession()->getPage();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();

    // Initial state: the Drupal Media CKEditor Widget is not selected.
    $drupalmedia = $assert_session->waitForElementVisible('css', '.ck-content .ck-widget.drupal-media');
    $this->assertNotEmpty($drupalmedia);
    $this->assertFalse($drupalmedia->hasClass('.ck-widget_selected'));

    // Assert the "editingDowncast" HTML before making changes.
    $assert_session->elementExists('css', '.ck-content .ck-widget.drupal-media > [data-drupal-media-preview]');

    // Assert the "dataDowncast" HTML before making changes.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query('//drupal-media'));
    $this->assertEmpty($xpath->query('//a'));

    // Assert the link button is present and not pressed.
    $link_button = $this->getEditorButton('Link');
    $this->assertSame('false', $link_button->getAttribute('aria-pressed'));

    // Wait for the preview to load.
    $preview = $assert_session->waitForElement('css', '.ck-content .ck-widget.drupal-media [data-drupal-media-preview="ready"]');
    $this->assertNotEmpty($preview);

    // Tests linking Drupal media.
    $drupalmedia->click();
    $this->assertTrue($drupalmedia->hasClass('ck-widget_selected'));
    $this->assertEditorButtonEnabled('Link');
    // Assert structure of image toolbar balloon.
    $this->assertVisibleBalloon('.ck-toolbar[aria-label="Drupal Media toolbar"]');
    $link_media_button = $this->getBalloonButton('Link media');
    // Click the "Link media" button.
    $this->assertSame('false', $link_media_button->getAttribute('aria-pressed'));
    $link_media_button->press();
    // Assert structure of link form balloon.
    $balloon = $this->assertVisibleBalloon('.ck-link-form');
    $url_input = $balloon->find('css', '.ck-labeled-field-view__input-wrapper .ck-input-text');
    // Fill in link form balloon's <input> and hit "Save".
    $url_input->setValue('http://linking-embedded-media.com');
    $balloon->pressButton('Save');

    // Assert the "editingDowncast" HTML after making changes. Assert the link
    // exists, then assert the link exists. Then assert the expected DOM
    // structure in detail.
    $assert_session->elementExists('css', '.ck-content a[href="http://linking-embedded-media.com"]');
    $assert_session->elementExists('css', '.ck-content .drupal-media.ck-widget > a[href="http://linking-embedded-media.com"] > div[aria-label] > article > div > img[src*="image-test.png"]');

    // Assert the "dataDowncast" HTML after making changes.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query('//drupal-media'));
    $this->assertNotEmpty($xpath->query('//a[@href="http://linking-embedded-media.com"]'));
    $this->assertNotEmpty($xpath->query('//a[@href="http://linking-embedded-media.com"]/drupal-media'));
    // Ensure that the media caption is retained and not linked as a result of
    // linking media.
    $this->assertNotEmpty($xpath->query('//a[@href="http://linking-embedded-media.com"]/drupal-media[@data-caption="baz"]'));

    // Add `class="trusted"` to the link.
    $this->assertEmpty($xpath->query('//a[@href="http://linking-embedded-media.com" and @class="trusted"]'));
    $this->pressEditorButton('Source');
    $source_text_area = $assert_session->waitForElement('css', '.ck-source-editing-area textarea');
    $this->assertNotEmpty($source_text_area);
    $new_value = str_replace('<a ', '<a class="trusted" ', $source_text_area->getValue());
    $source_text_area->setValue('<p>temp</p>');
    $source_text_area->setValue($new_value);
    $this->pressEditorButton('Source');

    // When unrestricted, additional attributes on links should be retained.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertCount($unrestricted ? 1 : 0, $xpath->query('//a[@href="http://linking-embedded-media.com" and @class="trusted"]'));

    // Save the entity whose text field is being edited.
    $page->pressButton('Save');

    // Assert the HTML the end user sees.
    $assert_session->elementExists('css', $unrestricted
      ? 'a[href="http://linking-embedded-media.com"].trusted img[src*="image-test.png"]'
      : 'a[href="http://linking-embedded-media.com"] img[src*="image-test.png"]');

    // Go back to edit the now *linked* <drupal-media>. Everything from this
    // point onwards is effectively testing "upcasting" and proving there is no
    // data loss.
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();

    // Assert the "dataDowncast" HTML before making changes.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query('//drupal-media'));
    $this->assertNotEmpty($xpath->query('//a[@href="http://linking-embedded-media.com"]'));
    $this->assertNotEmpty($xpath->query('//a[@href="http://linking-embedded-media.com"]/drupal-media'));

    // Tests unlinking media.
    $drupalmedia->click();
    $this->assertEditorButtonEnabled('Link');
    $this->assertSame('true', $this->getEditorButton('Link')->getAttribute('aria-pressed'));
    // Assert structure of Drupal media toolbar balloon.
    $this->assertVisibleBalloon('.ck-toolbar[aria-label="Drupal Media toolbar"]');
    $link_media_button = $this->getBalloonButton('Link media');
    $this->assertSame('true', $link_media_button->getAttribute('aria-pressed'));
    $link_media_button->click();
    // Assert structure of link actions balloon.
    $this->getBalloonButton('Edit link');
    $unlink_image_button = $this->getBalloonButton('Unlink');
    // Click the "Unlink" button.
    $unlink_image_button->click();
    $this->assertSame('false', $this->getEditorButton('Link')->getAttribute('aria-pressed'));

    // Assert the "editingDowncast" HTML after making changes. Assert the link
    // exists, then assert no link exists. Then assert the expected DOM
    // structure in detail.
    $assert_session->elementNotExists('css', '.ck-content a');
    $assert_session->elementExists('css', '.ck-content .drupal-media.ck-widget > div[aria-label] > article > div > img[src*="image-test.png"]');

    // Ensure that figcaption exists.
    // @see https://www.drupal.org/project/drupal/issues/3268318
    $assert_session->elementExists('css', '.ck-content .drupal-media.ck-widget > figcaption');

    // Assert the "dataDowncast" HTML after making changes.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query('//drupal-media'));
    $this->assertEmpty($xpath->query('//a'));
  }

  public static function providerLinkability(): array {
    return [
      'restricted' => [FALSE],
      'unrestricted' => [TRUE],
    ];
  }

  /**
   * Ensure that manual link decorators work with linkable media.
   *
   * @dataProvider providerLinkability
   */
  public function testLinkManualDecorator(bool $unrestricted): void {
    \Drupal::service('module_installer')->install(['ckeditor5_manual_decorator_test']);
    $this->resetAll();

    $decorator = 'Open in a new tab';
    $decorator_attributes = '[@target="_blank"][@rel="noopener noreferrer"][@class="link-new-tab"]';

    // Disable filter_html.
    if ($unrestricted) {
      FilterFormat::load('test_format')
        ->setFilterConfig('filter_html', ['status' => FALSE])
        ->save();
      $decorator = 'Pink color';
      $decorator_attributes = '[@style="color:pink;"]';
    }

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->assertNotEmpty($drupalmedia = $assert_session->waitForElementVisible('css', '.ck-content .ck-widget.drupal-media'));
    $drupalmedia->click();
    $this->assertVisibleBalloon('.ck-toolbar[aria-label="Drupal Media toolbar"]');

    // Turn off caption, so we don't accidentally put our link in that text
    // field instead of on the actual media.
    $this->getBalloonButton('Toggle caption off')->click();
    $assert_session->assertNoElementAfterWait('css', 'figure.drupal-media > figcaption');

    $this->assertVisibleBalloon('.ck-toolbar[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('Link media')->click();

    $balloon = $this->assertVisibleBalloon('.ck-link-form');
    $url_input = $balloon->find('css', '.ck-labeled-field-view__input-wrapper .ck-input-text');
    $url_input->setValue('http://linking-embedded-media.com');
    $this->getBalloonButton($decorator)->click();
    $balloon->pressButton('Save');

    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.drupal-media a'));
    $this->assertVisibleBalloon('.ck-link-actions');

    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query("//a[@href='http://linking-embedded-media.com']$decorator_attributes"));
    $this->assertNotEmpty($xpath->query("//a[@href='http://linking-embedded-media.com']$decorator_attributes/drupal-media"));

    // Ensure that manual decorators upcast correctly.
    $page->pressButton('Save');
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->assertNotEmpty($drupalmedia = $assert_session->waitForElementVisible('css', '.ck-content .ck-widget.drupal-media'));
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query("//a[@href='http://linking-embedded-media.com']$decorator_attributes"));
    $this->assertNotEmpty($xpath->query("//a[@href='http://linking-embedded-media.com']$decorator_attributes/drupal-media"));

    // Finally, ensure that media can be unlinked.
    $drupalmedia->click();
    $this->assertVisibleBalloon('.ck-toolbar[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('Link media')->click();
    $this->assertVisibleBalloon('.ck-link-actions');
    $this->getBalloonButton('Unlink')->click();

    $this->assertTrue($assert_session->waitForElementRemoved('css', '.drupal-media a'));
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertEmpty($xpath->query('//a'));
    $this->assertNotEmpty($xpath->query('//drupal-media'));
  }

}
