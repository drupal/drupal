<?php

declare(strict_types=1);

namespace Drupal\Tests\default_admin\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\MediaType;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that media library UI JS is not added before its dependencies.
 */
#[RunTestsInSeparateProcesses]
#[Group('default_admin')]
#[Group('media_library')]
class MediaLibraryJavascriptOrderTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'file',
    'filter',
    'image',
    'media',
    'media_library',
    'system',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installConfig([
      'system',
      'image',
      'media',
      'media_library',
    ]);

    MediaType::create([
      'id' => 'test_type',
      'label' => 'Test type',
      'source' => 'image',
    ])->save();

    $fieldStorageConfig = FieldStorageConfig::create([
      'field_name' => 'field_media',
      'entity_type' => 'entity_test',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $fieldStorageConfig->save();

    FieldConfig::create([
      'field_storage' => $fieldStorageConfig,
      'bundle' => 'entity_test',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'test_type' => 'test_type',
          ],
        ],
      ],
    ])->save();

    // Set the media field widget to media library, so that the JS library is
    // added to the entity_test form.
    $display = EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ])->setStatus(TRUE);
    $display->setComponent('field_media', [
      'type' => 'media_library_widget',
    ]);
    $display->save();

    $this->container->get(ThemeInstallerInterface::class)->install(['default_admin']);
    // Turn off JS preprocessing, so that the individual script tags can be
    // found on the page.
    $this->config('system.performance')
      ->set('js.preprocess', 0)
      ->save();
    $this->config('system.theme')
      ->set('default', 'default_admin')
      ->save();
  }

  /**
   * Tests that media library JS is after its dependencies in the page.
   */
  public function testJsScriptOrder(): void {
    // View the entity_test add entity form as a user who can create entity_test
    // content and view media, so that the media library is visible.
    $this->setUpCurrentUser(permissions: [
      'administer entity_test content',
      'view media',
      'view the administration theme',
    ]);
    $this->drupalGet('entity_test/add');
    $text = $this->getSession()->getPage()->getContent();
    $scripts = [
      '<script src="/core/assets/vendor/jquery/jquery.min.js',
      '<script src="/core/assets/vendor/once/once.min.js',
      '<script src="/core/misc/drupal.js',
      '<script src="/core/misc/drupal.init.js',
      '<script src="/core/themes/default_admin/js/media-library.ui.js',
    ];
    // Track the positions of the script strings in the page text.
    $positions = [];
    // Track the largest position for a script string found in the page text.
    $maxPosition = -1;
    foreach ($scripts as $script) {
      $position = strpos($text, $script);
      $this->assertNotFalse($position);
      $maxPosition = $position > $maxPosition ? $position : $maxPosition;
      // Each position should be unique.
      $this->assertNotContains($position, $positions);
      $positions[$script] = $position;
    }
    // The large position should be the one for the media library script.
    $this->assertSame($maxPosition, $positions['<script src="/core/themes/default_admin/js/media-library.ui.js']);
  }

}
