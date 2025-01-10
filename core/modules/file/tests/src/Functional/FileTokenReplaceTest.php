<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\file\Entity\File;

/**
 * Tests file token replacement.
 *
 * @group file
 */
class FileTokenReplaceTest extends FileFieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Creates a file, then tests the tokens generated from it.
   */
  public function testFileTokenReplacement(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $token_service = \Drupal::token();
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = $this->container->get('date.formatter');

    // Create file field.
    $type_name = 'article';
    $field_name = 'field_' . $this->randomMachineName();
    $this->createFileField($field_name, 'node', $type_name);

    $test_file = $this->getTestFile('text');
    // Coping a file to test uploads with non-latin filenames.
    // cSpell:disable-next-line
    $filename = \Drupal::service('file_system')->dirname($test_file->getFileUri()) . '/текстовый файл.txt';
    $test_file = \Drupal::service('file.repository')->copy($test_file, $filename);

    // Create a new node with the uploaded file.
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);

    // Load the node and the file.
    $node = $node_storage->load($nid);
    $file = File::load($node->{$field_name}->target_id);

    // Generate and test sanitized tokens.
    $tests = [];
    $tests['[file:fid]'] = $file->id();
    $tests['[file:uuid]'] = $file->uuid();
    $tests['[file:name]'] = Html::escape($file->getFilename());
    $tests['[file:path]'] = Html::escape($file->getFileUri());
    $tests['[file:mime]'] = Html::escape($file->getMimeType());
    $tests['[file:size]'] = ByteSizeMarkup::create($file->getSize());
    $tests['[file:url]'] = Html::escape($file->createFileUrl(FALSE));
    $tests['[file:created]'] = $date_formatter->format($file->getCreatedTime(), 'medium', '', NULL, $language_interface->getId());
    $tests['[file:created:short]'] = $date_formatter->format($file->getCreatedTime(), 'short', '', NULL, $language_interface->getId());
    $tests['[file:changed]'] = $date_formatter->format($file->getChangedTime(), 'medium', '', NULL, $language_interface->getId());
    $tests['[file:changed:short]'] = $date_formatter->format($file->getChangedTime(), 'short', '', NULL, $language_interface->getId());
    $tests['[file:owner]'] = Html::escape($this->adminUser->getDisplayName());
    $tests['[file:owner:uid]'] = $file->getOwnerId();

    $base_bubbleable_metadata = BubbleableMetadata::createFromObject($file);
    $metadata_tests = [];
    $metadata_tests['[file:fid]'] = $base_bubbleable_metadata;
    $metadata_tests['[file:uuid]'] = $base_bubbleable_metadata;
    $metadata_tests['[file:name]'] = $base_bubbleable_metadata;
    $metadata_tests['[file:path]'] = $base_bubbleable_metadata;
    $metadata_tests['[file:mime]'] = $base_bubbleable_metadata;
    $metadata_tests['[file:size]'] = $base_bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[file:url]'] = $bubbleable_metadata->addCacheContexts(['url.site']);
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[file:created]'] = $bubbleable_metadata->addCacheTags(['rendered']);
    $metadata_tests['[file:created:short]'] = $bubbleable_metadata;
    $metadata_tests['[file:changed]'] = $bubbleable_metadata;
    $metadata_tests['[file:changed:short]'] = $bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[file:owner]'] = $bubbleable_metadata->addCacheTags(['user:2']);
    $metadata_tests['[file:owner:uid]'] = $bubbleable_metadata;

    // Test to make sure that we generated something for each token.
    $this->assertNotContains(0, array_map('strlen', $tests), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $token_service->replace($input, ['file' => $file], ['langcode' => $language_interface->getId()], $bubbleable_metadata);
      $this->assertEquals($expected, $output, "Sanitized file token $input replaced.");
      $this->assertEquals($metadata_tests[$input], $bubbleable_metadata);
    }

    // Generate and test unsanitized tokens.
    $tests['[file:name]'] = $file->getFilename();
    $tests['[file:path]'] = $file->getFileUri();
    $tests['[file:mime]'] = $file->getMimeType();
    $tests['[file:size]'] = ByteSizeMarkup::create($file->getSize());

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, ['file' => $file], ['langcode' => $language_interface->getId(), 'sanitize' => FALSE]);
      $this->assertEquals($expected, $output, "Unsanitized file token $input replaced.");
    }
  }

}
