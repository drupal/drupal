<?php

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Media
 * @group ckeditor5
 * @internal
 */
class MediaTest extends WebDriverTestBase {

  use CKEditor5TestTrait;
  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * The user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The sample Media entity to embed.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $media;

  /**
   * A host entity with a body field to embed media in.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $host;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'media',
    'node',
    'text',
    'media_test_embed',
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
            'allowed_html' => '<p> <br> <a href> <drupal-media data-entity-type data-entity-uuid alt>',
          ],
        ],
        'filter_align' => ['status' => TRUE],
        'filter_caption' => ['status' => TRUE],
        'media_embed' => ['status' => TRUE],
      ],
    ])->save();
    Editor::create([
      'editor' => 'ckeditor5',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'items' => [
            'sourceEditing',
            'link',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [],
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

    // Note that media_install() grants 'view media' to all users by default.
    $this->adminUser = $this->drupalCreateUser([
      'use text format test_format',
      'bypass node access',
    ]);

    // Create a sample media entity to be embedded.
    $this->createMediaType('image', ['id' => 'image']);
    File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ])->save();
    $this->media = Media::create([
      'bundle' => 'image',
      'name' => 'Screaming hairy armadillo',
      'field_media_image' => [
        [
          'target_id' => 1,
          'alt' => 'default alt',
          'title' => 'default title',
        ],
      ],
    ]);
    $this->media->save();

    // Create a sample host entity to embed media in.
    $this->drupalCreateContentType(['type' => 'blog']);
    $this->host = $this->createNode([
      'type' => 'blog',
      'title' => 'Animals with strange names',
      'body' => [
        'value' => '<drupal-media data-caption="baz" data-entity-type="media" data-entity-uuid="' . $this->media->uuid() . '"></drupal-media>',
        'format' => 'test_format',
      ],
    ]);
    $this->host->save();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that only <drupal-media> tags are processed.
   *
   * @see \Drupal\Tests\media\Kernel\MediaEmbedFilterTest::testOnlyDrupalMediaTagProcessed()
   */
  public function testOnlyDrupalMediaTagProcessed() {
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace('drupal-media', 'p', $original_value);
    $this->host->save();

    // Assert that `<p data-* …>` is not upcast into a CKEditor Widget.
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $this->assertEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]', 1000));
    $assert_session->elementNotExists('css', '.ck-widget.drupal-media');

    $this->host->body->value = $original_value;
    $this->host->save();

