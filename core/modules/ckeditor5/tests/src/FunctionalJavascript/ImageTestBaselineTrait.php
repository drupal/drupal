<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\Component\Utility\Html;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;

// cspell:ignore imageresize

/**
 * Trait with common test methods for image tests.
 */
trait ImageTestBaselineTrait {

  /**
   * Ensures that attributes are retained on conversion.
   */
  public function testAttributeRetentionDuringUpcasting(): void {
    // Run test cases in a single test to make the test run faster.
    $attributes_to_retain = [
      '-none-' => 'inline',
      'data-caption="test caption 🦙"' => 'block',
      'data-align="left"' => 'inline',
    ];

    foreach ($attributes_to_retain as $attribute_to_retain => $expected_upcast_behavior_when_wrapped_in_block_element) {
      if ($attribute_to_retain === '-none-') {
        $attribute_to_retain = '';
      }
      $img_tag = '<img ' . $attribute_to_retain . ' alt="drupalimage test image" ' . $this->imageAttributesAsString() . ' />';
      $test_cases = [
        // Plain image tag for a baseline.
        [
          $img_tag,
          $img_tag,
        ],
        // Image tag wrapped with <p>.
        [
          "<p>$img_tag</p>",
          $expected_upcast_behavior_when_wrapped_in_block_element === 'inline' ? "<p>$img_tag</p>" : $img_tag,
        ],
        // Image tag wrapped with a disallowed paragraph-like element (<div).
        // When inline is the expected upcast behavior, it will wrap in <p>
        // because it still must wrap in a paragraph-like element, and <p> is
        // available to be that element.
        [
          "<div>$img_tag</div>",
          $expected_upcast_behavior_when_wrapped_in_block_element === 'inline' ? "<p>$img_tag</p>" : $img_tag,
        ],
      ];

      foreach ($test_cases as $test_case) {
        [$markup, $expected] = $test_case;
        $this->host->body->value = $markup;
        $this->host->save();

        $this->drupalGet($this->host->toUrl('edit-form'));
        $this->waitForEditor();

        // Ensure that the image is rendered in preview.
        $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', ".ck-content .ck-widget img"));
        $editor_dom = $this->getEditorDataAsDom();
        $expected_dom = Html::load($expected);
        $xpath = new \DOMXPath($this->getEditorDataAsDom());
        $this->assertEquals($expected_dom->getElementsByTagName('body')->item(0)->C14N(), $editor_dom->getElementsByTagName('body')->item(0)->C14N());

        // Ensure the test attribute is persisted on downcast.
        if ($attribute_to_retain) {
          $this->assertNotEmpty($xpath->query("//img[@$attribute_to_retain]"));
        }
      }
    }
  }

  /**
   * Tests that arbitrary attributes are allowed via GHS.
   */
  public function testImageArbitraryHtml(): void {
    $editor = Editor::load('test_format');
    $settings = $editor->getSettings();

    // Allow the data-foo attribute in img via GHS.
    $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags'] = ['<img data-foo>'];
    $editor->setSettings($settings);
    $editor->save();
    $format = FilterFormat::load('test_format');
    $original_config = $format->filters('filter_html')
      ->getConfiguration();

    foreach ($this->providerLinkability() as $data) {
      [$image_type, $unrestricted] = $data;

      $format_config = $unrestricted ? ['status' => FALSE] : $original_config;
      $format->setFilterConfig('filter_html', $format_config)
        ->save();

      // Make the test content have either a block image or an inline image.
      $img_tag = '<img data-foo="bar" alt="drupalimage test image" data-entity-type="file" ' . $this->imageAttributesAsString() . ' />';
      $this->host->body->value .= $image_type === 'block'
        ? $img_tag
        : "<p>$img_tag</p>";
      $this->host->save();

      $expected_widget_selector = $image_type === 'block' ? 'image img' : 'image-inline';

      $this->drupalGet($this->host->toUrl('edit-form'));
      $this->waitForEditor();

      $drupalimage = $this->assertSession()->waitForElementVisible('css', ".ck-content .ck-widget.$expected_widget_selector");
      $this->assertNotEmpty($drupalimage);
      $this->assertEquals('bar', $drupalimage->getAttribute('data-foo'));

      $xpath = new \DOMXPath($this->getEditorDataAsDom());
      $this->assertNotEmpty($xpath->query('//img[@data-foo="bar"]'));
    }
  }

