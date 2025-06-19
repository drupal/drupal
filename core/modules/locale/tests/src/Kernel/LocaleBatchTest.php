<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests locale batches.
 *
 * @group locale
 */
class LocaleBatchTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'locale',
    'system',
    'language',
  ];

  /**
   * Checks that the import batch finishes if the translation has already been imported.
   */
  public function testBuildProjects(): void {
    $this->installConfig(['locale']);
    $this->installSchema('locale', ['locale_file']);
    $this->container->get('module_handler')->loadInclude('locale', 'batch.inc');

    \Drupal::database()->insert('locale_file')
      ->fields([
        'project' => 'drupal',
        'langcode' => 'en',
        'filename' => 'drupal.po',
        'version' => \Drupal::VERSION,
        'timestamp' => time(),
      ])
      ->execute();

    $context = [];
    locale_translation_batch_fetch_import('drupal', 'en', [], $context);
    $this->assertEquals(1, $context['finished']);
    $this->assertEquals('Ignoring already imported translation for drupal.', $context['message']);
  }

}