    // Assert that `<drupal-media data-* …>` is upcast into a CKEditor Widget.
    $this->getSession()->reload();
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]'));
    $assert_session->elementExists('css', '.ck-widget.drupal-media');
  }

  /**
   * Tests that failed media embed preview requests inform the end user.
   */
  public function testErrorMessages() {
    // Assert that a request to the `media.filter.preview` route that does not
    // result in a 200 response (due to server error or network error) is
    // handled in the JavaScript by displaying the expected error message.
    // @see core/modules/media/js/media_embed_ckeditor.theme.js
    // @see js/ckeditor5_plugins/drupalMedia/src/drupalmediaediting.js
    $this->container->get('state')->set('test_media_filter_controller_throw_error', TRUE);
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $assert_session->waitForElementVisible('css', '.ck-widget.drupal-media');
    $this->assertEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]', 1000));
    $assert_session->elementNotExists('css', '.ck-widget.drupal-media .media');
    $this->assertNotEmpty($assert_session->waitForText('An error occurred while trying to preview the media. Please save your work and reload this page.'));
    // Now assert that the error doesn't appear when the override to force an
    // error is removed.
    $this->container->get('state')->set('test_media_filter_controller_throw_error', FALSE);
    $this->getSession()->reload();
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]'));

    // There's a second kind of error message that comes from the back end
    // that happens when the media uuid can't be converted to a media preview.
    // In this case, the error will appear in a the themeable
    // media-embed-error.html template.  We have a hook altering the css
    // classes to test the twig template is working properly and picking up our
    // extra class.
    // @see \Drupal\media\Plugin\Filter\MediaEmbed::renderMissingMediaIndicator()
    // @see core/modules/media/templates/media-embed-error.html.twig
    // @see media_test_embed_preprocess_media_embed_error()
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace($this->media->uuid(), 'invalid_uuid', $original_value);
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-widget.drupal-media .this-error-message-is-themeable'));

    // Test when using the classy theme, an additional class is added in
    // classy/templates/content/media-embed-error.html.twig.
    $this->assertTrue($this->container->get('theme_installer')->install(['classy']));
    $this->config('system.theme')
      ->set('default', 'classy')
      ->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-widget.drupal-media .this-error-message-is-themeable.media-embed-error--missing-source'));
    // @todo Uncomment this in https://www.drupal.org/project/ckeditor5/issues/3194084.
    // @codingStandardsIgnoreLine
    //$assert_session->responseContains('classy/css/components/media-embed-error.css');

    // Test that restoring a valid UUID results in the media embed preview
    // displaying.
    $this->host->body->value = $original_value;
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]'));
    $assert_session->elementNotExists('css', '.ck-widget.drupal-media .this-error-message-is-themeable');
  }

  /**
   * The CKEditor Widget must load a preview generated using the default theme.
   */
  public function testPreviewUsesDefaultThemeAndIsClientCacheable() {
    // Make the node edit form use the admin theme, like on most Drupal sites.
    $this->config('node.settings')
      ->set('use_admin_theme', TRUE)
      ->save();

    // Allow the test user to view the admin theme.
    $this->adminUser->addRole($this->drupalCreateRole(['view the administration theme']));
    $this->adminUser->save();

    // Configure a different default and admin theme, like on most Drupal sites.
    $this->config('system.theme')
      ->set('default', 'stable')
      ->set('admin', 'classy')
      ->save();

    // Assert that when looking at an embedded entity in the CKEditor Widget,
    // the preview is generated using the default theme, not the admin theme.
    // @see media_test_embed_entity_view_alter()
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]'));
    $element = $assert_session->elementExists('css', '[data-media-embed-test-active-theme]');
    $this->assertSame('stable', $element->getAttribute('data-media-embed-test-active-theme'));
    // Assert that the first preview request transferred >500 B over the wire.
    // Then toggle source mode on and off. This causes the CKEditor widget to be
    // destroyed and then reconstructed. Assert that during this reconstruction,
    // a second request is sent. This second request should have transferred 0
    // bytes: the browser should have cached the response, thus resulting in a
    // much better user experience.
    $this->assertGreaterThan(500, $this->getLastPreviewRequestTransferSize());
    $this->pressEditorButton('Source');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-source-editing-area'));
    // CKEditor 5 is very smart: if no changes were made in the Source Editing
    // Area, it will not rerender the contents. In this test, we
    // want to verify that Media preview responses are cached on the client side
    // so it is essential that rerendering occurs. To achieve this, we append a
    // single space.
    $source_text_area = $this->getSession()->getPage()->find('css', '[name="body[0][value]"] + .ck-editor textarea');
    $source_text_area->setValue($source_text_area->getValue() . ' ');
    $this->pressEditorButton('Source');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]'));
    $this->assertSame(0, $this->getLastPreviewRequestTransferSize());
  }

  /**
   * Tests caption editing in the CKEditor widget.
   */
  public function testEditableCaption() {
    // @todo Port in https://www.drupal.org/project/ckeditor5/issues/3246385
    $this->markTestSkipped('Blocked on https://www.drupal.org/project/ckeditor5/issues/3246385.');
  }

  /**
   * Tests the EditorMediaDialog's form elements' #access logic.
   */
  public function testDialogAccess() {
    // @todo Port in https://www.drupal.org/project/ckeditor5/issues/3245720
    $this->markTestSkipped('Blocked on https://www.drupal.org/project/ckeditor5/issues/3245720.');
  }

  /**
   * Tests the CKEditor 5 media plugin can override image media's alt attribute.
   */
  public function testAlt() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    // Wait for the media preview to load.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    // Test that by default no alt attribute is present on the drupal-media
    // element.
    $this->assertSourceAttributeSame('alt', NULL);
    // Test that the preview shows the alt value from the media field's
    // alt text.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img[alt*="default alt"]'));
    // Test that clicking the media widget triggers a CKEditor balloon panel
    // with a single button to override the alt text.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    // Click the "Override media image text alternative" button.
    $this->getBalloonButton('Override media image text alternative')->click();
    $this->assertVisibleBalloon('.ck-text-alternative-form');
    // Assert that the value is currently empty.
    // @todo Consider changing this in https://www.drupal.org/project/ckeditor5/issues/3246365.
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-text-alternative-form input[type=text]');
    $this->assertSame('', $alt_override_input->getValue());

    // Fill in the alt field and submit.
    // cSpell:disable-next-line
    $who_is_zartan = 'Zartan is the leader of the Dreadnoks.';
    $alt_override_input->setValue($who_is_zartan);
    $this->getBalloonButton('Save')->click();

    // Assert that the img within the media embed within the CKEditor contains
    // the overridden alt text set in the dialog.
    // @todo Uncomment this in https://www.drupal.org/project/ckeditor5/issues/3206522.
    // @codingStandardsIgnoreLine
