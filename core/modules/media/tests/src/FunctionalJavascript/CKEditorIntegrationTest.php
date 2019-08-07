<?php

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * @coversDefaultClass \Drupal\media\Plugin\CKEditorPlugin\DrupalMedia
 * @group media
 */
class CKEditorIntegrationTest extends WebDriverTestBase {

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
    'ckeditor',
    'media',
    'node',
    'text',
    'media_test_ckeditor',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_align' => ['status' => TRUE],
        'filter_caption' => ['status' => TRUE],
        'media_embed' => ['status' => TRUE],
      ],
    ])->save();
    Editor::create([
      'editor' => 'ckeditor',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'rows' => [
            [
              [
                'name' => 'All the things',
                'items' => [
                  'Source',
                  'Bold',
                  'Italic',
                  'DrupalLink',
                  'DrupalUnlink',
                  'DrupalImage',
                ],
              ],
            ],
          ],
        ],
      ],
    ])->save();

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
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $assert_session = $this->assertSession();
    $this->assertEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]', 1000));
    $assert_session->elementNotExists('css', 'figure');

    $this->host->body->value = $original_value;
    $this->host->save();

    // Assert that `<drupal-media data-* …>` is upcast into a CKEditor Widget.
    $this->getSession()->reload();
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]'));
    $assert_session->elementExists('css', 'figure');
  }

  /**
   * Tests that failed media embed preview requests inform the end user.
   */
  public function testPreviewFailure() {
    // Assert that a request to the `media.filter.preview` route that does not
    // result in a 200 response (due to server error or network error) is
    // handled in the JavaScript by displaying the expected error message.
    $this->container->get('state')->set('test_media_filter_controller_throw_error', TRUE);
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $assert_session = $this->assertSession();
    $this->assertEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]', 1000));
    $assert_session->elementNotExists('css', 'figure');
    $error_message = $assert_session->elementExists('css', '.media-embed-error.media-embed-error--preview-error')
      ->getText();
    $this->assertSame('An error occurred while trying to preview the media. Please save your work and reload this page.', $error_message);
    // Now assert that the error doesn't appear when the override to force an
    // error is removed.
    $this->container->get('state')->set('test_media_filter_controller_throw_error', FALSE);
    $this->getSession()->reload();
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]'));
  }

  /**
   * The CKEditor Widget must load a preview generated using the default theme.
   */
  public function testPreviewUsesDefaultThemeAndIsClientCacheable() {
    // Make the node edit form use the admin theme, like on most Drupal sites.
    $this->config('node.settings')
      ->set('use_admin_theme', TRUE)
      ->save();
    $this->container->get('router.builder')->rebuild();

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
    // @see media_test_ckeditor_entity_view_alter()
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
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
    $this->pressEditorButton('source');
    $this->assertNotEmpty($assert_session->waitForElement('css', 'textarea.cke_source'));
    $this->pressEditorButton('source');
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]'));
    $this->assertSame(0, $this->getLastPreviewRequestTransferSize());
  }

  /**
   * Tests caption editing in the CKEditor widget.
   */
  public function testEditableCaption() {
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();

    // Type in the widget's editable for the caption.
    $this->getSession()->switchToIFrame('ckeditor');
    $assert_session = $this->assertSession();
    $this->assertNotEmpty($assert_session->waitForElement('css', 'figcaption'));
    $this->setCaption('Caught in a <strong>landslide</strong>! No escape from <em>reality</em>!');
    $this->getSession()->switchToIFrame('ckeditor');
    $assert_session->elementExists('css', 'figcaption > em');
    $assert_session->elementExists('css', 'figcaption > strong')->click();

    // Select the <strong> element and unbold it.
    $this->clickPathLinkByTitleAttribute("strong element");
    $this->pressEditorButton('bold');
    $this->getSession()->switchToIFrame('ckeditor');
    $assert_session->elementExists('css', 'figcaption > em');
    $assert_session->elementNotExists('css', 'figcaption > strong');

    // Select the <em> element and unitalicize it.
    $assert_session->elementExists('css', 'figcaption > em')->click();
    $this->clickPathLinkByTitleAttribute("em element");
    $this->pressEditorButton('italic');

    // The "source" button should reveal the HTML source in a state matching
    // what is shown in the CKEditor widget.
    $this->pressEditorButton('source');
    $source = $assert_session->elementExists('css', 'textarea.cke_source');
    $value = $source->getValue();
    $dom = Html::load($value);
    $xpath = new \DOMXPath($dom);
    $drupal_media = $xpath->query('//drupal-media')[0];
    $this->assertSame('Caught in a landslide! No escape from reality!', $drupal_media->getAttribute('data-caption'));

    // Change the caption by modifying the HTML source directly. When exiting
    // "source" mode, this should be respected.
    $poor_boy_text = "I'm just a <strong>poor boy</strong>, I need no sympathy!";
    $drupal_media->setAttribute("data-caption", $poor_boy_text);
    $source->setValue(Html::serialize($dom));
    $this->pressEditorButton('source');
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $figcaption = $assert_session->waitForElement('css', 'figcaption');
    $this->assertNotEmpty($figcaption);
    $this->assertSame($poor_boy_text, $figcaption->getHtml());

    // Select the <strong> element that we just set in "source" mode. This
    // proves that it was indeed rendered by the CKEditor widget.
    $strong = $figcaption->find('css', 'strong');
    $this->assertNotEmpty($strong);
    $strong->click();
    $this->pressEditorButton('bold');

    // Insert a link into the caption.
    $this->clickPathLinkByTitleAttribute("Caption element");
    $this->pressEditorButton('drupallink');
    $field = $assert_session->waitForElementVisible('xpath', '//input[@name="attributes[href]"]');
    $this->assertNotEmpty($field);
    $field->setValue('https://www.drupal.org');
    $assert_session->elementExists('css', 'button.form-submit')->press();

    // Wait for the live preview in the CKEditor widget to finish loading, then
    // edit the link; no `data-cke-saved-href` attribute should exist on it.
    $this->getSession()->switchToIFrame('ckeditor');
    $figcaption = $assert_session->waitForElement('css', 'figcaption');
    $page = $this->getSession()->getPage();
    // Wait for AJAX refresh.
    $page->waitFor(10, function () use ($figcaption) {
      return $figcaption->find('xpath', '//a[@href="https://www.drupal.org"]');
    });
    $assert_session->elementExists('css', 'a', $figcaption)->click();
    $this->clickPathLinkByTitleAttribute("a element");
    $this->pressEditorButton('drupallink');
    $field = $assert_session->waitForElementVisible('xpath', '//input[@name="attributes[href]"]');
    $this->assertNotEmpty($field);
    $field->setValue('https://www.drupal.org/project/drupal');
    $assert_session->elementExists('css', 'button.form-submit')->press();
    $this->getSession()->switchToIFrame('ckeditor');
    $figcaption = $assert_session->waitForElement('css', 'figcaption');
    $page = $this->getSession()->getPage();
    // Wait for AJAX refresh.
    $page->waitFor(10, function () use ($figcaption) {
      return $figcaption->find('xpath', '//a[@href="https://www.drupal.org/project/drupal"]');
    });
    $this->pressEditorButton('source');
    $source = $assert_session->elementExists('css', "textarea.cke_source");
    $value = $source->getValue();
    $this->assertContains('https://www.drupal.org/project/drupal', $value);
    $this->assertNotContains('data-cke-saved-href', $value);

    // Save the entity.
    $assert_session->buttonExists('Save')->press();

    // Verify the saved entity when viewed also contains the captioned media.
    $link = $assert_session->elementExists('css', 'figcaption > a');
    $this->assertSame('https://www.drupal.org/project/drupal', $link->getAttribute('href'));
    $this->assertSame("I'm just a poor boy, I need no sympathy!", $link->getText());

    // Edit it again, type a different caption in the widget.
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'figcaption'));
    $this->setCaption('Scaramouch, <em>Scaramouch</em>, will you do the <strong>Fandango</strong>?');

    // Erase the caption in the CKEditor Widget, verify the <figcaption> still
    // exists and contains placeholder text, then type something else.
    $this->setCaption('');
    $this->getSession()->switchToIFrame('ckeditor');
    $assert_session->elementContains('css', 'figcaption', '');
    $assert_session->elementAttributeContains('css', 'figcaption', 'data-placeholder', 'Enter caption here');
    $this->setCaption('Fin.');
    $this->getSession()->switchToIFrame('ckeditor');
    $assert_session->elementContains('css', 'figcaption', 'Fin.');
  }

  /**
   * Tests linkability of the CKEditor widget.
   *
   * @dataProvider linkabilityProvider
   */
  public function testLinkability($drupalimage_is_enabled) {
    if (!$drupalimage_is_enabled) {
      // Remove the `drupalimage` plugin's `DrupalImage` button.
      $editor = Editor::load('test_format');
      $settings = $editor->getSettings();
      $rows = $settings['toolbar']['rows'];
      foreach ($rows as $row_key => $row) {
        foreach ($row as $group_key => $group) {
          foreach ($group['items'] as $item_key => $item) {
            if ($item === 'DrupalImage') {
              unset($settings['toolbar']['rows'][$row_key][$group_key]['items'][$item_key]);
            }
          }
        }
      }
      $editor->setSettings($settings);
      $editor->save();
    }

    $this->host->body->value .= '<p>The pirate is irate.</p><p>';
    if ($drupalimage_is_enabled) {
      // Add an image with a link wrapped around it.
      $uri = $this->media->field_media_image->entity->getFileUri();
      $src = file_url_transform_relative(file_create_url($uri));
      $this->host->body->value .= '<a href="http://www.drupal.org/association"><img alt="drupalimage test image" data-entity-type="" data-entity-uuid="" src="' . $src . '" /></a></p>';
    }
    $this->host->save();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $assert_session = $this->assertSession();

    // Select the CKEditor Widget.
    $drupalmedia = $assert_session->waitForElementVisible('css', 'drupal-media');
    $this->assertNotEmpty($drupalmedia);
    $drupalmedia->click();

    // While the CKEditor Widget is selected, assert the context menu does not
    // contain link-related context menu items.
    $this->openContextMenu();
    $this->assignNameToCkeditorPanelIframe();
    $this->getSession()->switchToIFrame('panel');
    $this->assertContextMenuItemNotExists('Edit Link');
    $this->assertContextMenuItemNotExists('Unlink');
    $this->closeContextMenu();

    // While the CKEditor Widget is selected, click the "link" button.
    $this->pressEditorButton('drupallink');
    $assert_session->waitForId('drupal-modal');

    // Enter a link in the link dialog and save.
    $field = $assert_session->waitForElementVisible('xpath', '//input[@name="attributes[href]"]');
    $this->assertNotEmpty($field);
    $field->setValue('https://www.drupal.org');
    $assert_session->elementExists('css', 'button.form-submit')->press();
    $this->getSession()->switchToIFrame('ckeditor');
    $link = $assert_session->waitForElementVisible('css', 'a[href="https://www.drupal.org"]');
    $this->assertNotEmpty($link);

    // Select the CKEditor Widget again and assert the context menu now does
    // contain link-related context menu items.
    $drupalmedia = $assert_session->waitForElementVisible('css', 'drupal-media');
    $this->assertNotEmpty($drupalmedia);
    $drupalmedia->click();
    $this->openContextMenu();
    $this->getSession()->switchToIFrame('panel');
    $this->assertContextMenuItemExists('Edit Link');
    $this->assertContextMenuItemExists('Unlink');
    $this->closeContextMenu();

    // Save the entity.
    $this->getSession()->switchToIFrame();
    $assert_session->buttonExists('Save')->press();

    // Verify the saved entity when viewed also contains the linked media.
    $assert_session->elementExists('css', 'figure > a[href="https://www.drupal.org"] > .media--type-image > .field--type-image > img[src*="image-test.png"]');

    // Test that `drupallink` also still works independently: inserting a link
    // is possible.
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->pressEditorButton('drupallink');
    $assert_session->waitForId('drupal-modal');
    $field = $assert_session->waitForElementVisible('xpath', '//input[@name="attributes[href]"]');
    $this->assertNotEmpty($field);
    $field->setValue('https://wikipedia.org');
    $assert_session->elementExists('css', 'button.form-submit')->press();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $link = $assert_session->waitForElementVisible('css', 'body > a[href="https://wikipedia.org"]');
    $this->assertNotEmpty($link);
    $assert_session->elementExists('css', 'body > .cke_widget_drupalmedia > drupal-media > figure > a[href="https://www.drupal.org"]');

    // Select the CKEditor Widget again and assert the `drupalunlink` button is
    // enabled. Also assert the context menu again contains link-related context
    // menu items.
    $drupalmedia = $assert_session->waitForElementVisible('css', 'drupal-media');
    $this->assertNotEmpty($drupalmedia);
    $drupalmedia->click();
    $this->openContextMenu();
    $this->getSession()->switchToIFrame();
    $this->assertEditorButtonEnabled('drupalunlink');
    $this->assignNameToCkeditorPanelIframe();
    $this->getSession()->switchToIFrame('panel');
    $this->assertContextMenuItemExists('Edit Link');
    $this->assertContextMenuItemExists('Unlink');

    // Test that moving focus to another element causes the `drupalunlink`
    // button to become disabled and causes link-related context menu items to
    // disappear.
    $this->getSession()->switchToIFrame();
    $this->getSession()->switchToIFrame('ckeditor');
    $p = $assert_session->waitForElementVisible('xpath', "//p[contains(text(), 'The pirate is irate')]");
    $this->assertNotEmpty($p);
    $p->click();
    $this->assertEditorButtonDisabled('drupalunlink');
    $this->getSession()->switchToIFrame('panel');
    $this->assertContextMenuItemExists('Edit Link');
    $this->assertContextMenuItemExists('Unlink');

    // To switch from the context menu iframe ("panel") back to the CKEditor
    // iframe, we first have to reset to top frame.
    $this->getSession()->switchToIFrame();
    $this->getSession()->switchToIFrame('ckeditor');

    // Test that moving focus to the `drupalimage` CKEditor Widget enables the
    // `drupalunlink` button again, because it is a linked image.
    if ($drupalimage_is_enabled) {
      $drupalimage = $assert_session->waitForElementVisible('xpath', '//img[@alt="drupalimage test image"]');
      $this->assertNotEmpty($drupalimage);
      $drupalimage->click();
      $this->assertEditorButtonEnabled('drupalunlink');
      $this->getSession()->switchToIFrame('panel');
      $this->assertContextMenuItemExists('Edit Link');
      $this->assertContextMenuItemExists('Unlink');
      $this->getSession()->switchToIFrame();
      $this->getSession()->switchToIFrame('ckeditor');
    }

    // Tests the `drupalunlink` button for the `drupalmedia` CKEditor Widget.
    $drupalmedia->click();
    $this->assertEditorButtonEnabled('drupalunlink');
    $this->getSession()->switchToIFrame('panel');
    $this->assertContextMenuItemExists('Edit Link');
    $this->assertContextMenuItemExists('Unlink');
    $this->pressEditorButton('drupalunlink');
    $this->assertEditorButtonDisabled('drupalunlink');
    $this->getSession()->switchToIFrame('ckeditor');
    $assert_session->elementNotExists('css', 'figure > a[href="https://www.drupal.org"] > .media--type-image > .field--type-image > img[src*="image-test.png"]');
    $assert_session->elementExists('css', 'figure .media--type-image > .field--type-image > img[src*="image-test.png"]');
    if ($drupalimage_is_enabled) {
      // Tests the `drupalunlink` button for the `drupalimage` CKEditor Widget.
      $drupalimage->click();
      $this->assertEditorButtonEnabled('drupalunlink');
      $this->pressEditorButton('drupalunlink');
      $this->assertEditorButtonDisabled('drupalunlink');
      $this->getSession()->switchToIFrame('ckeditor');
      $assert_session->elementNotExists('css', 'p > a[href="https://www.drupal.org/association"] > img[src*="image-test.png"]');
      $assert_session->elementExists('css', 'p > img[src*="image-test.png"]');
    }
  }

  /**
   * Data Provider for ::testLinkability.
   */
  public function linkabilityProvider() {
    return [
      'linkability when `drupalimage` is enabled' => [
        TRUE,
      ],
      'linkability when `drupalimage` is disabled' => [
        FALSE,
      ],
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
    $format = FilterFormat::create([
      'format' => $this->randomMachineName(),
      'name' => $this->randomString(),
      'filters' => [
        'filter_align' => ['status' => TRUE],
        'filter_caption' => ['status' => TRUE],
        'media_embed' => ['status' => $media_embed_enabled],
      ],
    ]);
    $format->save();

    $permissions = [
      'bypass node access',
    ];
    if ($can_use_format) {
      $permissions[] = $format->getPermissionName();
    }
    $this->drupalLogin($this->drupalCreateUser($permissions));

    $text = '<drupal-media data-caption="baz" data-entity-type="media" data-entity-uuid="' . $this->media->uuid() . '"></drupal-media>';
    $route_parameters = ['filter_format' => $format->id()];
    $options = ['query' => ['text' => $text]];
    $this->drupalGet(Url::fromRoute('media.filter.preview', $route_parameters, $options));

    $assert_session = $this->assertSession();
    if ($media_embed_enabled && $can_use_format) {
      $assert_session->elementExists('css', 'img');
      $assert_session->responseContains('baz');
    }
    else {
      $assert_session->responseContains('You are not authorized to access this page.');
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
   * Tests that alignment is reflected onto the CKEditor Widget wrapper.
   */
  public function testAlignmentClasses() {
    $alignments = [
      'right',
      'left',
      'center',
    ];
    $assert_session = $this->assertSession();
    foreach ($alignments as $alignment) {
      $this->host->body->value = '<drupal-media data-align="' . $alignment . '" data-entity-type="media" data-entity-uuid="' . $this->media->uuid() . '"></drupal-media>';
      $this->host->save();

      // The upcasted CKEditor Widget's wrapper must get an `align-*` class.
      $this->drupalGet($this->host->toUrl('edit-form'));
      $this->waitForEditor();
      $this->assignNameToCkeditorIframe();
      $this->getSession()->switchToIFrame('ckeditor');
      $wrapper = $assert_session->waitForElementVisible('css', '.cke_widget_drupalmedia', 2000);
      $this->assertNotEmpty($wrapper);
      $this->assertTrue($wrapper->hasClass('align-' . $alignment));
    }
  }

  /**
   * Gets the transfer size of the last preview request.
   *
   * @return int
   */
  protected function getLastPreviewRequestTransferSize() {
    $this->getSession()->switchToIFrame();
    $javascript = <<<JS
(function(){
  return window.performance
    .getEntries()
    .filter(function (entry) {
      return entry.initiatorType == 'xmlhttprequest' && entry.name.indexOf('/media/test_format/preview') !== -1;
    })
    .pop()
    .transferSize;
})()
JS;
    return $this->getSession()->evaluateScript($javascript);
  }

  /**
   * Set the text of the editable caption to the given text.
   *
   * @param string $text
   *   The text to set in the caption.
   */
  protected function setCaption($text) {
    $this->getSession()->switchToIFrame();
    $select_and_edit_caption = "var editor = CKEDITOR.instances['edit-body-0-value'];
       var figcaption = editor.widgets.getByElement(editor.editable().findOne('figcaption'));
       figcaption.editables.caption.setData('" . $text . "')";
    $this->getSession()->executeScript($select_and_edit_caption);
  }

  /**
   * Assigns a name to the CKEditor iframe.
   *
   * @see \Behat\Mink\Session::switchToIFrame()
   */
  protected function assignNameToCkeditorIframe() {
    $javascript = <<<JS
(function(){
  document.getElementsByClassName('cke_wysiwyg_frame')[0].id = 'ckeditor';
})()
JS;
    $this->getSession()->evaluateScript($javascript);
  }

  /**
   * Assigns a name to the CKEditor context menu iframe.
   *
   * Note that this iframe doesn't appear until context menu appears.
   *
   * @see \Behat\Mink\Session::switchToIFrame()
   */
  protected function assignNameToCkeditorPanelIframe() {
    $javascript = <<<JS
(function(){
  document.getElementsByClassName('cke_panel_frame')[0].id = 'panel';
})()
JS;
    $this->getSession()->evaluateScript($javascript);
  }

  /**
   * Clicks a CKEditor button.
   *
   * @param string $name
   *   The name of the button, such as drupalink, source, etc.
   */
  protected function pressEditorButton($name) {
    $this->getSession()->switchToIFrame();
    $button = $this->assertSession()->waitForElementVisible('css', 'a.cke_button__' . $name);
    $this->assertNotEmpty($button);
    $button->click();
  }

  /**
   * Waits for a CKEditor button and returns it when available and visible.
   *
   * @param string $name
   *   The name of the button, such as drupalink, source, etc.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The page element node if found, NULL if not.
   */
  protected function getEditorButton($name) {
    $this->getSession()->switchToIFrame();
    $button = $this->assertSession()->waitForElementVisible('css', 'a.cke_button__' . $name);
    $this->assertNotEmpty($button);

    return $button;
  }

  /**
   * Asserts a CKEditor button is disabled.
   *
   * @param string $name
   *   The name of the button, such as `drupallink`, `source`, etc.
   */
  protected function assertEditorButtonDisabled($name) {
    $button = $this->getEditorButton($name);
    $this->assertTrue($button->hasClass('cke_button_disabled'));
    $this->assertSame('true', $button->getAttribute('aria-disabled'));
  }

  /**
   * Asserts a CKEditor button is enabled.
   *
   * @param string $name
   *   The name of the button, such as `drupallink`, `source`, etc.
   */
  protected function assertEditorButtonEnabled($name) {
    $button = $this->getEditorButton($name);
    $this->assertFalse($button->hasClass('cke_button_disabled'));
    $this->assertSame('false', $button->getAttribute('aria-disabled'));
  }

  /**
   * Waits for CKEditor to initialize.
   *
   * @param string $instance_id
   *   The CKEditor instance ID.
   * @param int $timeout
   *   (optional) Timeout in milliseconds, defaults to 10000.
   */
  protected function waitForEditor($instance_id = 'edit-body-0-value', $timeout = 10000) {
    $condition = <<<JS
      (function() {
        return (
          typeof CKEDITOR !== 'undefined'
          && typeof CKEDITOR.instances["$instance_id"] !== 'undefined'
          && CKEDITOR.instances["$instance_id"].instanceReady
        );
      }());
JS;

    $this->getSession()->wait($timeout, $condition);
  }

  /**
   * Opens the context menu for the currently selected widget.
   *
   * @param string $instance_id
   *   The CKEditor instance ID.
   */
  protected function openContextMenu($instance_id = 'edit-body-0-value') {
    $this->getSession()->switchToIFrame();
    $script = <<<JS
      (function() {
        var editor = CKEDITOR.instances["$instance_id"];
        editor.contextMenu.open(editor.widgets.selected[0].element);
      }());
JS;
    $this->getSession()->executeScript($script);
  }

  /**
   * Asserts that a context menu item exists by aria-label attribute.
   *
   * @param string $label
   *   The `aria-label` attribute value of the context menu item.
   */
  protected function assertContextMenuItemExists($label) {
    $this->assertSession()->elementExists('xpath', '//a[@aria-label="' . $label . '"]');
  }

  /**
   * Asserts that a context menu item does not exist by aria-label attribute.
   *
   * @param string $label
   *   The `aria-label` attribute value of the context menu item.
   */
  protected function assertContextMenuItemNotExists($label) {
    $this->assertSession()->elementNotExists('xpath', '//a[@aria-label="' . $label . '"]');
  }

  /**
   * Closes the open context menu.
   *
   * @param string $instance_id
   *   The CKEditor instance ID.
   */
  protected function closeContextMenu($instance_id = 'edit-body-0-value') {
    $this->getSession()->switchToIFrame();
    $script = <<<JS
      (function() {
        var editor = CKEDITOR.instances["$instance_id"];
        editor.contextMenu.hide();
      }());
JS;
    $this->getSession()->executeScript($script);
  }

  /**
   * Clicks a link in the editor's path links with the given title text.
   *
   * @param string $text
   *   The title attribute of the link to click.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function clickPathLinkByTitleAttribute($text) {
    $this->getSession()->switchToIFrame();
    $selector = '//span[@id="cke_1_path"]//a[@title="' . $text . '"]';
    $this->assertSession()->elementExists('xpath', $selector)->click();
  }

}
