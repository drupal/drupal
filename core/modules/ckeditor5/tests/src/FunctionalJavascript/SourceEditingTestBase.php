<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Symfony\Component\Validator\ConstraintViolation;

// cspell:ignore sourceediting

/**
 * @internal
 */
abstract class SourceEditingTestBase extends CKEditor5TestBase {

  use CKEditor5TestTrait;

  /**
   * The user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A host entity with a body field whose text to edit with CKEditor 5.
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
  protected $defaultTheme = 'stark';

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
            'allowed_html' => '<div class> <p> <br> <a href> <ol> <ul> <li>',
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
            'sourceEditing',
            'link',
            'bulletedList',
            'numberedList',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => ['<div class>'],
          ],
          'ckeditor5_list' => [
            'properties' => [
              'reversed' => FALSE,
              'startIndex' => FALSE,
            ],
            'multiBlock' => TRUE,
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
    $this->adminUser = $this->drupalCreateUser([
      'use text format test_format',
      'bypass node access',
    ]);

    // Create a sample host entity to test CKEditor 5.
    $this->host = $this->createNode([
      'type' => 'page',
      'title' => 'Animals with strange names',
      'body' => [
        'value' => '',
        'format' => 'test_format',
      ],
    ]);
    $this->host->save();

    $this->drupalLogin($this->adminUser);
  }

}