  /**
   * Tests linkability of the image CKEditor widget.
   *
   * Due to the complex overrides that `drupalImage.DrupalImage` is making, this
   * is explicitly testing the "editingDowncast" and "dataDowncast" results.
   * These are CKEditor 5 concepts.
   *
   * @see https://ckeditor.com/docs/ckeditor5/latest/framework/guides/architecture/editing-engine.html#conversion
   */
  public function testLinkability(): void {
    $format = FilterFormat::load('test_format');
    $original_config = $format->filters('filter_html')
      ->getConfiguration();
    $original_body_value = $this->host->body->value;
    foreach ($this->providerLinkability() as $data) {
      [$image_type, $unrestricted] = $data;
      assert($image_type === 'inline' || $image_type === 'block');

      $format_config = $unrestricted ? ['status' => FALSE] : $original_config;

      $format->setFilterConfig('filter_html', $format_config)
        ->save();

      // Make the test content have either a block image or an inline image.
      $img_tag = '<img alt="drupalimage test image" data-entity-type="file" ' . $this->imageAttributesAsString() . ' />';
      $this->host->body->value = $original_body_value . ($image_type === 'block'
        ? $img_tag
        : "<p>$img_tag</p>");
      $this->host->save();

      $this->drupalGet($this->host->toUrl('edit-form'));
      $page = $this->getSession()->getPage();
      // Adjust the expectations accordingly.
      $expected_widget_class = $image_type === 'block' ? 'image' : 'image-inline';

      $this->waitForEditor();
      $assert_session = $this->assertSession();

      // Initial state: the image CKEditor Widget is not selected.
      $drupalimage = $assert_session->waitForElementVisible('css', ".ck-content .ck-widget.$expected_widget_class");
      $this->assertNotEmpty($drupalimage);
      $this->assertFalse($drupalimage->hasClass('.ck-widget_selected'));

      $src = basename($this->imageAttributes()['src']);
      // Assert the "editingDowncast" HTML before making changes.
      $assert_session->elementExists('css', '.ck-content .ck-widget.' . $expected_widget_class . ' > img[src*="' . $src . '"][alt="drupalimage test image"]');

      // Assert the "dataDowncast" HTML before making changes.
      $xpath = new \DOMXPath($this->getEditorDataAsDom());
      $this->assertNotEmpty($xpath->query('//img[@alt="drupalimage test image"]'));
      $this->assertEmpty($xpath->query('//a'));

      // Assert the link button is present and not pressed.
      $link_button = $this->getEditorButton('Link');
      $this->assertSame('false', $link_button->getAttribute('aria-pressed'));

      // Tests linking images.
      $drupalimage->click();
      $this->assertTrue($drupalimage->hasClass('ck-widget_selected'));
      $this->assertEditorButtonEnabled('Link');
      // Assert structure of image toolbar balloon.
      $this->assertVisibleBalloon('.ck-toolbar[aria-label="Image toolbar"]');
      $link_image_button = $this->getBalloonButton('Link image');
      // Click the "Link image" button.
      $this->assertSame('false', $link_image_button->getAttribute('aria-pressed'));
      $link_image_button->press();
      // Assert structure of link form balloon.
      $balloon = $this->assertVisibleBalloon('.ck-link-form');
      $url_input = $balloon->find('css', '.ck-labeled-field-view__input-wrapper .ck-input-text[inputmode=url]');
      // Fill in link form balloon's <input> and hit "Insert".
      $url_input->setValue('http://www.drupal.org/association');
      $balloon->pressButton('Insert');

      // Assert the "editingDowncast" HTML after making changes. First assert
      // the link exists, then assert the expected DOM structure in detail.
      $assert_session->elementExists('css', '.ck-content a[href*="//www.drupal.org/association"]');
      // For inline images, the link is wrapping the widget; for block images
      // the link lives inside the widget. (This is how it is implemented
      // upstream, it could be implemented differently, we just want to ensure
      // we do not break it. Drupal only cares about having its own
      // "dataDowncast", the "editingDowncast" is considered an implementation
      // detail.)
      $assert_session->elementExists('css', $image_type === 'inline'
        ? '.ck-content a[href*="//www.drupal.org/association"] .ck-widget.' . $expected_widget_class . ' > img[src*="' . $src . '"][alt="drupalimage test image"]'
        : '.ck-content .ck-widget.' . $expected_widget_class . ' a[href*="//www.drupal.org/association"] > img[src*="' . $src . '"][alt="drupalimage test image"]'
      );

      // Assert the "dataDowncast" HTML after making changes.
      $xpath = new \DOMXPath($this->getEditorDataAsDom());
      $this->assertCount(1, $xpath->query('//a[@href="http://www.drupal.org/association"]/img[@alt="drupalimage test image"]'));
      $this->assertEmpty($xpath->query('//a[@href="http://www.drupal.org/association" and @class="trusted"]'));

      // Add `class="trusted"` to the link.
      $xpath = new \DOMXPath($this->getEditorDataAsDom());
      $this->assertEmpty($xpath->query('//a[@href="http://www.drupal.org/association" and @class="trusted"]'));
      $this->pressEditorButton('Source');
      $source_text_area = $assert_session->waitForElement('css', '.ck-source-editing-area textarea');
      $this->assertNotEmpty($source_text_area);
      $new_value = str_replace('<a ', '<a class="trusted" ', $source_text_area->getValue());
      $source_text_area->setValue('<p>temp</p>');
      $source_text_area->setValue($new_value);
      $this->pressEditorButton('Source');

      // When unrestricted, additional attributes on links should be retained.
      $xpath = new \DOMXPath($this->getEditorDataAsDom());
      $this->assertCount($unrestricted ? 1 : 0, $xpath->query('//a[@href="http://www.drupal.org/association" and @class="trusted"]'));

      // Save the entity whose text field is being edited.
      $page->pressButton('Save');

      // Assert the HTML the end user sees.
      $assert_session->elementExists('css', $unrestricted
        ? 'a[href="http://www.drupal.org/association"].trusted img[src*="' . $src . '"]'
        : 'a[href="http://www.drupal.org/association"] img[src*="' . $src . '"]');

      // Go back to edit the now *linked* <drupal-media>. Everything from this
      // point onwards is effectively testing "upcasting" and proving there is
      // no data loss.
      $this->drupalGet($this->host->toUrl('edit-form'));
      $this->waitForEditor();

      // Assert the "dataDowncast" HTML before making changes.
      $xpath = new \DOMXPath($this->getEditorDataAsDom());
      $this->assertNotEmpty($xpath->query('//img[@alt="drupalimage test image"]'));
      $this->assertNotEmpty($xpath->query('//a[@href="http://www.drupal.org/association"]'));
      $this->assertNotEmpty($xpath->query('//a[@href="http://www.drupal.org/association"]/img[@alt="drupalimage test image"]'));
      $this->assertCount($unrestricted ? 1 : 0, $xpath->query('//a[@href="http://www.drupal.org/association" and @class="trusted"]'));

      // Tests unlinking images.
      $drupalimage->click();
      $this->assertEditorButtonEnabled('Link');
      $this->assertSame('true', $this->getEditorButton('Link')->getAttribute('aria-pressed'));
      // Assert structure of image toolbar balloon.
      $this->assertVisibleBalloon('.ck-toolbar[aria-label="Image toolbar"]');
      $link_image_button = $this->getBalloonButton('Link image');
      $this->assertSame('true', $link_image_button->getAttribute('aria-pressed'));
      $link_image_button->click();
      // Assert structure of link actions balloon.
      $this->getBalloonButton('Edit link');
      $unlink_image_button = $this->getBalloonButton('Unlink');
      // Click the "Unlink" button.
      $unlink_image_button->click();
      $this->assertSame('false', $this->getEditorButton('Link')->getAttribute('aria-pressed'));

      // Assert the "editingDowncast" HTML after making changes. Assert the
      // widget exists but not the link, or *any* link for that matter. Then
      // assert the expected DOM structure in detail.
      $assert_session->elementExists('css', '.ck-content .ck-widget.' . $expected_widget_class);
      $assert_session->elementNotExists('css', '.ck-content a');
      $assert_session->elementExists('css', '.ck-content .ck-widget.' . $expected_widget_class . ' > img[src*="' . $src . '"][alt="drupalimage test image"]');

      // Assert the "dataDowncast" HTML after making changes.
      $xpath = new \DOMXPath($this->getEditorDataAsDom());
      $this->assertCount(0, $xpath->query('//a[@href="http://www.drupal.org/association"]/img[@alt="drupalimage test image"]'));
      $this->assertCount(1, $xpath->query('//img[@alt="drupalimage test image"]'));
      $this->assertCount(0, $xpath->query('//a'));
    }
  }