//    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img[alt*="' . $who_is_zartan . '"]'));
    // Test `aria-label` attribute appears on the widget wrapper.
    $assert_session->elementExists('css', '.ck-widget.drupal-media [aria-label="Screaming hairy armadillo"]');

    // Test that the downcast drupal-media element now has the alt attribute
    // entered in the dialog.
    $this->assertSourceAttributeSame('alt', $who_is_zartan);

    // The alt field should now display the override instead of the default.
    $this->getBalloonButton('Override media image text alternative')->click();
    $this->assertVisibleBalloon('.ck-text-alternative-form');
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-text-alternative-form input[type=text]');
    $this->assertSame($who_is_zartan, $alt_override_input->getValue());

    // Test the process again with a different alt text to make sure it works
    // the second time around.
    $cobra_commander_bio = 'The supreme leader of the terrorist organization Cobra';
    // Set the alt field to the new alt text.
    $alt_override_input->setValue($cobra_commander_bio);
    $this->getBalloonButton('Save')->click();
    // Assert that the img within the media embed preview
    // within the CKEditor contains the overridden alt text set in the dialog.
    // @todo Uncomment this in https://www.drupal.org/project/ckeditor5/issues/3206522.
    // @codingStandardsIgnoreLine
//    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'drupal-media img[alt*="' . $cobra_commander_bio . '"]'));

    // Test that the downcast drupal-media element now has the alt attribute
    // entered in the dialog.
    $this->assertSourceAttributeSame('alt', $cobra_commander_bio);

    // The default value of the alt field should now display the override
    // instead of the value on the media image field.
    $this->getBalloonButton('Override media image text alternative')->click();
    $this->assertVisibleBalloon('.ck-text-alternative-form');
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-text-alternative-form input[type=text]');
    $this->assertSame($cobra_commander_bio, $alt_override_input->getValue());

    // Test that setting alt value to two double quotes will signal to the
    // MediaEmbed filter to unset the attribute on the media image field.
    // We intentionally add a space space after the two double quotes to test
    // the string is trimmed to two quotes.
    $alt_override_input->setValue('"" ');
    $this->getBalloonButton('Save')->click();
    // Verify that the two double quote empty alt indicator ('""') set in
    // the dialog has successfully resulted in a media image field with the
    // alt attribute present but without a value.
    // @todo Uncomment this in https://www.drupal.org/project/ckeditor5/issues/3206522.
    // @codingStandardsIgnoreLine
