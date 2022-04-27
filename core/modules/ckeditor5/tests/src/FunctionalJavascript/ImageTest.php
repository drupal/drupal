<?php

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\Component\Utility\Html;
use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Symfony\Component\Validator\ConstraintViolation;

// cspell:ignore imageresize imageupload

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\ImageUpload
 * @group ckeditor5
 * @internal
 */
class ImageTest extends CKEditor5TestBase {

  use CKEditor5TestTrait;
  use TestFileCreationTrait;

  /**
   * The user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The sample image File entity to embed.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

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
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p> <br> <em> <a href> <img src alt data-entity-uuid data-entity-type height width data-caption data-align>',
          ],
        ],
        'filter_align' => ['status' => TRUE],
        'filter_caption' => ['status' => TRUE],
      ],
    ])->save();
    Editor::create([
      'editor' => 'ckeditor5',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'items' => [
            'uploadImage',
            'sourceEditing',
            'link',
            'italic',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [],
          ],
          'ckeditor5_imageResize' => [
            'allow_resize' => TRUE,
          ],
        ],
      ],
      'image_upload' => [
        'status' => TRUE,
        'scheme' => 'public',
        'directory' => 'inline-images',
        'max_size' => '1M',
        'max_dimensions' => ['width' => 100, 'height' => 100],
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
    $this->adminUser = $this->drupalCreateUser([
      'use text format test_format',
      'bypass node access',
      'administer filters',
    ]);

    // Create a sample host entity to embed images in.
    $this->file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $this->file->save();
    $this->host = $this->createNode([
      'type' => 'page',
      'title' => 'Animals with strange names',
      'body' => [
        'value' => '<p>The pirate is irate.</p>',
        'format' => 'test_format',
      ],
    ]);
    $this->host->save();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Ensures that attributes are retained on conversion.
   */
  public function testAttributeRetentionDuringUpcasting() {
    // Run test cases in a single test to make the test run faster.
    $attributes_to_retain = [
      '-none-' => 'inline',
      'data-caption="test caption ðŸ¦™"' => 'block',
      'data-align="left"' => 'inline',
    ];

    foreach ($attributes_to_retain as $attribute_to_retain => $expected_upcast_behavior_when_wrapped_in_block_element) {
      if ($attribute_to_retain === '-none-') {
        $attribute_to_retain = '';
      }
      $img_tag = '<img ' . $attribute_to_retain . ' alt="drupalimage test image" data-entity-type="file" data-entity-uuid="' . $this->file->uuid() . '" src="' . $this->file->createFileUrl() . '" />';
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
        // Image tag wrapped with an unallowed paragraph-like element. When
        // inline is the expected upcast behavior the wrapping is expected.
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
    $img_tag = '<img data-foo="bar" alt="drupalimage test image" data-entity-type="file" data-entity-uuid="' . $this->file->uuid() . '" src="' . $this->file->createFileUrl() . '" />';
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
    $img_tag = '<img alt="drupalimage test image" data-entity-type="file" data-entity-uuid="' . $this->file->uuid() . '" src="' . $this->file->createFileUrl() . '" />';
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

    // Assert the "editingDowncast" HTML before making changes.
    $assert_session->elementExists('css', '.ck-content .ck-widget.' . $expected_widget_class . ' > img[src*="image-test.png"][alt="drupalimage test image"]');

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
      ? '.ck-content a[href*="//www.drupal.org/association"] .ck-widget.' . $expected_widget_class . ' > img[src*="image-test.png"][alt="drupalimage test image"]'
      : '.ck-content .ck-widget.' . $expected_widget_class . ' a[href*="//www.drupal.org/association"] > img[src*="image-test.png"][alt="drupalimage test image"]'
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
      ? 'a[href="http://www.drupal.org/association"].trusted img[src*="image-test.png"]'
      : 'a[href="http://www.drupal.org/association"] img[src*="image-test.png"]');

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
    // @todo Remove the different assertion for the "inline, unrestricted" case when https://www.drupal.org/project/ckeditor5/issues/3247634 is fixed.
    if ($image_type === 'inline' && $unrestricted) {
      $assert_session->elementNotExists('css', '.ck-content a[href]');
      $assert_session->elementExists('css', '.ck-content a.trusted');
    }
    else {
      $assert_session->elementNotExists('css', '.ck-content a');
    }
    $assert_session->elementExists('css', '.ck-content .ck-widget.' . $expected_widget_class . ' > img[src*="image-test.png"][alt="drupalimage test image"]');

    // Assert the "dataDowncast" HTML after making changes.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertCount(0, $xpath->query('//a[@href="http://www.drupal.org/association"]/img[@alt="drupalimage test image"]'));
    $this->assertCount(1, $xpath->query('//img[@alt="drupalimage test image"]'));
    // @todo Remove the different assertion for the inline cases when https://www.drupal.org/project/ckeditor5/issues/3247634 is fixed.
    if ($image_type === 'inline') {
      $this->assertCount(1, $xpath->query('//a'));
    }
    else {
      $this->assertCount(0, $xpath->query('//a'));
    }
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
    $img_tag = '<img data-entity-type="file" data-entity-uuid="' . $this->file->uuid() . '" src="' . $this->file->createFileUrl() . '" width="500" />';
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
    $this->assertNotEmpty($image_upload_field = $page->find('css', '.ck-file-dialog-button input[type="file"]'));
    $image = $this->getTestFiles('image')[0];
    $image_upload_field->attachFile($this->container->get('file_system')->realpath($image->uri));
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.image'));
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-balloon-panel'));
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
    $img_tag = '<img alt="drupalimage test image" data-entity-type="file" data-entity-uuid="' . $this->file->uuid() . '" src="' . $this->file->createFileUrl() . '" />';
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
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.messages.messages--status'));
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
    $this->host->body->value = sprintf('<img data-foo="bar" alt="drupalimage test image" data-entity-type="file" data-entity-uuid="%s" src="%s" width="%s" />', $this->file->uuid(), $this->file->createFileUrl(), $width);
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
    $img_tag = '<img alt="drupalimage test image" data-caption="Alpacas &lt;em&gt;are&lt;/em&gt; cute" foo="bar" data-entity-type="file" data-entity-uuid="' . $this->file->uuid() . '" src="' . $this->file->createFileUrl() . '">';
    $this->host->body->value = $img_tag;
    $this->host->save();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();

    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-editor'));
    $this->assertNotEmpty($figcaption = $assert_session->waitForElement('css', '.image figcaption'));
    $this->assertSame('Alpacas <em>are</em> cute', $figcaption->getHtml());
    $page->pressButton('Source');
    $editor_dom = $this->getEditorDataAsDom();
    $data_caption = $editor_dom->getElementsByTagName('img')->item(0)->getAttribute('data-caption');
    $this->assertSame('Alpacas <em>are</em> cute', $data_caption);

    $page->pressButton('Save');

    $this->assertEquals('<img src="' . $this->file->createFileUrl() . '" data-entity-uuid="' . $this->file->uuid() . '" data-entity-type="file" alt="drupalimage test image" data-caption="Alpacas &lt;em&gt;are&lt;/em&gt; cute">', Node::load(1)->get('body')->value);
    $assert_session->elementExists('xpath', '//figure/img[@src="' . $this->file->createFileUrl() . '" and not(@data-caption)]');
    $assert_session->responseContains('<figcaption>Alpacas <em>are</em> cute</figcaption>');
  }

