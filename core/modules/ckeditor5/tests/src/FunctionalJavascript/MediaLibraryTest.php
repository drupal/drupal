<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\MediaLibrary
 * @group ckeditor5
 * @internal
 */
class MediaLibraryTest extends WebDriverTestBase {

  use MediaTypeCreationTrait;
  use TestFileCreationTrait;
  use CKEditor5TestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

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
    'ckeditor5',
    'media_library',
    'node',
    'media',
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
      'editor' => 'ckeditor5',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'items' => [
            'drupalMedia',
            'sourceEditing',
            'undo',
            'redo',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [],
          ],
          'media_media' => [
            'allow_view_mode_override' => FALSE,
          ],
        ],
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
   * Tests using drupalMedia button to embed media into CKEditor 5.
   */
  public function testButton() {
    // Skipped due to frequent random test failures.
    // @todo Fix this and stop skipping it at https://www.drupal.org/i/3351597.
    $this->markTestSkipped();
    $media_preview_selector = '.ck-content .ck-widget.drupal-media .media';
    $this->drupalGet('/node/add/blog');
    $this->waitForEditor();
    $this->pressEditorButton('Insert Media');
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-modal #media-library-content'));

    // Ensure that the tab order is correct.
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
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', $media_preview_selector, 1000));
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $drupal_media = $xpath->query('//drupal-media')[0];
    $expected_attributes = [
      'data-entity-type' => 'media',
      'data-entity-uuid' => $this->media->uuid(),
    ];
    foreach ($expected_attributes as $name => $expected) {
      $this->assertSame($expected, $drupal_media->getAttribute($name));
    }
    $this->assertEditorButtonEnabled('Undo');
    $this->pressEditorButton('Undo');
    $this->assertEmpty($assert_session->waitForElementVisible('css', $media_preview_selector, 1000));
    $this->assertEditorButtonDisabled('Undo');
    $this->pressEditorButton('Redo');
    $this->assertEditorButtonEnabled('Undo');

    // Ensure that data-align attribute is set by default when media is inserted
    // while filter_align is enabled.
    FilterFormat::load('test_format')
      ->setFilterConfig('filter_align', ['status' => TRUE])
      ->save();
    $this->drupalGet('/node/add/blog');
    $this->waitForEditor();
    $this->pressEditorButton('Insert Media');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-modal #media-library-content'));
    $assert_session->elementExists('css', '.js-media-library-item')->click();
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', $media_preview_selector, 1000));
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $drupal_media = $xpath->query('//drupal-media')[0];
    $expected_attributes = [
      'data-entity-type' => 'media',
      'data-entity-uuid' => $this->media->uuid(),
    ];
    foreach ($expected_attributes as $name => $expected) {
      $this->assertSame($expected, $drupal_media->getAttribute($name));
    }
    // Ensure that by default, data-align attribute is not set.
    $this->assertFalse($drupal_media->hasAttribute('data-align'));
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
            'allowed_media_types' => $allowed_media_types,
          ],
        ])->save();

      // Now test opening the media library from the CKEditor plugin, and
      // verify the expected behavior.
      $this->drupalGet('/node/add/blog');
      $this->waitForEditor();
      $this->pressEditorButton('Insert Media');

      $assert_session = $this->assertSession();
      $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-modal #media-library-wrapper'));

      if (empty($allowed_media_types) || count($allowed_media_types) === 2) {
        $menu = $assert_session->elementExists('css', '.js-media-library-menu');
        $assert_session->elementExists('named', ['link', 'Image'], $menu);
        $assert_session->elementExists('named', ['link', 'Arrakis'], $menu);
        $assert_session->elementTextContains('css', '.js-media-library-item', 'Fear is the mind-killer');
      }
      elseif (count($allowed_media_types) === 1 && !empty($allowed_media_types['image'])) {
        // No tabs should appear if there's only one media type available.
        $assert_session->elementNotExists('css', '.js-media-library-menu');
        $assert_session->elementTextContains('css', '.js-media-library-item', 'Fear is the mind-killer');
      }
      elseif (count($allowed_media_types) === 1 && !empty($allowed_media_types['arrakis'])) {
        // No tabs should appear if there's only one media type available.
        $assert_session->elementNotExists('css', '.js-media-library-menu');
        $assert_session->elementTextContains('css', '.js-media-library-item', 'Le baron Vladimir Harkonnen');
      }
    }
  }

  /**
   * Ensures that alt text can be changed on Media Library inserted Media.
   */
  public function testAlt() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/node/add/blog');
    $this->waitForEditor();
    $this->pressEditorButton('Insert Media');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-modal #media-library-content'));
    $assert_session->elementExists('css', '.js-media-library-item')->click();
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));

    // Test that clicking the media widget triggers a CKEditor balloon panel
    // with a single button to override the alt text.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    // Click the "Override media image text alternative" button.
    $this->getBalloonButton('Override media image alternative text')->click();
    $this->assertVisibleBalloon('.ck-media-alternative-text-form');
    // Assert that the value is currently empty.
    $alt_override_input = $page->find('css', '.ck-balloon-panel .ck-media-alternative-text-form input[type=text]');
    $this->assertSame('', $alt_override_input->getValue());

    $test_alt = 'Alt text override';
    $alt_override_input->setValue($test_alt);
    $this->getBalloonButton('Save')->click();

    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img[alt*="' . $test_alt . '"]'));
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $drupal_media = $xpath->query('//drupal-media')[0];
    $this->assertEquals($test_alt, $drupal_media->getAttribute('alt'));
  }

}
