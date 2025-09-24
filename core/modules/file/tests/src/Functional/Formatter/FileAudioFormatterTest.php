<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Functional\Formatter;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldFormatter\FileAudioFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\file\Plugin\Field\FieldFormatter\FileAudioFormatter.
 */
#[CoversClass(FileAudioFormatter::class)]
#[Group('file')]
#[RunTestsInSeparateProcesses]
class FileAudioFormatterTest extends FileMediaFormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests render.
   *
   * @legacy-covers ::viewElements
   */
  #[DataProvider('dataProvider')]
  public function testRender($tag_count, $formatter_settings): void {
    // Create a file field that accepts .mp3 and an unknown file extension.
    $field_config = $this->createMediaField('file_audio', 'unknown-extension, mp3', $formatter_settings);

    file_put_contents('public://file.mp3', str_repeat('t', 10));
    $file1 = File::create([
      'uri' => 'public://file.mp3',
      'filename' => 'file.mp3',
    ]);
    $file1->save();

    $file2 = File::create([
      'uri' => 'public://file.mp3',
      'filename' => 'file.mp3',
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
    $assert_session->elementsCount('css', 'audio[controls="controls"]', $tag_count);
    $assert_session->elementExists('css', "audio > source[src='$file1_url'][type='audio/mpeg']");
    $assert_session->elementExists('css', "audio > source[src='$file2_url'][type='audio/mpeg']");
  }

}
