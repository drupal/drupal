<?php

namespace Drupal\Tests\file\Functional\Formatter;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\file\Entity\File;

/**
 * @coversDefaultClass \Drupal\file\Plugin\Field\FieldFormatter\FileVideoFormatter
 * @group file
 */
class FileVideoFormatterTest extends FileMediaFormatterTestBase {

  /**
   * @covers ::viewElements
   *
   * @dataProvider dataProvider
   */
  public function testRender($tag_count, $formatter_settings) {
    $field_config = $this->createMediaField('file_video', 'mp4', $formatter_settings);

    file_put_contents('public://file.mp4', str_repeat('t', 10));
    $file1 = File::create([
      'uri' => 'public://file.mp4',
      'filename' => 'file.mp4',
    ]);
    $file1->save();

    $file2 = File::create([
      'uri' => 'public://file.mp4',
      'filename' => 'file.mp4',
    ]);
    $file2->save();

    $entity = EntityTest::create([
      $field_config->getName() => [
        [
          'target_id' => $file1->id(),
        ],
        [
          'target_id' => $file2->id(),
        ],
      ],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());

    $file1_url = $file1->createFileUrl();
    $file2_url = $file2->createFileUrl();

    $assert_session = $this->assertSession();
    $assert_session->elementsCount('css', 'video[controls="controls"]', $tag_count);
    $assert_session->elementExists('css', "video > source[src='$file1_url'][type='video/mp4']");
    $assert_session->elementExists('css', "video > source[src='$file2_url'][type='video/mp4']");
  }

  /**
   * Tests that the attributes added to the formatter are applied on render.
   */
  public function testAttributes() {
    $field_config = $this->createMediaField(
      'file_video',
      'mp4',
      [
        'autoplay' => TRUE,
        'loop' => TRUE,
        'muted' => TRUE,
      ]
    );

    file_put_contents('public://file.mp4', str_repeat('t', 10));
    $file = File::create([
      'uri' => 'public://file.mp4',
      'filename' => 'file.mp4',
    ]);
    $file->save();

    $entity = EntityTest::create([
      $field_config->getName() => [
        [
          'target_id' => $file->id(),
        ],
      ],
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());

    $file_url = file_url_transform_relative(file_create_url($file->getFileUri()));

    $assert_session = $this->assertSession();
    $assert_session->elementExists('css', "video[autoplay='autoplay'] > source[src='$file_url'][type='video/mp4']");
    $assert_session->elementExists('css', "video[loop='loop'] > source[src='$file_url'][type='video/mp4']");
    $assert_session->elementExists('css', "video[muted='muted'] > source[src='$file_url'][type='video/mp4']");
  }

}
