<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Kernel\Migrate\d6;

use Drupal\Core\Database\Database;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\ImageEffectPluginCollection;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of ImageCache presets to image styles.
 *
 * @group image
 */
class MigrateImageCacheTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['image']);
  }

  /**
   * Tests that an exception is thrown when ImageCache is not installed.
   */
  public function testMissingTable(): void {
    $this->sourceDatabase->update('system')
      ->fields([
        'status' => 0,
      ])
      ->condition('name', 'imagecache')
      ->condition('type', 'module')
      ->execute();

    $this->expectException(RequirementsException::class);
    $this->getMigration('d6_imagecache_presets')
      ->getSourcePlugin()
      ->checkRequirements();
  }

  /**
   * Tests basic passing migrations.
   */
  public function testPassingMigration(): void {
    $this->executeMigration('d6_imagecache_presets');

    /** @var \Drupal\image\Entity\ImageStyle $style */
    $style = ImageStyle::load('big_blue_cheese');

    // Check basic Style info.
    $this->assertSame('big_blue_cheese', $style->get('name'), 'ImageStyle name set correctly');
    $this->assertSame('big_blue_cheese', $style->get('label'), 'ImageStyle label set correctly');

    // Test effects.
    $effects = $style->getEffects();

    // Check crop effect.
    $this->assertImageEffect($effects, 'image_crop', [
      'width' => 555,
      'height' => 5555,
      'anchor' => 'center-center',
    ]);

    // Check resize effect.
    $this->assertImageEffect($effects, 'image_resize', [
      'width' => 55,
      'height' => 55,
    ]);

    // Check rotate effect.
    $this->assertImageEffect($effects, 'image_rotate', [
      'degrees' => 55,
      'random' => FALSE,
      'bgcolor' => '',
    ]);
  }

  /**
   * Tests that missing actions causes failures.
   */
  public function testMissingEffectPlugin(): void {
    Database::getConnection('default', 'migrate')->insert("imagecache_action")
      ->fields([
        'presetid',
        'weight',
        'module',
        'action',
        'data',
      ])
      ->values([
        'presetid' => '1',
        'weight' => '0',
        'module' => 'imagecache',
        'action' => 'imagecache_deprecated_scale',
        'data' => 'a:3:{s:3:"fit";s:7:"outside";s:5:"width";s:3:"200";s:6:"height";s:3:"200";}',
      ])->execute();

    $this->startCollectingMessages();
    $this->executeMigration('d6_imagecache_presets');
    $messages = iterator_to_array($this->migration->getIdMap()->getMessages());
    $this->assertCount(1, $messages);
    $this->assertStringContainsString('The "image_deprecated_scale" plugin does not exist.', $messages[0]->message);
    $this->assertEquals(MigrationInterface::MESSAGE_ERROR, $messages[0]->level);
  }

  /**
   * Tests that missing action's causes failures.
   */
  public function testInvalidCropValues(): void {
    Database::getConnection('default', 'migrate')->insert("imagecache_action")
      ->fields([
        'presetid',
        'weight',
        'module',
        'action',
        'data',
      ])
      ->values([
        'presetid' => '1',
        'weight' => '0',
        'module' => 'imagecache',
        'action' => 'imagecache_crop',
        'data' => serialize([
          'xoffset' => '10',
          'yoffset' => '10',
        ]),
      ])->execute();

    $this->startCollectingMessages();
    $this->executeMigration('d6_imagecache_presets');
    $this->assertEquals([
      'error' => [
        'The Drupal 8 image crop effect does not support numeric values for x and y offsets. Use keywords to set crop effect offsets instead.',
      ],
    ], $this->migrateMessages);
  }

  /**
   * Assert that a given image effect is migrated.
   *
   * @param \Drupal\image\ImageEffectPluginCollection $collection
   *   Collection of effects
   * @param string $id
   *   Id that should exist in the collection.
   * @param array $config
   *   Expected configuration for the collection.
   *
   * @internal
   */
  protected function assertImageEffect(ImageEffectPluginCollection $collection, string $id, array $config): void {
    /** @var \Drupal\image\ConfigurableImageEffectBase $effect */
    foreach ($collection as $effect) {
      $effect_config = $effect->getConfiguration();

      if ($effect_config['id'] == $id && $effect_config['data'] == $config) {
        // We found this effect so the assertion is successful.
        return;
      }
    }
    // The loop did not find the effect so we it was not imported correctly.
    $this->fail('Effect ' . $id . ' did not import correctly');
  }

}
