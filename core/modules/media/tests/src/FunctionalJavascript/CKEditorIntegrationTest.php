<?php

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\media\Entity\Media;
use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * @coversDefaultClass \Drupal\media\Plugin\CKEditorPlugin\DrupalMedia
 * @group media
 */
class CKEditorIntegrationTest extends WebDriverTestBase {

  use CKEditorTestTrait;
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
   * The character code for the return key.
   *
   * @var int
   */
  const RETURN_KEY = 13;

  /**
   * The character code for the space bar.
   *
   * @var int
   */
  const SPACE_BAR = 32;

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
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    // Test that setting caption to blank string doesn't break 'Edit media'
    // button.
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace('data-caption="baz"', 'data-caption=""', $original_value);
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForButton('Edit media'));
    $assert_session->elementContains('css', 'figcaption', '');
    $assert_session->elementAttributeContains('css', 'figcaption', 'data-placeholder', 'Enter caption here');

    // Restore caption in saved body value.
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace('data-caption=""', 'data-caption="baz"', $original_value);
    $this->host->save();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    // Assert that figcaption element exists within the drupal-media element.
    $this->assertNotEmpty($figcaption = $assert_session->waitForElement('css', 'drupal-media figcaption'));
    $this->assertSame('baz', $figcaption->getHtml());

    // Test that disabling the caption in the metadata dialog removes it
    // from the drupal-media element.
    $this->openMetadataDialogWithKeyPress(static::SPACE_BAR);
    $page->uncheckField('hasCaption');
    $this->submitDialog();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($drupal_media = $assert_session->waitForElementVisible('css', 'drupal-media'));

    // Wait for element to update without figcaption.
    $result = $page->waitFor(10, function () use ($drupal_media) {
      return empty($drupal_media->find('css', 'figcaption'));
    });
    // Will be true if no figcaption exists within the drupal-media element.
    $this->assertTrue($result);

    // Test that enabling the caption in the metadata dialog adds an editable
    // caption to the embedded media.
    $this->openMetadataDialogWithKeyPress(static::SPACE_BAR);
    $page->checkField('hasCaption');
    $this->submitDialog();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($drupal_media = $assert_session->waitForElementVisible('css', 'drupal-media figcaption'));

    // Type into the widget's caption element.
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
   * Test the EditorMediaDialog's form elements' #access logic.
   */
  public function testDialogAccess() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');

    // Enable `filter_html` without "alt", "data-align" or "data-caption"
    // attributes added to the drupal-media tag.
    $allowed_html = "<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li> <dl> <dt> <dd> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id> <drupal-media data-entity-type data-entity-uuid data-view-mode>";
    $filter_format = FilterFormat::load('test_format');
    $filter_format->setFilterConfig('filter_html', [
      'status' => TRUE,
      'settings' => [
        'allowed_html' => $allowed_html,
      ],
    ])->save();

    // Test the validation of attributes in the dialog.  If the alt,
    // data-caption, and data-align attributes are not set on the drupal-media
    // tag, the respective fields shouldn't display in the dialog.
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'drupal-media', 2000));
    $page->pressButton('Edit media');
    $this->waitForMetadataDialog();
    $assert_session->fieldNotExists('attributes[alt]');
    $assert_session->fieldNotExists('attributes[align]');
    $assert_session->fieldNotExists('hasCaption');
    $assert_session->pageTextContains('There is nothing to configure for this media.');
    // The edit link for the format shouldn't appear unless the user has
    // permission to edit the text format.
    $assert_session->pageTextNotContains('Edit the text format Test format to modify the attributes that can be overridden.');
    $page->pressButton('Close');
    $this->getSession()->switchToIFrame('ckeditor');

    // Now test the same thing with a user who has access to edit text formats.
    // An extra message containing a link to edit the text format should
    // appear.
    Role::load(RoleInterface::AUTHENTICATED_ID)
      ->grantPermission('administer filters')
      ->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'drupal-media', 2000));
    $page->pressButton('Edit media');
    $this->waitForMetadataDialog();
    $assert_session->fieldNotExists('attributes[alt]');
    $assert_session->fieldNotExists('attributes[align]');
    $assert_session->fieldNotExists('hasCaption');
    $assert_session->pageTextContains('There is nothing to configure for this media. Edit the text format Test format to modify the attributes that can be overridden.');
    $assert_session->linkExists('Edit the text format Test format');
    $page->pressButton('Close');
    $this->getSession()->switchToIFrame('ckeditor');

    // Now test that adding the attributes to the allowed HTML will allow
    // the fields to display in the dialog.
    $allowed_html = str_replace('<drupal-media data-entity-type data-entity-uuid data-view-mode>', '<drupal-media alt data-align data-caption data-entity-type data-entity-uuid data-view-mode>', $allowed_html);
    $filter_format->setFilterConfig('filter_html', [
      'status' => TRUE,
      'settings' => [
        'allowed_html' => $allowed_html,
      ],
    ])->save();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'drupal-media', 2000));
    $page->pressButton('Edit media');
    $this->waitForMetadataDialog();
    $assert_session->fieldExists('attributes[alt]');
    $assert_session->fieldExists('attributes[data-align]');
    $assert_session->fieldExists('hasCaption');
    $page->pressButton('Close');
    $this->getSession()->switchToIFrame('ckeditor');

    // Test that setting the media image field to not display alt field also
    // disables it in the dialog.
    FieldConfig::loadByName('media', 'image', 'field_media_image')
      ->setSetting('alt_field', FALSE)
      ->save();
    // @todo This manual cache clearing should not be necessary, fix in
    // https://www.drupal.org/project/drupal/issues/3076544
    $this->container
      ->get('cache.discovery')
      ->delete('entity_bundle_field_definitions:media:image:en');
    // Wait for preview.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'drupal-media', 2000));
    $page->pressButton('Edit media');
    $this->waitForMetadataDialog();
    $assert_session->fieldNotExists('attributes[alt]');
    $assert_session->fieldExists('attributes[data-align]');
    $assert_session->fieldExists('hasCaption');
    $page->pressButton('Close');
    $this->getSession()->switchToIFrame('ckeditor');

    // Test that enabling the alt field on the media image field restores
    // the field in the dialog.
    FieldConfig::loadByName('media', 'image', 'field_media_image')
      ->setSetting('alt_field', TRUE)
      ->save();
    // @todo This manual cache clearing should not be necessary, fix in
    // https://www.drupal.org/project/drupal/issues/3076544
    $this->container
      ->get('cache.discovery')
      ->delete('entity_bundle_field_definitions:media:image:en');
    // Wait for preview.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'drupal-media', 2000));
    $page->pressButton('Edit media');
    $this->waitForMetadataDialog();
    $assert_session->fieldExists('attributes[alt]');
    $assert_session->fieldExists('attributes[data-align]');
    $assert_session->fieldExists('hasCaption');
    $page->pressButton('Close');
    $this->getSession()->switchToIFrame('ckeditor');

    // Test that disabling `filter_caption` and `filter_align` disables the
    // respective fields in the dialog.
    $filter_format
      ->setFilterConfig('filter_caption', [
        'status' => FALSE,
      ])->setFilterConfig('filter_align', [
        'status' => FALSE,
      ])->save();
    // Wait for preview.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'drupal-media', 2000));
    $page->pressButton('Edit media');
    $this->waitForMetadataDialog();
    $assert_session->fieldNotExists('attributes[data-align]');
    $assert_session->fieldNotExists('hasCaption');
    // The alt field should be unaffected.
    $assert_session->fieldExists('attributes[alt]');
    $page->pressButton('Close');
    $this->getSession()->switchToIFrame('ckeditor');

    // Test that enabling the two filters restores the fields in the dialog.
    $filter_format
      ->setFilterConfig('filter_caption', [
        'status' => TRUE,
      ])->setFilterConfig('filter_align', [
        'status' => TRUE,
      ])->save();
    // Wait for preview.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'drupal-media', 2000));
    $page->pressButton('Edit media');
    $this->waitForMetadataDialog();
    $assert_session->fieldExists('attributes[data-align]');
    $assert_session->fieldExists('hasCaption');
    $assert_session->pageTextNotContains('There is nothing to configure for this media. Edit the text format Test format to modify the attributes that can be overridden.');
    // The alt field should be unaffected.
    $assert_session->fieldExists('attributes[alt]');
  }

  /**
   * Tests the EditorMediaDialog can set the alt attribute.
   */
  public function testAlt() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    // Test that by default no alt attribute is present on the drupal-media
    // element.
    $this->pressEditorButton('source');
    $this->assertSourceAttributeSame('alt', NULL);
    $this->leaveSourceMode();
    // Test that the preview shows the alt value from the media field's
    // alt text.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'drupal-media img[alt*="default alt"]'));
    $this->openMetadataDialogWithKeyPress(static::RETURN_KEY);
    // Assert that the placeholder is set to the value of the media field's
    // alt text.
    $assert_session->elementAttributeContains('named', ['field', 'attributes[alt]'], 'placeholder', 'default alt');

    // Fill in the alt field, submit and return to CKEditor.
    $who_is_zartan = 'Zartan is the leader of the Dreadnoks.';
    $page->fillField('attributes[alt]', $who_is_zartan);
    $this->submitDialog();
    $this->getSession()->switchToIFrame('ckeditor');

    // Assert that the img within the media embed within the CKEditor contains
    // the overridden alt text set in the dialog.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'drupal-media img[alt*="' . $who_is_zartan . '"]'));

    // Test that the downcast drupal-media element now has the alt attribute
    // entered in the dialog.
    $this->pressEditorButton('source');
    $this->assertSourceAttributeSame('alt', $who_is_zartan);

    // The alt field should now display the override instead of the default.
    $this->leaveSourceMode();
    $this->openMetadataDialog();
    $assert_session->fieldValueEquals('attributes[alt]', $who_is_zartan);

    // Test the process again with a different alt text to make sure it works
    // the second time around.
    $cobra_commander_bio = 'The supreme leader of the terrorist organization Cobra';
    // Set the alt field to the new alt text.
    $page->fillField('attributes[alt]', $cobra_commander_bio);
    $this->submitDialog();
    $this->getSession()->switchToIFrame('ckeditor');
    // Assert that the img within the media embed preview
    // within the CKEditor contains the overridden alt text set in the dialog.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'drupal-media img[alt*="' . $cobra_commander_bio . '"]'));

    // Test that the downcast drupal-media element now has the alt attribute
    // entered in the dialog.
    $this->pressEditorButton('source');
    $this->assertSourceAttributeSame('alt', $cobra_commander_bio);

    // The default value of the alt field should now display the override
    // instead of the value on the media image field.
    $this->leaveSourceMode();
    $this->openMetadataDialogWithKeyPress(static::RETURN_KEY);
    $assert_session->fieldValueEquals('attributes[alt]', $cobra_commander_bio);

    // Test that setting alt value to two double quotes will signal to the
    // MediaEmbed filter to unset the attribute on the media image field.
    // We intentionally add a space space after the two double quotes to test
    // the string is trimmed to two quotes.
    $page->fillField('attributes[alt]', '"" ');
    $this->submitDialog();
    $this->getSession()->switchToIFrame('ckeditor');
    // Verify that the two double quote empty alt indicator ('""') set in
    // the dialog has successfully resulted in a media image field with the
    // alt attribute present but without a value.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'drupal-media img[alt=""]'));

    // Test that the downcast drupal-media element's alt attribute now has the
    // empty string indicator.
    $this->pressEditorButton('source');
    $this->assertSourceAttributeSame('alt', '""');

    // Test that setting alt to back to an empty string within the dialog will
    // restore the default alt value saved in to the media image field of the
    // media item.
    $this->leaveSourceMode();
    $this->openMetadataDialog();
    $page->fillField('attributes[alt]', '');
    $this->submitDialog();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'drupal-media img[alt*="default alt"]'));

    // Test that the downcast drupal-media element no longer has an alt
    // attribute.
    $this->pressEditorButton('source');
    $this->assertSourceAttributeSame('alt', NULL);
  }

  /**
   * Test that dialog loads appropriate translation's alt text.
   */
  public function testTranslationAlt() {
    \Drupal::service('module_installer')->install(['language', 'content_translation']);
    $this->resetAll();
    ConfigurableLanguage::create(['id' => 'fr'])->save();
    ContentLanguageSettings::loadByEntityTypeBundle('media', 'image')
      ->setDefaultLangcode('en')
      ->setLanguageAlterable(TRUE)
      ->save();
    $media = Media::create([
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
    $media->save();
    $media_fr = $media->addTranslation('fr');
    $media_fr->name = "Tatou poilu hurlant";
    $media_fr->field_media_image->setValue([
      [
        'target_id' => '1',
        'alt' => "texte alternatif par défaut",
        'title' => "titre alternatif par défaut",
      ],
    ]);
    $media_fr->save();

    ContentLanguageSettings::loadByEntityTypeBundle('node', 'blog')
      ->setDefaultLangcode('en')
      ->setLanguageAlterable(TRUE)
      ->save();

    $host = $this->createNode([
      'type' => 'blog',
      'title' => 'Animals with strange names',
      'body' => [
        'value' => '<drupal-media data-caption="baz" data-entity-type="media" data-entity-uuid="' . $media->uuid() . '"></drupal-media>',
        'format' => 'test_format',
      ],
    ]);
    $host->save();

    $translation = $host->addTranslation('fr');
    $translation->title = 'Animaux avec des noms étranges';
    $translation->body->value = $host->body->value;
    $translation->body->format = $host->body->format;
    $translation->save();

    Role::load(RoleInterface::AUTHENTICATED_ID)
      ->grantPermission('translate any entity')
      ->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet('/fr/node/' . $host->id() . '/edit');
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');

    // Test that the default alt attribute displays without an override.
    $this->assertNotEmpty($assert_session->waitForElementVisible('xpath', '//img[contains(@alt, "texte alternatif par défaut")]'));
    $page->pressButton('Edit media');
    $this->waitForMetadataDialog();
    // Assert that the placeholder is set to the value of the media field's
    // alt text.
    $assert_session->elementAttributeContains('named', ['field', 'attributes[alt]'], 'placeholder', 'texte alternatif par défaut');

    // Fill in the alt field in the dialog.
    $qui_est_zartan = 'Zartan est le chef des Dreadnoks.';
    $page->fillField('attributes[alt]', $qui_est_zartan);
    $this->submitDialog();
    $this->getSession()->switchToIFrame('ckeditor');

    // Assert that the img within the media embed within CKEditor contains
    // the overridden alt text set in the dialog.
    $this->assertNotEmpty($assert_session->waitForElementVisible('xpath', '//img[contains(@alt, "' . $qui_est_zartan . '")]'));
    $this->getSession()->switchToIFrame();
    $page->pressButton('Save');
    $assert_session->elementExists('xpath', '//img[contains(@alt, "' . $qui_est_zartan . '")]');
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
   * Tests alignment integration.
   *
   * Tests that alignment is reflected onto the CKEditor Widget wrapper, that
   * the EditorMediaDialog allows altering the alignment and that the changes
   * are reflected on the widget and downcast drupal-media tag.
   */
  public function testAlignment() {
    $assert_session = $this->assertSession();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    // Assert that setting the data-align property in the dialog adds the
    // `align-right', `align-left` or `align-center' class on the widget,
    // caption figure and drupal-media tag.
    $alignments = [
      'right',
      'left',
      'center',
    ];
    foreach ($alignments as $alignment) {
      $this->fillFieldInMetadataDialogAndSubmit('attributes[data-align]', $alignment);
      // Now verify the result.
      $selector = sprintf('drupal-media[data-align="%s"] .caption-drupal-media.align-%s', $alignment, $alignment);
      $this->assertNotEmpty($assert_session->waitForElementVisible('css', $selector, 2000));
      // Assert that the resultant downcast drupal-media element has the proper
      // `data-align` attribute.
      $this->pressEditorButton('source');
      $this->assertSourceAttributeSame('data-align', $alignment);
      $this->leaveSourceMode();
    }
    // Test that setting the "Align" field to "none" in the dialog will
    // remove the attribute from the drupal-media element in the CKEditor.
    $this->fillFieldInMetadataDialogAndSubmit('attributes[data-align]', 'none');

    // Assert that neither the widget nor the caption figure have alignment
    // classes.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.caption-drupal-media:not([class*="align-"])', 2000));
    $assert_session->elementExists('css', '.cke_widget_drupalmedia:not([class*="align-"])');
    // Assert that the resultant downcast <drupal-media> tag has no data-align
    // attribute.
    $this->pressEditorButton('source');
    $this->assertNotEmpty($drupal_media = $this->getDrupalMediaFromSource());
    $this->assertFalse($drupal_media->hasAttribute('data-align'));
  }

  /**
   * Waits for the form that allows editing metadata.
   *
   * @see \Drupal\media\Form\EditorMediaDialog
   */
  protected function waitForMetadataDialog() {
    $page = $this->getSession()->getPage();
    $this->getSession()->switchToIFrame();
    // Wait for the dialog to open.
    $result = $page->waitFor(10, function ($page) {
      $metadata_editor = $page->find('css', 'form.editor-media-dialog');
      return !empty($metadata_editor);
    });
    $this->assertTrue($result);
  }

  /**
   * Fills in a field in the metadata dialog for an embedded media item.
   *
   * This method assumes that the calling code has already switched into the
   * CKEditor iframe.
   *
   * @param string $locator
   *   The field ID, name, or label.
   * @param string $value
   *   The value to set on the field.
   */
  protected function fillFieldInMetadataDialogAndSubmit($locator, $value) {
    // Wait for the drupal-media which holds the "Edit media" button which
    // opens the dialog.
    $this->openMetadataDialog();
    $this->getSession()->getPage()->fillField($locator, $value);
    $this->submitDialog();
    // Since ::waitforMetadataDialog() switches back to the main iframe, we'll
    // need to switch back.
    $this->getSession()->switchToIFrame('ckeditor');
  }

  /**
   * Clicks the `Edit media` button and waits for the metadata dialog.
   *
   * This method assumes that the calling code has already switched into the
   * CKEditor iframe.
   */
  protected function openMetadataDialog() {
    $this->assertNotEmpty($embedded_media = $this->assertSession()->waitForElementVisible('css', 'drupal-media'));
    $embedded_media->pressButton('Edit media');
    $this->waitForMetadataDialog();
  }

  /**
   * Focuses on `Edit media` button and presses the given key.
   *
   * @param int $char
   *   The character code to press.
   *
   *   This method assumes that the calling code has already switched into the
   *   CKEditor iframe.
   */
  protected function openMetadataDialogWithKeyPress($char) {
    $this->assertNotEmpty($button = $this->assertSession()->waitForButton('Edit media'));
    $button->keyDown($char);
    $this->waitForMetadataDialog();
  }

  /**
   * Leaves source mode and returns to the CKEditor iframe.
   */
  protected function leaveSourceMode() {
    // Press the source button again to leave source mode.
    $this->pressEditorButton('source');
    // Having entered source mode means we need to reassign an id to the
    // CKEditor iframe.
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
  }

  /**
   * Verifies value of an attribute on the downcast <drupal-media> element.
   *
   * Assumes CKEditor is in source mode.
   *
   * @param string $attribute
   *   The attribute to check.
   * @param mixed $value
   *   Either a string value or if NULL, asserts that <drupal-media> element
   *   doesn't have the attribute.
   */
  protected function assertSourceAttributeSame($attribute, $value) {
    $this->assertNotEmpty($drupal_media = $this->getDrupalMediaFromSource());
    if ($value === NULL) {
      $this->assertFalse($drupal_media->hasAttribute($attribute));
    }
    else {
      $this->assertSame($value, $drupal_media->getAttribute($attribute));
    }
  }

  /**
   * Closes and submits the metadata dialog.
   */
  protected function submitDialog() {
    $this->assertNotEmpty($dialog_buttons = $this->assertSession()->elementExists('css', 'div.ui-dialog-buttonpane'));
    $dialog_buttons->pressButton('Save');
  }

  /**
   * Closes the metadata dialog.
   */
  protected function closeDialog() {
    $page = $this->getSession()->getPage();
    $page->pressButton('Close');
    $result = $page->waitFor(10, function ($page) {
      $metadata_editor = $page->find('css', 'form.editor-media-dialog');
      return empty($metadata_editor);
    });
    $this->assertTrue($result);
  }

  /**
   * Gets the transfer size of the last preview request.
   *
   * @return int
   *   The size of the bytes transferred.
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
   * Sets the text of the editable caption to the given text.
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
   */
  protected function clickPathLinkByTitleAttribute($text) {
    $this->getSession()->switchToIFrame();
    $selector = '//span[@id="cke_1_path"]//a[@title="' . $text . '"]';
    $this->assertSession()->elementExists('xpath', $selector)->click();
  }

  /**
   * Parses the <drupal-media> element from CKEditor's "source" view.
   *
   * Assumes CKEditor is in source mode.
   *
   * @return \DOMNode|null
   *   The drupal-media element or NULL if it can't be found.
   */
  protected function getDrupalMediaFromSource() {
    $value = $this->assertSession()
      ->elementExists('css', 'textarea.cke_source')
      ->getValue();
    $dom = Html::load($value);
    $xpath = new \DOMXPath($dom);
    $list = $xpath->query('//drupal-media');
    return count($list) > 0 ? $list[0] : NULL;
  }

}
