<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;

// cspell:ignore imageresize

/**
 * Provides test methods using data providers for image tests.
 */
trait ImageTestProviderTrait {

  /**
   * Tests that alt text is required for images.
   *
   * @see https://ckeditor.com/docs/ckeditor5/latest/framework/guides/architecture/editing-engine.html#conversion
   *
   * @dataProvider providerAltTextRequired
   */
  public function testAltTextRequired(bool $unrestricted): void {
    // Disable filter_html.
    if ($unrestricted) {
      FilterFormat::load('test_format')
        ->setFilterConfig('filter_html', ['status' => FALSE])
        ->save();
    }

    // Make the test content has a block image and an inline image.
    $img_tag = preg_replace(
      '/width="\d+" height="\d+"/',
      'width="500"',
      '<img ' . $this->imageAttributesAsString() . ' />'
    );
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

  public static function providerAltTextRequired(): array {
    return [
      'Restricted' => [FALSE],
      'Unrestricted' => [TRUE],
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

  public static function providerAlignment() {
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

    // Despite the absence of a `height` attribute on the `<img>`, CKEditor 5
    // should generate an appropriate `height`, matching with the aspect ratio
    // of the image.
    $expected_computed_height = $width;
    if (!str_ends_with($width, '%')) {
      $ratio = $width / (int) $this->imageAttributes()['width'];
      $expected_computed_height = (string) (int) round($ratio * (int) $this->imageAttributes()['height']);
    }

    // Add image to the host body.
    $this->host->body->value = sprintf('<img data-foo="bar" alt="drupalimage test image" ' . $this->imageAttributesAsString() . ' width="%s" />', $width);
    $this->host->save();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();

    // Ensure that the image is upcast as expected. In the editing view, the
    // width attribute should downcast to an inline style on the container
    // element.
    $assert_session->waitForElementVisible('css', ".ck-widget.image");
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', ".ck-widget.image[style] img"));

    // Ensure that the width attribute is retained on downcast.
    $editor_data = $this->getEditorDataAsDom();
    $img_in_editor = $editor_data->getElementsByTagName('img')->item(0);
    $this->assertSame($width, $img_in_editor->getAttribute('width'));
    $this->assertSame($expected_computed_height, $img_in_editor->getAttribute('height'));

    // Save the node and ensure that the width attribute is retained, and ensure
    // that a natural image ratio-respecting height attribute has been added.
    $page->pressButton('Save');
    $this->assertNotEmpty($assert_session->waitForElement('css', "img[width='$width'][height='$expected_computed_height']"));
  }

  /**
   * Data provider for ::testWidth().
   *
   * @return string[][]
   */
  public static function providerWidth(): array {
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
  public static function providerResize(): array {
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
