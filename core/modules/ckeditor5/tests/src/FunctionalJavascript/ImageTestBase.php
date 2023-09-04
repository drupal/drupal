<?php

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\Component\Utility\Html;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;

// cspell:ignore imageresize imageupload

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Image
 * @group ckeditor5
 * @internal
 */
abstract class ImageTestBase extends CKEditor5TestBase {

  use CKEditor5TestTrait;
  use TestFileCreationTrait;

  /**
   * The user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A host entity with a body field to embed images in.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $host;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Provides the relevant image attributes.
   *
   * @return string[]
   */
  protected function imageAttributes() {
    return ['src' => base_path() . 'core/misc/druplicon.png'];
  }

  /**
   * Helper to format attributes.
   *
   * @param bool $reverse
   *   Reverse attributes when printing them.
   *
   * @return string
   */
  protected function imageAttributesAsString($reverse = FALSE) {
    $string = [];
    foreach ($this->imageAttributes() as $key => $value) {
      $string[] = $key . '="' . $value . '"';
    }
    if ($reverse) {
      $string = array_reverse($string);
    }
    return implode(' ', $string);
  }

  /**
   * Add an image to the CKEditor 5 editable zone.
   */
  protected function addImage() {
    $page = $this->getSession()->getPage();
    $src = $this->imageAttributes()['src'];
    $this->waitForEditor();
    $this->pressEditorButton('Insert image');
    $panel = $page->find('css', '.ck-dropdown__panel.ck-image-insert__panel');
    $src_input = $panel->find('css', 'input[type=text]');
    $src_input->setValue($src);
    $panel->find('xpath', "//button[span[text()='Insert']]")->click();
    // Wait for the image to be uploaded and rendered by CKEditor 5.
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '.ck-widget.image > img[src="' . $src . '"]'));
  }

  /**
   * Ensures that attributes are retained on conversion.
   */
  public function testAttributeRetentionDuringUpcasting() {
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
        // Image tag wrapped with an unallowed paragraph-like element (<div).
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
   *
   * @dataProvider providerLinkability
   */
  public function testImageArbitraryHtml(string $image_type, bool $unrestricted) {
    $editor = Editor::load('test_format');
    $settings = $editor->getSettings();

    // Allow the data-foo attribute in img via GHS.
    $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags'] = ['<img data-foo>'];
    $editor->setSettings($settings);
    $editor->save();

    // Disable filter_html.
    if ($unrestricted) {
      FilterFormat::load('test_format')
        ->setFilterConfig('filter_html', ['status' => FALSE])
        ->save();
    }

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

  /**
   * Tests linkability of the image CKEditor widget.
   *
   * Due to the complex overrides that `drupalImage.DrupalImage` is making, this
   * is explicitly testing the "editingDowncast" and "dataDowncast" results.
   * These are CKEditor 5 concepts.
   *
   * @see https://ckeditor.com/docs/ckeditor5/latest/framework/guides/architecture/editing-engine.html#conversion
   *
   * @dataProvider providerLinkability
   */
  public function testLinkability(string $image_type, bool $unrestricted) {
    assert($image_type === 'inline' || $image_type === 'block');

    // Disable filter_html.
    if ($unrestricted) {
      FilterFormat::load('test_format')
        ->setFilterConfig('filter_html', ['status' => FALSE])
        ->save();
    }

    // Make the test content have either a block image or an inline image.
    $img_tag = '<img alt="drupalimage test image" data-entity-type="file" ' . $this->imageAttributesAsString() . ' />';
    $this->host->body->value .= $image_type === 'block'
      ? $img_tag
      : "<p>$img_tag</p>";
    $this->host->save();
    // Adjust the expectations accordingly.
    $expected_widget_class = $image_type === 'block' ? 'image' : 'image-inline';

    $page = $this->getSession()->getPage();

    $this->drupalGet($this->host->toUrl('edit-form'));
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
    $url_input = $balloon->find('css', '.ck-labeled-field-view__input-wrapper .ck-input-text');
    // Fill in link form balloon's <input> and hit "Save".
    $url_input->setValue('http://www.drupal.org/association');
    $balloon->pressButton('Save');

    // Assert the "editingDowncast" HTML after making changes. First assert the
    // link exists, then assert the expected DOM structure in detail.
    $assert_session->elementExists('css', '.ck-content a[href*="//www.drupal.org/association"]');
    // For inline images, the link is wrapping the widget; for block images the
    // link lives inside the widget. (This is how it is implemented upstream, it
    // could be implemented differently, we just want to ensure we do not break
    // it. Drupal only cares about having its own "dataDowncast", the
    // "editingDowncast" is considered an implementation detail.)
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
    // point onwards is effectively testing "upcasting" and proving there is no
    // data loss.
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

  /**
   * Tests that alt text is required for images.
   *
   * @see https://ckeditor.com/docs/ckeditor5/latest/framework/guides/architecture/editing-engine.html#conversion
   *
   * @dataProvider providerAltTextRequired
   */
  public function testAltTextRequired(bool $unrestricted) {
    // Disable filter_html.
    if ($unrestricted) {
      FilterFormat::load('test_format')
        ->setFilterConfig('filter_html', ['status' => FALSE])
        ->save();
    }

    // Make the test content has a block image and an inline image.
    $img_tag = '<img ' . $this->imageAttributesAsString() . ' width="500" />';
    $this->host->body->value .= $img_tag . "<p>$img_tag</p>";
    $this->host->save();

    $page = $this->getSession()->getPage();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();

    // Confirm both of the images exist.
    $this->assertNotEmpty($image_block = $assert_session->waitForElementVisible('css', ".ck-content .ck-widget.image"));
    $this->assertNotEmpty($image_inline = $assert_session->waitForElementVisible('css', ".ck-content .ck-widget.image-inline"));

    // Confirm both of the images have an alt text required warning.
    $this->assertNotEmpty($image_block->find('css', '.image-alternative-text-missing-wrapper'));
    $this->assertNotEmpty($image_inline->find('css', '.image-alternative-text-missing-wrapper'));

    // Add alt text to the block image.
    $image_block->find('css', '.image-alternative-text-missing button')->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-balloon-panel'));
    $this->assertVisibleBalloon('.ck-text-alternative-form');

    // Ensure that the missing alt text warning is hidden when the alternative
    // text form is open.
    $assert_session->waitForElement('css', '.ck-content .ck-widget.image .image-alternative-text-missing.ck-hidden');
    $assert_session->elementExists('css', '.ck-content .ck-widget.image-inline .image-alternative-text-missing');
    $assert_session->elementNotExists('css', '.ck-content .ck-widget.image-inline .image-alternative-text-missing.ck-hidden');

    // Ensure that the missing alt text error is not added to decorative images.
    $this->assertNotEmpty($decorative_button = $this->getBalloonButton('Decorative image'));
    $assert_session->elementExists('css', '.ck-balloon-panel .ck-text-alternative-form input[type=text]');
    $decorative_button->click();
    $assert_session->elementExists('css', '.ck-content .ck-widget.image .image-alternative-text-missing.ck-hidden');
    $assert_session->elementExists('css', ".ck-content .ck-widget.image-inline .image-alternative-text-missing-wrapper");
    $assert_session->elementNotExists('css', '.ck-content .ck-widget.image-inline .image-alternative-text-missing.ck-hidden');

    // Ensure that the missing alt text error is removed after saving the
    // changes.
    $this->assertNotEmpty($save_button = $this->getBalloonButton('Save'));
    $save_button->click();
    $this->assertTrue($assert_session->waitForElementRemoved('css', ".ck-content .ck-widget.image .image-alternative-text-missing-wrapper"));
    $assert_session->elementExists('css', '.ck-content .ck-widget.image-inline .image-alternative-text-missing-wrapper');

    // Ensure that the decorative image downcasts into empty alt attribute.
    $editor_dom = $this->getEditorDataAsDom();
    $decorative_img = $editor_dom->getElementsByTagName('img')->item(0);
    $this->assertTrue($decorative_img->hasAttribute('alt'));
    $this->assertEmpty($decorative_img->getAttribute('alt'));

    // Ensure that missing alt text error is not added to images with alt text.
    $this->assertNotEmpty($alt_text_button = $this->getBalloonButton('Change image alternative text'));
    $alt_text_button->click();

    $decorative_button->click();
    $this->assertNotEmpty($save_button = $this->getBalloonButton('Save'));
    $this->assertTrue($save_button->hasClass('ck-disabled'));

    $this->assertNotEmpty($alt_override_input = $page->find('css', '.ck-balloon-panel .ck-text-alternative-form input[type=text]'));
    $alt_override_input->setValue('There is now alt text');
    $this->assertTrue($assert_session->waitForElementRemoved('css', '.ck-balloon-panel .ck-text-alternative-form .ck-disabled'));
    $this->assertFalse($save_button->hasClass('ck-disabled'));
    $save_button->click();

    // Save the node and confirm that the alt text is retained.
    $page->pressButton('Save');
    $this->assertNotEmpty($assert_session->waitForElement('css', 'img[alt="There is now alt text"]'));

    // Ensure that alt form is opened after image upload.
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->addImage();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-text-alternative-form'));
    $this->assertVisibleBalloon('.ck-text-alternative-form');
  }

  public function providerAltTextRequired(): array {
    return [
      'Restricted' => [FALSE],
      'Unrestricted' => [TRUE],
    ];
  }

  public function providerLinkability(): array {
    return [
      'BLOCK image, restricted' => ['block', FALSE],
      'BLOCK image, unrestricted' => ['block', TRUE],
      'INLINE image, restricted' => ['inline', FALSE],
      'INLINE image, unrestricted' => ['inline', TRUE],
    ];
  }

  /**
   * Tests alignment integration.
   *
   * @dataProvider providerAlignment
   */
  public function testAlignment(string $image_type): void {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Make the test content have either a block image or an inline image.
    $img_tag = '<img alt="drupalimage test image" ' . $this->imageAttributesAsString() . ' />';
    $this->host->body->value .= $image_type === 'block'
      ? $img_tag
      : "<p>$img_tag</p>";
    $this->host->save();

    $image_selector = $image_type === 'block' ? '.ck-widget.image' : '.ck-widget.image-inline';
    $default_alignment = $image_type === 'block' ? 'Break text' : 'In line';

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', $image_selector));

    // Ensure that the default alignment option matches expectation.
    $this->click($image_selector);
    $this->assertVisibleBalloon('[aria-label="Image toolbar"]');
    $this->assertTrue($this->getBalloonButton($default_alignment)->hasClass('ck-on'));
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('img')
      ->item(0);
    $this->assertFalse($drupal_media_element->hasAttribute('data-align'));
    $this->getBalloonButton('Align center and break text')->click();

    // Assert the alignment class exists after editing downcast.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-widget.image.image-style-align-center'));
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('img')
      ->item(0);
    $this->assertEquals('center', $drupal_media_element->getAttribute('data-align'));

    $page->pressButton('Save');
    // Check that the 'content has been updated' message status appears to confirm we left the editor.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '[data-drupal-messages]'));
    // Check that the class is correct in the front end.
    $assert_session->elementExists('css', 'img.align-center');
    // Go back to the editor to check that the alignment class still exists.
    $edit_url = $this->getSession()->getCurrentURL() . '/edit';
    $this->drupalGet($edit_url);
    $this->waitForEditor();
    $assert_session->elementExists('css', '.ck-widget.image.image-style-align-center');

    // Ensure that "Centered image" alignment option is selected.
    $this->click('.ck-widget.image');
    $this->assertVisibleBalloon('[aria-label="Image toolbar"]');
    $this->assertTrue($this->getBalloonButton('Align center and break text')->hasClass('ck-on'));
    $this->getBalloonButton('Break text')->click();
    $this->assertTrue($assert_session->waitForElementRemoved('css', '.ck-widget.image.image-style-align-center'));
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('img')
      ->item(0);
    $this->assertFalse($drupal_media_element->hasAttribute('data-align'));
  }

  public function providerAlignment() {
    return [
      'Block image' => ['block'],
      'Inline image' => ['inline'],
    ];
  }

  /**
   * Ensures that width attribute upcasts and downcasts correctly.
   *
   * @param string $width
   *   The width input for the image.
   *
   * @dataProvider providerWidth
   */
  public function testWidth(string $width): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Add image to the host body.
    $this->host->body->value = sprintf('<img data-foo="bar" alt="drupalimage test image" ' . $this->imageAttributesAsString() . ' width="%s" />', $width);
    $this->host->save();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();

    // Ensure that the image is upcast as expected. In the editing view, the
    // width attribute should downcast to an inline style on the container
    // element.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.image[style] img'));

    // Ensure that the width attribute is retained on downcast.
    $editor_data = $this->getEditorDataAsDom();
    $width_from_editor = $editor_data->getElementsByTagName('img')->item(0)->getAttribute('width');
    $this->assertSame($width, $width_from_editor);

    // Save the node and ensure that the width attribute is retained.
    $page->pressButton('Save');
    $this->assertNotEmpty($assert_session->waitForElement('css', "img[width='$width']"));
  }

  /**
   * Ensures that images can have caption set.
   */
  public function testImageCaption() {
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
    $this->assertEquals('<img ' . $this->imageAttributesAsString(TRUE) . ' alt="drupalimage test image" data-caption="Alpacas &lt;em&gt;are&lt;/em&gt; cute&lt;br&gt;really!">', Node::load(1)->get('body')->value);
    $assert_session->elementExists('xpath', '//figure/img[@src="' . $src . '" and not(@data-caption)]');
    $assert_session->responseContains('<figcaption>Alpacas <em>are</em> cute<br>really!</figcaption>');
  }

  /**
   * Data provider for ::testWidth().
   *
   * @return string[][]
   */
  public function providerWidth(): array {
    return [
      'Image resize with percent unit (only allowed in HTML 4)' => [
        'width' => '33%',
      ],
      'Image resize with (implied) px unit' => [
        'width' => '100',
      ],
    ];
  }

  /**
   * Tests the image resize plugin.
   *
   * Confirms that enabling the resize plugin introduces the resize class to
   * images within CKEditor 5.
   *
   * @param bool $is_resize_enabled
   *   Boolean flag to test enabled or disabled.
   *
   * @dataProvider providerResize
   */
  public function testResize(bool $is_resize_enabled): void {
    // Disable resize plugin because it is enabled by default.
    if (!$is_resize_enabled) {
      Editor::load('test_format')->setSettings([
        'toolbar' => [
          'items' => [
            'drupalInsertImage',
          ],
        ],
        'plugins' => [
          'ckeditor5_imageResize' => [
            'allow_resize' => FALSE,
          ],
        ],
      ])->save();
    }

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet('node/add');
    $page->fillField('title[0][value]', 'My test content');
    $this->addImage();
    $image_figure = $assert_session->waitForElementVisible('css', 'figure');
    $this->assertSame($is_resize_enabled, $image_figure->hasClass('ck-widget_with-resizer'));
  }

  /**
   * Data provider for ::testResize().
   *
   * @return array
   *   The test cases.
   */
  public function providerResize(): array {
    return [
      'Image resize is enabled' => [
        'is_resize_enabled' => TRUE,
      ],
      'Image resize is disabled' => [
        'is_resize_enabled' => FALSE,
      ],
    ];
  }

}
