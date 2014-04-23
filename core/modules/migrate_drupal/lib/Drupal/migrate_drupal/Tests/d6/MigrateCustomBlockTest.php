<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateCustomBlockTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\Core\Language\Language;
use Drupal\custom_block\Entity\CustomBlock;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests the Drupal 6 custom block to Drupal 8 migration.
 */
class MigrateCustomBlockTest extends MigrateDrupalTestBase {

  static $modules = array('block', 'custom_block');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate custom blocks.',
      'description'  => 'Upgrade custom blocks.',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->prepareIdMappings(array(
      'd6_filter_format' => array(
        array(array(2), array('full_html'))
      )
    ));
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_custom_block');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6Box.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 custom block to Drupal 8 migration.
   */
  public function testBlockMigration() {
    /** @var CustomBlock $block */
    $block = entity_load('custom_block', 1);
    $this->assertEqual('My block 1', $block->label());
    $this->assertEqual(1, $block->getRevisionId());
    $this->assertTrue(REQUEST_TIME <= $block->getChangedTime() && $block->getChangedTime() <= time());
    $this->assertEqual(Language::LANGCODE_NOT_SPECIFIED, $block->language()->id);
    $this->assertEqual('<h3>My custom block body</h3>', $block->body->value);
    $this->assertEqual('full_html', $block->body->format);
  }

}