//    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'drupal-media img[alt=""]'));

    // Test that the downcast drupal-media element's alt attribute now has the
    // empty string indicator.
    $this->assertSourceAttributeSame('alt', '""');

    // Test that setting alt to back to an empty string within the dialog will
    // restore the default alt value saved in to the media image field of the
    // media item.
    $this->getBalloonButton('Override media image text alternative')->click();
    $this->assertVisibleBalloon('.ck-text-alternative-form');
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-text-alternative-form input[type=text]');
    $alt_override_input->setValue('');
    $this->getBalloonButton('Save')->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img[alt*="default alt"]'));

    // Test that the downcast drupal-media element no longer has an alt
    // attribute.
    $this->assertSourceAttributeSame('alt', NULL);
  }

  /**
   * Tests the CKEditor 5 media plugin loads the translated alt attribute.
   */
  public function testTranslationAlt() {
    // @todo Port in https://www.drupal.org/project/ckeditor5/issues/3246365
    $this->markTestSkipped('Blocked on https://www.drupal.org/project/ckeditor5/issues/3246365.');
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
  public function testLinkability(bool $unrestricted) {
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

    // Assert the "dataDowncast" HTML after making changes.
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($xpath->query('//drupal-media'));
    $this->assertEmpty($xpath->query('//a'));
  }

  public function providerLinkability(): array {
    return [
      'restricted' => [FALSE],
      'unrestricted' => [TRUE],
    ];
  }

  /**
   * Tests preview route access.
   *
   * @param bool $media_embed_enabled
   *   Whether to test with media_embed filter enabled on the text format.
   * @param bool $can_use_format
   *   Whether the logged in user is allowed to use the text format.
   *
   * @dataProvider previewAccessProvider
   */
  public function testEmbedPreviewAccess($media_embed_enabled, $can_use_format) {
    // Reconfigure the host entity's text format to suit our needs.
    /** @var \Drupal\filter\FilterFormatInterface $format */
    $format = FilterFormat::load($this->host->body->format);
    $format->set('filters', [
      'filter_align' => ['status' => TRUE],
      'filter_caption' => ['status' => TRUE],
      'media_embed' => ['status' => $media_embed_enabled],
    ]);
    $format->save();

    $permissions = [
      'bypass node access',
    ];
    if ($can_use_format) {
      $permissions[] = $format->getPermissionName();
    }
    $this->drupalLogin($this->drupalCreateUser($permissions));
    $this->drupalGet($this->host->toUrl('edit-form'));

    $assert_session = $this->assertSession();
    if ($can_use_format) {
      $this->waitForEditor();
      if ($media_embed_enabled) {
        // The preview rendering, which in this test will use Classy's
        // media.html.twig template, will fail without the CSRF token/header.
        // @see ::testEmbeddedMediaPreviewWithCsrfToken()
        $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'article.media'));
      }
      else {
        // If the filter isn't enabled, there won't be an error, but the
        // preview shouldn't be rendered.
        $assert_session->assertWaitOnAjaxRequest();
        $assert_session->elementNotExists('css', 'article.media');
      }
    }
    else {
      $assert_session->pageTextContains('This field has been disabled because you do not have sufficient permissions to edit it.');
    }
  }

  /**
   * Data provider for ::testEmbedPreviewAccess.
   */
  public function previewAccessProvider() {
    return [
      'media_embed filter enabled' => [
        TRUE,
        TRUE,
      ],
      'media_embed filter disabled' => [
        FALSE,
        TRUE,
      ],
      'media_embed filter enabled, user not allowed to use text format' => [
        TRUE,
        FALSE,
      ],
    ];
  }

  /**
   * Tests alignment integration.
   *
   * Tests that alignment is reflected onto the CKEditor Widget wrapper, that
   * the EditorMediaDialog allows altering the alignment and that the changes
   * are reflected on the widget and downcast drupal-media tag.
   */
  public function testAlignment() {
    // @todo Port in https://www.drupal.org/project/ckeditor5/issues/3246385
    $this->markTestSkipped('Blocked on https://www.drupal.org/project/ckeditor5/issues/3246385.');
  }

  /**
   * Tests the EditorMediaDialog can set the data-view-mode attribute.
   */
  public function testViewMode() {
    // @todo Port in https://www.drupal.org/project/ckeditor5/issues/3245720
    $this->markTestSkipped('Blocked on https://www.drupal.org/project/ckeditor5/issues/3245720.');
  }

  /**
   * Verifies value of an attribute on the downcast <drupal-media> element.
   *
   * Assumes CKEditor is in source mode.
   *
   * @param string $attribute
   *   The attribute to check.
   * @param string|null $value
   *   Either a string value or if NULL, asserts that <drupal-media> element
   *   doesn't have the attribute.
   *
   * @internal
   */
  protected function assertSourceAttributeSame(string $attribute, ?string $value): void {
    $dom = $this->getEditorDataAsDom();
    $drupal_media = (new \DOMXPath($dom))->query('//drupal-media');
    $this->assertNotEmpty($drupal_media);
    if ($value === NULL) {
      $this->assertFalse($drupal_media[0]->hasAttribute($attribute));
    }
    else {
      $this->assertSame($value, $drupal_media[0]->getAttribute($attribute));
    }
  }

  /**
   * Gets the transfer size of the last preview request.
   *
   * @return int
   *   The size of the bytes transferred.
   */
  protected function getLastPreviewRequestTransferSize() {
    $javascript = <<<JS
(function(){
  return window.performance
    .getEntries()
    .filter(function (entry) {
      return entry.initiatorType == 'fetch' && entry.name.indexOf('/media/test_format/preview') !== -1;
    })
    .pop()
    .transferSize;
})()
JS;
    return $this->getSession()->evaluateScript($javascript);
  }

}
