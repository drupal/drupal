<?php

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Database\Database;
use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Symfony\Component\Validator\ConstraintViolation;

// cspell:ignore layercake

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
   * The second sample Media entity to embed used in one of the tests.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $mediaFile;

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
    'media_library',
    'ckeditor5_test',
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

    EntityViewMode::create([
      'id' => 'media.view_mode_1',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 1',
    ])->save();
    EntityViewMode::create([
      'id' => 'media.22222',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 2 has Numeric ID',
    ])->save();
    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <em> <a href> <drupal-media data-entity-type data-entity-uuid data-align data-view-mode data-caption alt>',
          ],
        ],
        'filter_align' => ['status' => TRUE],
        'filter_caption' => ['status' => TRUE],
        'media_embed' => [
          'status' => TRUE,
          'settings' => [
            'default_view_mode' => 'view_mode_1',
            'allowed_view_modes' => [
              'view_mode_1' => 'view_mode_1',
              '22222' => '22222',
            ],
            'allowed_media_types' => [],
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
            'sourceEditing',
            'link',
            'bold',
            'italic',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [],
          ],
          'media_media' => [
            'allow_view_mode_override' => TRUE,
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

    $this->createMediaType('file', ['id' => 'file']);
    File::create([
      'uri' => $this->getTestFiles('text')[0]->uri,
    ])->save();
    $this->mediaFile = Media::create([
      'bundle' => 'file',
      'name' => 'Information about screaming hairy armadillo',
      'field_media_file' => [
        [
          'target_id' => 2,
        ],
      ],
    ]);
    $this->mediaFile->save();

    // Set created media types for each view mode.
    EntityViewDisplay::create([
      'id' => 'media.image.view_mode_1',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'bundle' => 'image',
      'mode' => 'view_mode_1',
    ])->save();
    EntityViewDisplay::create([
      'id' => 'media.image.22222',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'bundle' => 'image',
      'mode' => '22222',
    ])->save();

    // Create a sample host entity to embed media in.
    $this->drupalCreateContentType(['type' => 'blog']);
    $this->host = $this->createNode([
      'type' => 'blog',
      'title' => 'Animals with strange names',
      'body' => [
        'value' => '<drupal-media data-entity-type="media" data-entity-uuid="' . $this->media->uuid() . '" data-caption="baz"></drupal-media>',
        'format' => 'test_format',
      ],
    ]);
    $this->host->save();

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that `<drupal-media>` is converted into a block element.
   */
  public function testConversion() {
    // Wrap the `<drupal-media>` markup in a `<p>`.
    $original_value = $this->host->body->value;
    $this->host->body->value = '<p>foo' . $original_value . '</p>';
    $this->host->save();

    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]', 1000);
    $editor_html = $this->getEditorDataAsHtmlString();
    // Observe that `<drupal-media>` was moved into its own block element.
    $this->assertEquals('<p>foo</p>' . $original_value, str_replace('&nbsp;', '', $editor_html));
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
   * Tests that arbitrary attributes are allowed via GHS.
   */
  public function testMediaArbitraryHtml() {
    $assert_session = $this->assertSession();

    $editor = Editor::load('test_format');
    $settings = $editor->getSettings();

    // Allow the data-foo attribute in drupal-media via GHS. Also, add support
    // for div's with data-foo attribute to ensure that drupal-media elements
    // can be wrapped with other block elements.
    $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags'] = ['<drupal-media data-foo>', '<div data-bar>'];
    $editor->setSettings($settings);
    $editor->save();

    $filter_format = $editor->getFilterFormat();
    $filter_format->setFilterConfig('filter_html', [
      'status' => TRUE,
      'settings' => [
        'allowed_html' => '<p> <br> <strong> <em> <a href> <drupal-media data-entity-type data-entity-uuid data-align data-caption alt data-foo data-view-mode> <div data-bar>',
      ],
    ]);
    $filter_format->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));

    // Add data-foo use to an existing drupal-media tag.
    $original_value = $this->host->body->value;
    $this->host->body->value = '<div data-bar="baz">' . str_replace('drupal-media', 'drupal-media data-foo="bar" ', $original_value) . '</div>';
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));

    // Confirm data-foo is present in the drupal-media preview.
    $this->assertNotEmpty($upcasted_media = $assert_session->waitForElementVisible('css', '.ck-widget.drupal-media'));
    $this->assertFalse($upcasted_media->hasAttribute('data-foo'));
    $this->assertNotEmpty($preview = $assert_session->waitForElementVisible('css', '.ck-widget.drupal-media > [data-drupal-media-preview="ready"] > .media', 30000));
    $this->assertEquals('bar', $preview->getAttribute('data-foo'));

    // Confirm that the media is wrapped by the div on the editing view.
    $assert_session->elementExists('css', 'div[data-bar="baz"] > .drupal-media');

    // Confirm data-foo is not stripped from source.
    $this->assertSourceAttributeSame('data-foo', 'bar');

    // Confirm that drupal-media is wrapped by the div.
    $editor_dom = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($editor_dom->query('//div[@data-bar="baz"]/drupal-media'));
  }

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
      function (ConstraintViolation $v) {
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
    $this->host->body->value = '<div data-bar="baz"><a href="https://drupal.org" data-foo="bar">' . $original_value . '</a></div>';
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));

    // Confirm data-foo is present in the editing view.
    $this->assertNotEmpty($link = $assert_session->waitForElementVisible('css', 'a[href="https://drupal.org"]'));
    $this->assertEquals('bar', $link->getAttribute('data-foo'));

    // Confirm that the media is wrapped by the div on the editing view.
    $assert_session->elementExists('css', 'div[data-bar="baz"] > .drupal-media > a[href="https://drupal.org"] > div[data-drupal-media-preview]');

    // Confirm that drupal-media is wrapped by the div and a, and that GHS has
    // retained arbitrary HTML allowed by source editing.
    $editor_dom = new \DOMXPath($this->getEditorDataAsDom());
    $this->assertNotEmpty($editor_dom->query('//div[@data-bar="baz"]/a[@data-foo="bar"]/drupal-media'));
  }

  /**
   * Tests that failed media embed preview requests inform the end user.
   */
  public function testErrorMessages() {
    // This test currently frequently causes the SQLite database to lock, so
    // skip the test on SQLite until the issue can be resolved.
    // @todo https://www.drupal.org/project/drupal/issues/3273626
    if (Database::getConnection()->driver() === 'sqlite') {
      $this->markTestSkipped('Test frequently causes a locked database on SQLite');
    }

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
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    // Test that setting caption to blank string doesn't break 'Edit media'
    // button.
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace('data-caption="baz"', 'data-caption=""', $original_value);
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    // Wait for the media preview to load.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    $assert_session->elementExists('css', '[data-drupal-media-preview][aria-label="Screaming hairy armadillo"]');
    $assert_session->elementContains('css', 'figcaption', '');
    $assert_session->elementAttributeContains('css', 'figcaption', 'data-placeholder', 'Enter media caption');

    // Test if you leave the caption blank, but change another attribute,
    // such as the alt text, the editable caption is still there and the edit
    // button still exists.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    // Click the "Override media image alternative text" button.
    $this->getBalloonButton('Override media image alternative text')->click();
    $this->assertVisibleBalloon('.ck-media-alternative-text-form');
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-media-alternative-text-form input[type=text]');

    // Fill in the alt field and submit.
    $alt_override_input->setValue('Gold star for robot boy.');
    $this->getBalloonButton('Save')->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.drupal-media img[alt*="Gold star for robot boy."]'));
    $this->assertEquals('', $assert_session->waitForElement('css', '.drupal-media figcaption')->getText());
    $assert_session->elementAttributeContains('css', '.drupal-media figcaption', 'data-placeholder', 'Enter media caption');

    // Restore caption in saved body value.
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace('data-caption=""', 'data-caption="baz"', $original_value);
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    $this->assertNotEmpty($figcaption = $assert_session->waitForElement('css', '.drupal-media figcaption'));
    $this->assertSame('baz', $figcaption->getHtml());

    // Ensure that the media contextual toolbar is visible when figcaption is
    // selected.
    $this->selectTextInsideElement('.drupal-media figcaption');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $expected_buttons = [
      'Toggle caption off',
      'Link media',
      'Override media image alternative text',
      // Check only one of the element style buttons since that is sufficient
      // for confirming that element style buttons are visible in the toolbar.
      'Break text',
    ];
    foreach ($expected_buttons as $expected_button) {
      $this->assertNotEmpty($this->getBalloonButton($expected_button));
    }

    // Ensure that caption can be toggled off from the toolbar.
    $this->getBalloonButton('Toggle caption off')->click();
    $assert_session->assertNoElementAfterWait('css', 'figcaption');

    // Ensure that caption can be toggled on from the toolbar.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('Toggle caption on')->click();
    $this->assertNotEmpty($figcaption = $assert_session->waitForElementVisible('css', '.drupal-media figcaption'));

    // Ensure that the media contextual toolbar is visible after toggling
    // caption on.
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');

    // Type into the widget's caption element.
    $this->selectTextInsideElement('.drupal-media figcaption');
    $figcaption->setValue('Llamas are the most awesome ever');
    $editor_dom = $this->getEditorDataAsDom();
    $this->assertEquals('Llamas are the most awesome ever', $editor_dom->getElementsByTagName('drupal-media')->item(0)->getAttribute('data-caption'));

    // Ensure that the caption can be changed to bold.
    $this->assertNotEmpty($figcaption = $assert_session->waitForElement('css', '.drupal-media figcaption'));
    $this->selectTextInsideElement('.drupal-media figcaption');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.drupal-media figcaption.ck-editor__nested-editable'));
    $this->pressEditorButton('Bold');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.drupal-media figcaption > strong'));
    $this->assertEquals('<strong>Llamas are the most awesome ever</strong>', $figcaption->getHtml());
    $editor_dom = $this->getEditorDataAsDom();
    $this->assertEquals('<strong>Llamas are the most awesome ever</strong>', $editor_dom->getElementsByTagName('drupal-media')->item(0)->getAttribute('data-caption'));

    // Ensure that bold can be removed from the caption.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.drupal-media figcaption > strong'));
    $this->selectTextInsideElement('.drupal-media figcaption > strong');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.drupal-media figcaption.ck-editor__nested-editable'));
    $this->pressEditorButton('Bold');
    $this->assertTrue($assert_session->waitForElementRemoved('css', '.drupal-media figcaption > strong'));
    $this->assertNotEmpty($figcaption = $assert_session->waitForElement('css', '.drupal-media figcaption'));
    $this->assertEquals('Llamas are the most awesome ever', $figcaption->getHtml());
    $editor_dom = $this->getEditorDataAsDom();
    $this->assertEquals('Llamas are the most awesome ever', $editor_dom->getElementsByTagName('drupal-media')->item(0)->getAttribute('data-caption'));

    // Ensure that caption can be linked.
    $this->assertNotEmpty($figcaption = $assert_session->waitForElement('css', '.drupal-media figcaption'));
    $this->selectTextInsideElement('.drupal-media figcaption');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.drupal-media figcaption.ck-editor__nested-editable'));
    $this->pressEditorButton('Link');
    $this->assertVisibleBalloon('.ck-link-form');
    $link_input = $page->find('css', '.ck-balloon-panel .ck-link-form input[type=text]');
    $link_input->setValue('https://drupal.org');
    $page->find('css', '.ck-balloon-panel .ck-link-form button[type=submit]')->click();
    $this->assertNotEmpty($assert_session->waitForElement('css', '.drupal-media figcaption > a'));
    $this->assertEquals('<a class="ck-link_selected" href="https://drupal.org">Llamas are the most awesome ever</a>', $figcaption->getHtml());
    $editor_dom = $this->getEditorDataAsDom();
    $this->assertEquals('<a href="https://drupal.org">Llamas are the most awesome ever</a>', $editor_dom->getElementsByTagName('drupal-media')->item(0)->getAttribute('data-caption'));
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
    // Click the "Override media image alternative text" button.
    $this->getBalloonButton('Override media image alternative text')->click();
    $this->assertVisibleBalloon('.ck-media-alternative-text-form');
    // Assert that the default alt text is visible in the UI.
    $assert_session->elementTextEquals('css', '.ck-media-alternative-text-form__default-alt-text-value', 'default alt');
    // Assert that the value is currently empty.
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-media-alternative-text-form input[type=text]');
    $this->assertSame('', $alt_override_input->getValue());

    // Fill in the alt field and submit.
    // cSpell:disable-next-line
    $who_is_zartan = 'Zartan is the leader of the Dreadnoks.';
    $alt_override_input->setValue($who_is_zartan);
    $this->getBalloonButton('Save')->click();

    // Assert that the img within the media embed within the CKEditor contains
    // the overridden alt text set in the dialog.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img[alt*="' . $who_is_zartan . '"]'));
    // Ensure that the Drupal Media widget doesn't have alt attribute.
    // @see https://www.drupal.org/project/drupal/issues/3248440
    $assert_session->elementNotExists('css', '.ck-widget.drupal-media[alt]');
    // Test `aria-label` attribute appears on the widget wrapper.
    $assert_session->elementExists('css', '.ck-widget.drupal-media [aria-label="Screaming hairy armadillo"]');

    // Test that the downcast drupal-media element now has the alt attribute
    // entered in the balloon.
    $this->assertSourceAttributeSame('alt', $who_is_zartan);

    // The alt field should now display the override instead of the default.
    $this->getBalloonButton('Override media image alternative text')->click();
    $this->assertVisibleBalloon('.ck-media-alternative-text-form');
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-media-alternative-text-form input[type=text]');
    $this->assertSame($who_is_zartan, $alt_override_input->getValue());
    // Assert that the default alt text is still visible in the UI.
    $assert_session->elementTextEquals('css', '.ck-media-alternative-text-form__default-alt-text-value', 'default alt');

    // Test the process again with a different alt text to make sure it works
    // the second time around.
    $cobra_commander_bio = 'The supreme leader of the terrorist organization Cobra';
    // Set the alt field to the new alt text.
    $alt_override_input->setValue($cobra_commander_bio);
    $this->getBalloonButton('Save')->click();
    // Assert that the img within the media embed preview inside CKEditor 5
    // contains the overridden alt text set in the balloon.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img[alt*="' . $cobra_commander_bio . '"]'));

    // Test that the downcast drupal-media element now has the alt attribute
    // entered in the dialog.
    $this->assertSourceAttributeSame('alt', $cobra_commander_bio);

    // The default value of the alt field should now display the override
    // instead of the value on the media image field.
    $this->getBalloonButton('Override media image alternative text')->click();
    $this->assertVisibleBalloon('.ck-media-alternative-text-form');
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-media-alternative-text-form input[type=text]');
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

    // Test that setting alt to back to an empty string within the balloon will
    // restore the default alt value saved in to the media image field of the
    // media item.
    $this->getBalloonButton('Override media image alternative text')->click();
    $this->assertVisibleBalloon('.ck-media-alternative-text-form');
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-media-alternative-text-form input[type=text]');
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
        'alt' => "texte alternatif par dÃ©faut",
        'title' => "titre alternatif par dÃ©faut",
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
    // cSpell:disable-next-line
    $translation->title = 'Animaux avec des noms Ã©tranges';
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

    // Test that the default alt attribute displays without an override.
    // cSpell:disable-next-line
    $this->assertNotEmpty($assert_session->waitForElementVisible('xpath', '//img[contains(@alt, "texte alternatif par dÃ©faut")]'));
    // Test `aria-label` attribute appears on the preview wrapper.
    // cSpell:disable-next-line
    $assert_session->elementExists('css', '[data-drupal-media-preview][aria-label="Tatou poilu hurlant"]');
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    // Click the "Override media image alternative text" button.
    $this->getBalloonButton('Override media image alternative text')->click();
    $this->assertVisibleBalloon('.ck-media-alternative-text-form');
    // Assert that the default alt on the UI is the default alt text from the
    // media entity.
    // cSpell:disable-next-line
    $assert_session->elementTextEquals('css', '.ck-media-alternative-text-form__default-alt-text-value', 'texte alternatif par dÃ©faut');

    // Fill in the alt field in the balloon form.
    // cSpell:disable-next-line
    $qui_est_zartan = 'Zartan est le chef des Dreadnoks.';
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-media-alternative-text-form input[type=text]');
    $alt_override_input->setValue($qui_est_zartan);
    $this->getBalloonButton('Save')->click();

    // Assert that the img within the media embed within CKEditor 5 contains
    // the overridden alt text set in CKEditor 5.
    $this->assertNotEmpty($assert_session->waitForElementVisible('xpath', '//img[contains(@alt, "' . $qui_est_zartan . '")]'));
    $this->getSession()->switchToIFrame();
    $page->pressButton('Save');
    $assert_session->elementExists('xpath', '//img[contains(@alt, "' . $qui_est_zartan . '")]');
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

  public function providerLinkability(): array {
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
  public function testLinkManualDecorator(bool $unrestricted) {
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
   * the media style toolbar allows altering the alignment and that the changes
   * are reflected on the widget and downcast drupal-media tag.
   */
  public function testAlignment() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    // Wait for the media preview to load.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));

    // Ensure that by default the "Break text" alignment option is selected.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->assertTrue(($align_button = $this->getBalloonButton('Break text'))->hasClass('ck-on'));
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertFalse($drupal_media_element->hasAttribute('data-align'));
    $this->getBalloonButton('Align center and break text')->click();

    // Assert the alignment class exists after editing downcast.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-widget.drupal-media.drupal-media-style-align-center'));
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertEquals('center', $drupal_media_element->getAttribute('data-align'));

    $page->pressButton('Save');
    // Check that the 'content has been updated' message status appears to confirm we left the editor.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.messages.messages--status'));
    // Check that the class is correct in the front end.
    $assert_session->elementExists('css', 'figure.align-center');
    // Go back to the editor to check that the alignment class still exists.
    $edit_url = $this->getSession()->getCurrentURL() . '/edit';
    $this->drupalGet($edit_url);
    $this->waitForEditor();
    $assert_session->elementExists('css', '.ck-widget.drupal-media.drupal-media-style-align-center');

    // Ensure that "Centered media" alignment option is selected.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->assertTrue($this->getBalloonButton('Align center and break text')->hasClass('ck-on'));
    $this->getBalloonButton('Break text')->click();
    $this->assertTrue($assert_session->waitForElementRemoved('css', '.ck-widget.drupal-media.drupal-media-style-align-center'));
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertFalse($drupal_media_element->hasAttribute('data-align'));
  }

  /**
   * Ensures that Drupal Media Styles can be displayed in a dropdown.
   */
  public function testDrupalMediaStyleInDropdown() {
    \Drupal::service('module_installer')->install(['ckeditor5_drupalelementstyle_test']);
    $this->resetAll();

    $assert_session = $this->assertSession();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    // Wait for the media preview to load.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));

    // Ensure that by default the "Break text" alignment option is selected and
    // that the split button title is displayed.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->assertNotEmpty(($split_button = $this->getBalloonButton('Test title: Break text'))->hasClass('ck-on'));

    // Ensure that the split button can be opened.
    $split_button->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-dropdown__panel-visible'));

    // Ensure that a button inside the split button can be clicked.
    $this->assertNotEmpty($align_button = $this->getBalloonButton('Align center and break text'));
    $align_button->click();

    // Ensure that the "Align center and break text" option is selected and the
    // split button title is displayed.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-widget.drupal-media.drupal-media-style-align-center'));
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertEquals('center', $drupal_media_element->getAttribute('data-align'));
    $this->assertNotEmpty(($this->getBalloonButton('Test title: Align center and break text'))->hasClass('ck-on'));
  }

  /**
   * Tests Drupal Media Style with a CSS class.
   */
  public function testDrupalMediaStyleWithClass() {
    $editor = Editor::load('test_format');
    $editor->setSettings([
      'toolbar' => [
        'items' => [
          'heading',
          'sourceEditing',
          'simpleBox',
        ],
      ],
      'plugins' => [
        'ckeditor5_heading' => [
          'enabled_headings' => [
            'heading1',
          ],
        ],
        'ckeditor5_sourceEditing' => [
          'allowed_tags' => [
            '<div>',
            '<section>',
          ],
        ],
        'media_media' => [
          'allow_view_mode_override' => TRUE,
        ],
      ],
    ]);
    $filter_format = $editor->getFilterFormat();
    $filter_format->setFilterConfig('filter_html', [
      'status' => TRUE,
      'settings' => [
        'allowed_html' => '<p> <br> <h1 class> <div class> <section class> <drupal-media data-entity-type data-entity-uuid data-align data-caption data-view-mode alt class="layercake-side">',
      ],
    ]);
    $filter_format->save();
    $editor->save();

    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();

    $page->pressButton('Source');
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')->item(0);

    // Add `layercake-side` class which is used in `ckeditor5_test_layercake`,
    // as well as an arbitrary class to compare behavior between these.
    $drupal_media_element->setAttribute('class', 'layercake-side arbitrary-class');
    $textarea = $page->find('css', '.ck-source-editing-area > textarea');
    $textarea->setValue($editor_dom->C14N());
    $page->pressButton('Source');

    // Ensure that the `layercake-side` class is retained.
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-widget.drupal-media.layercake-side'));

    // Ensure that the `arbitrary-class` class is removed.
    $assert_session->elementNotExists('css', '.ck-widget.drupal-media.arbitrary-class');
    $page->pressButton('Save');

    // Check that the 'content has been updated' message status appears to confirm we left the editor.
    $assert_session->waitForElementVisible('css', 'messages messages--status');

    // Ensure that the class is correct in the front end.
    $assert_session->elementExists('css', 'figure.layercake-side');
    $assert_session->elementNotExists('css', 'figure.arbitrary-class');
  }

  /**
   * Tests view mode integration.
   *
   * Tests that view mode is reflected onto the CKEditor 5 Widget wrapper, that
   * the media style toolbar allows changing the view mode and that the changes
   * are reflected on the widget and downcast drupal-media tag.
   *
   * @dataProvider providerTestViewMode
   */
  public function testViewMode(bool $with_alignment) {
    EntityViewMode::create([
      'id' => 'media.view_mode_3',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 3',
    ])->save();
    EntityViewMode::create([
      'id' => 'media.view_mode_4',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 4',
    ])->save();
    // Enable view mode 1, 2, 4 for Image.
    EntityViewDisplay::create([
      'id' => 'media.image.view_mode_4',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'bundle' => 'image',
      'mode' => 'view_mode_4',
    ])->save();

    $filter_format = FilterFormat::load('test_format');
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [
          'view_mode_1' => 'view_mode_1',
          '22222' => '22222',
          'view_mode_3' => 'view_mode_3',
        ],
      ],
    ])->save();

    if (!$with_alignment) {
      $filter_format->filters('filter_align')->setConfiguration(array_merge($filter_format->filters('filter_align')->getConfiguration(), ['status' => FALSE]));
    }

    // Test that view mode dependencies are returned from the MediaEmbed
    // filter's ::getDependencies() method.
    $expected_config_dependencies = [
      'core.entity_view_mode.media.view_mode_1',
      'core.entity_view_mode.media.22222',
      'core.entity_view_mode.media.view_mode_3',
    ];

    $dependencies = $filter_format->getDependencies();
    $this->assertArrayHasKey('config', $dependencies);
    $this->assertEqualsCanonicalizing($expected_config_dependencies, $dependencies['config']);
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    // Wait for the media preview to load.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    // Check that there is no data-view-mode set after embedding media.
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertFalse($drupal_media_element->hasAttribute('data-view-mode'));
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('View Mode 1')->click();

    // Set view mode.
    $this->getBalloonButton('View Mode 2 has Numeric ID')->click();
    $editor_dom = $this->getEditorDataAsDom();
    // Check that “data-view-mode” exists inside source editing.
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertEquals('22222', $drupal_media_element->getAttribute('data-view-mode'));

    // Check that toolbar matches current view mode.
    $dropdown_button = $page->find('css', 'button.ck-dropdown__button > span.ck-button__label');
    $this->assertEquals('View Mode 2 has Numeric ID', $dropdown_button->getText());
    // Enter source mode.
    $this->pressEditorButton('Source');
    // Leave source mode to force CKEditor 5 to upcast again to check data
    // persistence.
    $this->pressEditorButton('Source');
    $this->click('.ck-widget.drupal-media');
    $dropdown_button = $page->find('css', 'button.ck-dropdown__button > span.ck-button__label');
    // Check that view mode 2 persisted.
    $this->assertEquals('View Mode 2 has Numeric ID', $dropdown_button->getText());

    // Check that selecting a caption that is the child of a drupal-media will
    // inherit the drupalElementStyle of its parent element.
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('Toggle caption off')->click();
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    // Select the caption by toggling it on.
    $this->getBalloonButton('Toggle caption on')->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.drupal-media figcaption'));
    // Ensure that the media contextual toolbar is visible after toggling
    // caption on.
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $dropdown_button = $page->find('css', 'button.ck-dropdown__button > span.ck-button__label');
    $this->assertEquals('View Mode 2 has Numeric ID', $dropdown_button->getText());

    // Remove the current view mode by setting it to Default.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('View Mode 2 has Numeric ID')->click();
    // Unset view mode.
    $this->getBalloonButton('View Mode 1')->click();
    $this->waitForEditor();
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    // Test that setting the view mode back to the default removes the
    // `data-view-mode` attribute.
    $this->assertFalse($drupal_media_element->hasAttribute('data-view-mode'));
    $assert_session->elementExists('css', 'article.media--view-mode-view-mode-1');

    // Check that the toolbar status matches "no view mode".
    $dropdown_button = $page->find('css', 'button.ck-dropdown__button > span.ck-button__label');
    $this->assertEquals('View Mode 1', $dropdown_button->getText());

    // Test that setting the allowed_view_modes option to only one option hides
    // the field (it requires more than one option).
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [
          'view_mode_1' => 'view_mode_1',
        ],
      ],
    ])->save();

    // Test that the dependencies change when the allowed_view_modes change.
    $dependencies = $filter_format->getDependencies();
    $this->assertArrayHasKey('config', $dependencies);
    $this->assertSame(['core.entity_view_mode.media.view_mode_1'], $dependencies['config']);
    // Reload page to get new configuration.
    $this->getSession()->reload();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    $this->click('.ck-widget.drupal-media');
    // Check that view mode dropdown is gone because there is only one option.
    $this->assertEmpty($assert_session->waitForElementVisible('css', '.ck.ck-dropdown', 1000));
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertFalse($drupal_media_element->hasAttribute('data-view-mode'));
    $assert_session->elementExists('css', 'article.media--view-mode-view-mode-1');

    // Test that setting allowed_view_modes back to two items restores the
    // field.
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [
          'view_mode_1' => 'view_mode_1',
          '22222' => '22222',
        ],
      ],
    ])->save();

    // Test that the dependencies change when the allowed_view_modes change.
    $expected_config_dependencies = [
      'core.entity_view_mode.media.view_mode_1',
      'core.entity_view_mode.media.22222',
    ];
    $dependencies = $filter_format->getDependencies();
    $this->assertArrayHasKey('config', $dependencies);
    $this->assertEqualsCanonicalizing($expected_config_dependencies, $dependencies['config']);
    // Reload page to get new configuration.
    $this->getSession()->reload();
    $this->waitForEditor();

    // Test that changing the view mode with an empty editable caption
    // preserves the empty editable caption when the preview reloads.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.drupal-media figcaption'));
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace('data-caption="baz"', '', $original_value);
    $this->host->save();
    $this->getSession()->reload();
    $this->waitForEditor();
    $assert_session->elementExists('css', 'article.media--view-mode-view-mode-1');

    $this->assertEmpty($assert_session->waitForElementVisible('css', '.drupal-media figcaption'));
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('View Mode 1')->click();
    $this->getBalloonButton('View Mode 2 has Numeric ID')->click();
    $assert_session->elementExists('css', 'article.media--view-mode-_2222');
    $this->assertEmpty($assert_session->waitForElementVisible('css', '.drupal-media figcaption'));

    // Test that a media with no view modes configured will be
    // set to the default view mode.
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [],
      ],
    ])->save();
    $dependencies = $filter_format->getDependencies();
    $this->assertArrayHasKey('config', $dependencies);
    $this->assertSame(['core.entity_view_mode.media.view_mode_1'], $dependencies['config']);
    $this->host->body->value = '<drupal-media data-caption="armadillo" data-entity-type="media" data-entity-uuid="' . $this->mediaFile->uuid() . '"></drupal-media>';
    $this->host->save();
    // Reload page to get new configuration.
    $this->getSession()->reload();
    $this->waitForEditor();
    $assert_session->waitForElementVisible('css', 'article.media--view-mode-view-mode-1');

    // Test that having a default_view_mode that is not an allowed_view_mode
    // will still be added to the editor.
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [
          '22222' => '22222',
          'view_mode_4' => 'view_mode_4',
        ],
      ],
    ])->save();

    // Test that the dependencies change when the allowed_view_modes change.
    $expected_config_dependencies = [
      'core.entity_view_mode.media.22222',
      'core.entity_view_mode.media.view_mode_1',
      'core.entity_view_mode.media.view_mode_4',
    ];
    $dependencies = $filter_format->getDependencies();
    $this->assertArrayHasKey('config', $dependencies);
    $this->assertEqualsCanonicalizing($expected_config_dependencies, $dependencies['config']);
    $this->host->body->value = '<drupal-media data-entity-type="media" data-entity-uuid="' . $this->media->uuid() . '" data-caption="baz"></drupal-media>';
    $this->host->save();
    // Reload page to get new configuration.
    $this->getSession()->reload();
    $this->waitForEditor();
    // Wait for the media preview to load.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->click('.ck-widget.drupal-media');
    // Check that all three view modes exist including the default view mode
    // that was not originally included in the allowed_view_modes.
    $this->assertNotEmpty($this->getBalloonButton('View Mode 1'));
    $this->assertNotEmpty($this->getBalloonButton('View Mode 2 has Numeric ID'));
    $this->assertNotEmpty($this->getBalloonButton('View Mode 4'));
  }

  /**
   * For testing view modes in different scenarios.
   */
  public function providerTestViewMode(): array {
    return [
      'with alignment' => [TRUE],
      'without alignment' => [FALSE],
    ];
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

  /**
   * Selects text inside an element.
   *
   * @param string $selector
   *   A CSS selector for the element which contents should be selected.
   */
  protected function selectTextInsideElement(string $selector): void {
    $javascript = <<<JS
(function() {
  const el = document.querySelector("$selector");
  const range = document.createRange();
  range.selectNodeContents(el);
  const sel = window.getSelection();
  sel.removeAllRanges();
  sel.addRange(range);
})();
JS;
    $this->getSession()->evaluateScript($javascript);
  }

}
