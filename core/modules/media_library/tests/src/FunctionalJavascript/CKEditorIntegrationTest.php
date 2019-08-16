<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\Component\Utility\Html;
use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * @coversDefaultClass \Drupal\media_library\Plugin\CKEditorPlugin\DrupalMediaLibrary
 * @group media_library
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
  protected $user;

  /**
   * The media item to embed.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $media;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor',
    'media_library',
    'node',
    'text',
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
                'name' => 'Main',
                'items' => [
                  'Source',
                  'Undo',
                  'Redo',
                ],
              ],
            ],
            [
              [
                'name' => 'Embeds',
                'items' => [
                  'DrupalMediaLibrary',
                ],
              ],
            ],
          ],
        ],
      ],
    ])->save();

    $this->drupalCreateContentType(['type' => 'blog']);

    // Note that media_install() grants 'view media' to all users by default.
    $this->user = $this->drupalCreateUser([
      'use text format test_format',
      'access media overview',
      'create blog content',
    ]);

    // Create a media type that starts with the letter a, to test tab order.
    $this->createMediaType('image', ['id' => 'Arrakis', 'label' => 'Arrakis']);

    // Create a sample media entity to be embedded.
    $this->createMediaType('image', ['id' => 'image', 'label' => 'Image']);
    File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ])->save();
    $this->media = Media::create([
      'bundle' => 'image',
      'name' => 'Fear is the mind-killer',
      'field_media_image' => [
        [
          'target_id' => 1,
          'alt' => 'default alt',
          'title' => 'default title',
        ],
      ],
    ]);
    $this->media->save();

    $this->drupalLogin($this->user);
  }

  /**
   * Tests that media_embed filter is required to enable the DrupalMediaLibrary
   * button.
   */
  public function testConfigurationValidation() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'administer filters',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalGet('/admin/config/content/formats/manage/test_format');
    $page->uncheckField('filters[media_embed][status]');
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The Embed media filter must be enabled to use the Insert from Media Library button.');
    $page->checkField('filters[media_embed][status]');
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The text format Test format has been updated.');
  }

  /**
   * Tests using DrupalMediaLibrary button to embed media into CKEditor.
   */
  public function testButton() {
    $this->drupalGet('/node/add/blog');
    $this->waitForEditor();
    $this->pressEditorButton('drupalmedialibrary');
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->assertNotEmpty($assert_session->waitForId('drupal-modal'));

    // Test that the order is the order set in DrupalMediaLibrary::getConfig().
    $tabs = $page->findAll('css', '.media-library-menu__link');
    $expected_tab_order = [
      'Show Image media (selected)',
      'Show Arrakis media',
    ];
    foreach ($tabs as $key => $tab) {
      $this->assertSame($expected_tab_order[$key], $tab->getText());
    }

    $assert_session->pageTextContains('0 of 1 item selected');
    $assert_session->elementExists('css', '.media-library-item')->click();
    $assert_session->pageTextContains('1 of 1 item selected');
    $assert_session->elementExists('css', 'button.media-library-select.button.button--primary')->click();
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.cke_widget_drupalmedia drupal-media .media', 2000));
    // @todo Inserting media embed should enable undo.
    // @see https://www.drupal.org/project/drupal/issues/3073294
    $this->pressEditorButton('source');
    $value = $assert_session->elementExists('css', 'textarea.cke_source')->getValue();
    $dom = Html::load($value);
    $xpath = new \DOMXPath($dom);
    $drupal_media = $xpath->query('//drupal-media')[0];
    $expected_attributes = [
      'data-entity-type' => 'media',
      'data-entity-uuid' => $this->media->uuid(),
      'data-align' => 'center',
    ];
    foreach ($expected_attributes as $name => $expected) {
      $this->assertSame($expected, $drupal_media->getAttribute($name));
    }
    $this->pressEditorButton('source');
    // Why do we keep switching to the 'ckeditor' iframe? Because the buttons
    // are in a separate iframe from the markup, so after calling
    // ::pressEditorButton() (which switches to the button iframe), we'll need
    // to switch back to the CKEditor iframe.
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.cke_widget_drupalmedia drupal-media .media', 1000));
    $this->assertEditorButtonEnabled('undo');
    $this->pressEditorButton('undo');
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertEmpty($assert_session->waitForElementVisible('css', '.cke_widget_drupalmedia drupal-media .media', 1000));
    $this->assertEditorButtonDisabled('undo');
    $this->pressEditorButton('redo');
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.cke_widget_drupalmedia drupal-media .media', 1000));
    $this->assertEditorButtonEnabled('undo');
  }

}
