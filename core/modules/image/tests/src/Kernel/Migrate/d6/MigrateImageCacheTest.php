<?php

namespace Drupal\Tests\image\Kernel\Migrate\d6;

use Drupal\Core\Database\Database;
use Drupal\image\Entity\ImageStyle;
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
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['image']);
  }

  /**
   * Tests that an exception is thrown when ImageCache is not installed.
   */
  public function testMissingTable() {
    $this->sourceDatabase->update('system')
      ->fields([
        'status' => 0,
      ])
      ->condition('name', 'imagecache')
      ->condition('type', 'module')
      ->execute();

    try {
      $this->getMigration('d6_imagecache_presets')
        ->getSourcePlugin()
        ->checkRequirements();
      $this->fail('Did not catch expected RequirementsException.');
    }
    catch (RequirementsException $e) {
      $this->pass('Caught expected RequirementsException: ' . $e->getMessage());
    }
  }

  /**
   * Test basic passing migrations.
   */
  public function testPassingMigration() {
    $this->executeMigration('d6_imagecache_presets');

    /** @var \Drupal\image\Entity\ImageStyle $style */
    $style = ImageStyle::load('big_blue_cheese');

    // Check basic Style info.
    $this->assertIdentical('big_blue_cheese', $style->get('name'), 'ImageStyle name set correctly');
    $this->assertIdentical('big_blue_cheese', $style->get('label'), 'ImageStyle label set correctly');

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
   * Test that missing actions causes failures.
   */
  public function testMissingEffectPlugin() {
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
    $this->assertEqual($messages[0]->level, MigrationInterface::MESSAGE_ERROR);
  }

  /**
   * Test that missing action's causes failures.
   */
  public function testInvalidCropValues() {
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
    $this->assertEqual([
      'error' => [
        'The Drupal 8 image crop effect does not support numeric values for x and y offsets. Use keywords to set crop effect offsets instead.',
      ],
    ], $this->migrateMessages);
  }

  /**
   * Assert that a given image effect is migrated.
   *
   * @param array $collection
   *   Collection of effects
   * @param $id
   *   Id that should exist in the collection.
   * @param $config
   *   Expected configuration for the collection.
   *
   * @return bool
   */
  protected function assertImageEffect($collection, $id, $config) {
    /** @var \Drupal\image\ConfigurableImageEffectBase $effect */
    foreach ($collection as $key => $effect) {
      $effect_config = $effect->getConfiguration();

      if ($effect_config['id'] == $id && $effect_config['data'] == $config) {
        // We found this effect so succeed and return.
        return $this->pass('Effect ' . $id . ' imported correctly');
      }
    }
    // The loop did not find the effect so we it was not imported correctly.
    return $this->fail('Effect ' . $id . ' did not import correctly');
  }

}
