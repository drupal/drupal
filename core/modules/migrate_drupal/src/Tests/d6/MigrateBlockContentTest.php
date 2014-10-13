<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateBlockContentTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\Core\Language\Language;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Language\LanguageInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade custom blocks.
 *
 * @group migrate_drupal
 */
class MigrateBlockContentTest extends MigrateDrupalTestBase {

  static $modules = array('block', 'block_content');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->prepareMigrations(array(
      'd6_filter_format' => array(
        array(array(2), array('full_html'))
      )
    ));
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_custom_block');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6Box.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 custom block to Drupal 8 migration.
   */
  public function testBlockMigration() {
    /** @var BlockContent $block */
    $block = entity_load('block_content', 1);
    $this->assertEqual('My block 1', $block->label());
    $this->assertEqual(1, $block->getRevisionId());
    $this->assertTrue(REQUEST_TIME <= $block->getChangedTime() && $block->getChangedTime() <= time());
    $this->assertEqual('en', $block->language()->getId());
    $this->assertEqual('<h3>My first custom block body</h3>', $block->body->value);
    $this->assertEqual('full_html', $block->body->format);

    $block = entity_load('block_content', 2);
    $this->assertEqual('My block 2', $block->label());
    $this->assertEqual(2, $block->getRevisionId());
    $this->assertTrue(REQUEST_TIME <= $block->getChangedTime() && $block->getChangedTime() <= time());
    $this->assertEqual('en', $block->language()->getId());
    $this->assertEqual('<h3>My second custom block body</h3>', $block->body->value);
    $this->assertEqual('full_html', $block->body->format);
  }

}
