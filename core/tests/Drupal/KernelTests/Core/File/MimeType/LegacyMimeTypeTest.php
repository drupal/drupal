<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File\MimeType;

use Drupal\Core\File\MimeType\MimeTypeMapInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated file features.
 *
 * @group legacy
 * @group File
 */
class LegacyMimeTypeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'file_deprecated_test'];

  /**
   * Tests deprecation of hook_file_mimetype_mapping_alter.
   *
   * @group legacy
   */
  public function testHookFileMimetypeMappingAlter(): void {
    $this->expectDeprecation(
      'The deprecated alter hook hook_file_mimetype_mapping_alter() is implemented in these locations: file_deprecated_test_file_mimetype_mapping_alter. This hook is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Implement a \Drupal\Core\File\Event\MimeTypeMapLoadedEvent listener instead. See https://www.drupal.org/node/3494040'
    );

    $map = \Drupal::service(MimeTypeMapInterface::class);
    $this->assertEquals(['file_test_2', 'file_test_3'],
      $map->getExtensionsForMimeType('made_up/file_test_2'));
  }

}