  /**
   * Data provider for ::testWidth().
   *
   * @return \string[][]
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
            'uploadImage',
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
    $this->assertNotEmpty($image_upload_field = $page->find('css', '.ck-file-dialog-button input[type="file"]'));
    $image = $this->getTestFiles('image')[0];
    $image_upload_field->attachFile($this->container->get('file_system')->realpath($image->uri));
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

  /**
   * Tests the ckeditor5_imageResize and ckeditor5_imageUpload settings forms.
   */
  public function testImageSettingsForm() {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/config/content/formats/manage/test_format');

    // The image resize and upload plugin settings forms should be present.
    $assert_session->elementExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-imageresize"]');
    $assert_session->elementExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-imageupload"]');

    // Removing the imageUpload button from the toolbar must remove the plugin
    // settings forms too.
    $this->triggerKeyUp('.ckeditor5-toolbar-item-uploadImage', 'ArrowUp');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementNotExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-imageresize"]');
    $assert_session->elementNotExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-imageupload"]');

    // Re-adding the imageUpload button to the toolbar must re-add the plugin
    // settings forms too.
    $this->triggerKeyUp('.ckeditor5-toolbar-item-uploadImage', 'ArrowDown');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-imageresize"]');
    $assert_session->elementExists('css', '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-imageupload"]');
  }

}