  /**
   * Returns data for testLinkability() and testImageArbitraryHtml().
   */
  protected function providerLinkability(): array {
    return [
      'BLOCK image, restricted' => ['block', FALSE],
      'BLOCK image, unrestricted' => ['block', TRUE],
      'INLINE image, restricted' => ['inline', FALSE],
      'INLINE image, unrestricted' => ['inline', TRUE],
    ];
  }

  /**
   * Ensures that images can have caption set.
   */
  public function testImageCaption(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // The foo attribute is added to be removed later by CKEditor 5 to make sure
    // CKEditor 5 was able to downcast data.
    $img_tag = '<img ' . $this->imageAttributesAsString() . ' alt="drupalimage test image" data-caption="Alpacas &lt;em&gt;are&lt;/em&gt; cute&lt;br&gt;really!" foo="bar">';
    $this->host->body->value = $img_tag;
    $this->host->save();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();

    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
    $this->assertNotEmpty($figcaption = $assert_session->waitForElement('css', '.image figcaption'));
    $this->assertSame('Alpacas <em>are</em> cute<br>really!', $figcaption->getHtml());
    $page->pressButton('Source');
    $editor_dom = $this->getEditorDataAsDom();
    $data_caption = $editor_dom->getElementsByTagName('img')->item(0)->getAttribute('data-caption');
    $this->assertSame('Alpacas <em>are</em> cute<br>really!', $data_caption);

    $page->pressButton('Save');

    $src = $this->imageAttributes()['src'];
    $expected = '<img ' . $this->imageAttributesAsString(TRUE) . ' alt="drupalimage test image" data-caption="Alpacas &lt;em&gt;are&lt;/em&gt; cute&lt;br&gt;really!">';
    $expected_dom = Html::load($expected);
    $this->assertEquals($expected_dom->getElementsByTagName('body')->item(0)->C14N(), $editor_dom->getElementsByTagName('body')->item(0)->C14N());
    $assert_session->elementExists('xpath', '//figure/img[@src="' . $src . '" and not(@data-caption)]');
    $assert_session->responseContains('<figcaption>Alpacas <em>are</em> cute<br>really!</figcaption>');
  }

}
