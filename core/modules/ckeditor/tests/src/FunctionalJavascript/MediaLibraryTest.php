<?php

namespace Drupal\Tests\ckeditor\FunctionalJavascript;

use Drupal\Component\Utility\Html;
use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;
use Drupal\Tests\ckeditor\Traits\CKEditorAdminSortTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * @coversDefaultClass \Drupal\ckeditor\Plugin\CKEditorPlugin\DrupalMediaLibrary
 * @group ckeditor
 */
class MediaLibraryTest extends WebDriverTestBase {

  use CKEditorTestTrait;
  use CKEditorAdminSortTrait;
  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

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
  protected function setUp(): void {
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
    $this->createMediaType('image', ['id' => 'arrakis', 'label' => 'Arrakis']);

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

    $arrakis_media = Media::create([
      'bundle' => 'arrakis',
      'name' => 'Le baron Vladimir Harkonnen',
      'field_media_image' => [
        [
          'target_id' => 1,
          'alt' => 'Il complote pour détruire le duc Leto',
          'title' => 'Il complote pour détruire le duc Leto',
        ],
      ],
    ]);
    $arrakis_media->save();

    $this->drupalLogin($this->user);
  }

  /**
   * Tests validation that DrupalMediaLibrary requires media_embed filter.
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

    // Now test adding a new format.
    $this->drupalGet('/admin/config/content/formats/add');
    $page->fillField('name', 'Sulaco');
    // Wait for machine name to be filled in.
    $this->assertNotEmpty($assert_session->waitForText('sulaco'));
    $page->checkField('roles[authenticated]');
    $page->selectFieldOption('editor[editor]', 'ckeditor');

    $targetSelector = 'ul.ckeditor-toolbar-group-buttons';
    $buttonSelector = 'li[data-drupal-ckeditor-button-name="DrupalMediaLibrary"]';
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', $targetSelector));
    $this->assertNotEmpty($assert_session->elementExists('css', $buttonSelector));
    $this->sortableTo($buttonSelector, 'ul.ckeditor-available-buttons', $targetSelector);
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The Embed media filter must be enabled to use the Insert from Media Library button.');
    $page->checkField('filters[media_embed][status]');
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('Added text format Sulaco.');

    // Test that when adding the DrupalMediaLibrary button to the editor the
    // correct attributes are added to the <drupal-media> tag in the Allowed
    // HTML tags.
    $this->drupalGet('/admin/config/content/formats/manage/sulaco');
    $page->checkField('filters[filter_html][status]');
    $expected = 'drupal-media data-entity-type data-entity-uuid data-view-mode data-align data-caption alt title';
    $allowed_html = $assert_session->fieldExists('filters[filter_html][settings][allowed_html]')->getValue();
    $this->assertStringContainsString($expected, $allowed_html);
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The text format Sulaco has been updated.');

    // Test that the config form allows removing non-required attributes from
    // the <drupal-media> tag.
    $this->drupalGet('/admin/config/content/formats/manage/sulaco');
    $allowed_html_field = $assert_session->fieldExists('filters[filter_html][settings][allowed_html]');
    $allowed_html = $allowed_html_field->getValue();
    $search = 'drupal-media data-entity-type data-entity-uuid data-view-mode data-align data-caption alt title';
    $replace = 'drupal-media data-entity-type data-entity-uuid';
    $allowed_html = str_replace($search, $replace, $allowed_html);
    $page->clickLink('Limit allowed HTML tags and correct faulty HTML');
    $this->assertTrue($allowed_html_field->waitFor(10, function ($allowed_html_field) {
      return $allowed_html_field->isVisible();
    }));
    $allowed_html_field->setValue($allowed_html);
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The text format Sulaco has been updated.');
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
    $assert_session->elementExists('css', '.js-media-library-item')->click();
    $assert_session->pageTextContains('1 of 1 item selected');
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assignNameToCkeditorIframe();
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.cke_widget_drupalmedia drupal-media .media'));
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
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.cke_widget_drupalmedia drupal-media .media'));
    $this->assertEditorButtonEnabled('undo');
    $this->pressEditorButton('undo');
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertEmpty($assert_session->waitForElementVisible('css', '.cke_widget_drupalmedia drupal-media .media'));
    $this->assertEditorButtonDisabled('undo');
    $this->pressEditorButton('redo');
    $this->getSession()->switchToIFrame('ckeditor');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.cke_widget_drupalmedia drupal-media .media'));
    $this->assertEditorButtonEnabled('undo');
  }

  /**
   * Tests the allowed media types setting on the MediaEmbed filter.
   */
  public function testAllowedMediaTypes() {
    $test_cases = [
      'all_media_types' => [],
      'only_image' => ['image' => 'image'],
      'only_arrakis' => ['arrakis' => 'arrakis'],
      'both_items_checked' => [
        'image' => 'image',
        'arrakis' => 'arrakis',
      ],
    ];

    foreach ($test_cases as $allowed_media_types) {
      // Update the filter format to set the allowed media types.
      FilterFormat::load('test_format')
        ->setFilterConfig('media_embed', [
        'status' => TRUE,
        'settings' => [
          'default_view_mode' => 'view_mode_1',
          'allowed_media_types' => $allowed_media_types,
          'allowed_view_modes' => [
            'view_mode_1' => 'view_mode_1',
            'view_mode_2' => 'view_mode_2',
          ],
        ],
      ])->save();

      // Now test opening the media library from the CKEditor plugin, and
      // verify the expected behavior.
      $this->drupalGet('/node/add/blog');
      $this->waitForEditor();
      $this->pressEditorButton('drupalmedialibrary');
      $assert_session = $this->assertSession();
      $this->assertNotEmpty($assert_session->waitForId('media-library-wrapper'));

      if (empty($allowed_media_types) || count($allowed_media_types) === 2) {
        $assert_session->elementExists('css', 'li.media-library-menu-image');
        $assert_session->elementExists('css', 'li.media-library-menu-arrakis');
        $assert_session->elementTextContains('css', '.media-library-item__name', 'Fear is the mind-killer');
      }
      elseif (count($allowed_media_types) === 1 && !empty($allowed_media_types['image'])) {
        // No tabs should appear if there's only one media type available.
        $assert_session->elementNotExists('css', 'li.media-library-menu-image');
        $assert_session->elementNotExists('css', 'li.media-library-menu-arrakis');
        $assert_session->elementTextContains('css', '.media-library-item__name', 'Fear is the mind-killer');
      }
      elseif (count($allowed_media_types) === 1 && !empty($allowed_media_types['arrakis'])) {
        // No tabs should appear if there's only one media type available.
        $assert_session->elementNotExists('css', 'li.media-library-menu-image');
        $assert_session->elementNotExists('css', 'li.media-library-menu-arrakis');
        $assert_session->elementTextContains('css', '.media-library-item__name', 'Le baron Vladimir Harkonnen');
      }
    }
  }

}
