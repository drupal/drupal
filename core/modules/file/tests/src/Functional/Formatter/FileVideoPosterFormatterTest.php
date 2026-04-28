<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Functional\Formatter;

use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldFormatter\FileVideoFormatter;
use Drupal\Tests\file\Functional\FileFieldTestBase;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests rendering poster on video.
 */
#[CoversClass(FileVideoFormatter::class)]
#[Group('file')]
#[RunTestsInSeparateProcesses]
class FileVideoPosterFormatterTest extends FileFieldTestBase {

  use ImageFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'image',
  ];

  /**
   * Tests rendering poster on video.
   */
  public function testPosterNoImageStyle(): void {

    // Video field configuration.
    $video_fieldname = 'field_' . mb_strtolower($this->randomMachineName());
    $this->createFileField($video_fieldname, 'node', 'article', [], ['file_extensions' => 'mp4']);

    // Poster image field configuration.
    $poster_fieldname = 'field_' . mb_strtolower($this->randomMachineName());
    $this->createImageField($poster_fieldname, 'node', 'article', [], ['file_extensions' => 'jpg']);

    // Configure node display.
    $display = \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'article');
    $display_options = [
      'type' => 'file_video',
      'settings' => [
        'poster' => $poster_fieldname,
        'poster_image_style' => '',
      ],
    ];
    $display->setComponent($video_fieldname, $display_options)
      ->save();

    // Create files.
    file_put_contents('public://file.mp4', str_repeat('t', 10));
    $video1 = File::create([
      'uri' => 'public://file.mp4',
      'filename' => 'file.mp4',
    ]);
    $video1->save();
    $file_url = \Drupal::service('file_url_generator')->generate($video1->getFileUri())->toString();

    file_put_contents('public://file.jpg', str_repeat('t', 10));
    $poster1 = File::create([
      'uri' => 'public://file.jpg',
      'filename' => 'file.jpg',
    ]);
    $poster1->save();
    $poster_url = \Drupal::service('file_url_generator')->generate($poster1->getFileUri())->toString();

    // Create test node.
    $node = $this->drupalCreateNode([
      'title' => 'Hello, world!',
      'type' => 'article',
      $video_fieldname => [
        [
          'target_id' => $video1->id(),
        ],
      ],
      $poster_fieldname => [
        [
          'target_id' => $poster1->id(),
        ],
      ],
    ]);
    $node->save();

    $this->drupalGet('/node/' . $node->id());
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', "video > source[src='$file_url'][type='video/mp4']");
    $assert_session->elementExists('css', "video[poster='$poster_url']");
  }

  /**
   * Tests rendering poster on video using an image style.
   */
  public function testPosterImageStyle(): void {
    // Video field configuration.
    $video_fieldname = 'field_' . mb_strtolower($this->randomMachineName());
    $this->createFileField($video_fieldname, 'node', 'article', [], ['file_extensions' => 'mp4']);

    // Poster image field configuration.
    $poster_fieldname = 'field_' . mb_strtolower($this->randomMachineName());
    $this->createImageField($poster_fieldname, 'node', 'article', [], ['file_extensions' => 'jpg']);

    // Configure node display.
    $display = \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'article');
    $display_options = [
      'type' => 'file_video',
      'settings' => [
        'poster' => $poster_fieldname,
        'poster_image_style' => 'medium',
      ],
    ];
    $display->setComponent($video_fieldname, $display_options)
      ->save();

    // Create files.
    file_put_contents('public://file.mp4', str_repeat('t', 10));
    $video1 = File::create([
      'uri' => 'public://file.mp4',
      'filename' => 'file.mp4',
    ]);
    $video1->save();
    $file_url = \Drupal::service('file_url_generator')->generate($video1->getFileUri())->toString();

    file_put_contents('public://file.jpg', str_repeat('t', 10));
    $poster1 = File::create([
      'uri' => 'public://file.jpg',
      'filename' => 'file.jpg',
    ]);
    $poster1->save();

    // Create test node.
    $node = $this->drupalCreateNode([
      'title' => 'Hello, world!',
      'type' => 'article',
      $video_fieldname => [
        [
          'target_id' => $video1->id(),
        ],
      ],
      $poster_fieldname => [
        [
          'target_id' => $poster1->id(),
        ],
      ],
    ]);
    $node->save();

    $this->drupalGet('/node/' . $node->id());
    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', "video > source[src='$file_url'][type='video/mp4']");
    // Ensure the image style is served:
    $assert_session->elementAttributeContains('css', 'video', 'poster', 'styles/medium/public/file.jpg');
  }

}
