<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\editor\Entity\Editor;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Base class for CKEditor 5 Media integration tests.
 *
 * @internal
 */
abstract class MediaTestBase extends WebDriverTestBase {

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
  protected $defaultTheme = 'starterkit_theme';

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
      function (ConstraintViolationInterface $v) {
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
  protected function getLastPreviewRequestTransferSize(): int {
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
