<?php

/**
 * @file
 * Contains \Drupal\custom_block\Tests\CustomBlockTranslationUITest.
 */

namespace Drupal\custom_block\Tests;

use Drupal\content_translation\Tests\ContentTranslationUITest;
use Drupal\custom_block\Entity\CustomBlock;

/**
 * Tests the Custom Block Translation UI.
 */
class CustomBlockTranslationUITest extends ContentTranslationUITest {

  /**
   * The name of the test block.
   */
  protected $name;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'language',
    'content_translation',
    'block',
    'field_ui',
    'custom_block'
  );

  /**
   * Declares test information.
   */
  public static function getInfo() {
    return array(
      'name' => 'Custom Block translation UI',
      'description' => 'Tests the node translation UI.',
      'group' => 'Custom Block',
    );
  }

  /**
   * Overrides \Drupal\simpletest\WebTestBase::setUp().
   */
  public function setUp() {
    $this->entityTypeId = 'custom_block';
    $this->bundle = 'basic';
    $this->name = drupal_strtolower($this->randomName());
    $this->testLanguageSelector = FALSE;
    parent::setUp();
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getTranslatorPermission().
   */
  public function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array(
      'translate any entity',
      'access administration pages',
      'administer blocks',
      'administer custom_block fields'
    ));
  }

  /**
   * Creates a custom block.
   *
   * @param string $title
   *   (optional) Title of block. When no value is given uses a random name.
   *   Defaults to FALSE.
   * @param string $bundle
   *   (optional) Bundle name. When no value is given, defaults to
   *   $this->bundle. Defaults to FALSE.
   *
   * @return \Drupal\custom_block\Entity\CustomBlock
   *   Created custom block.
   */
  protected function createCustomBlock($title = FALSE, $bundle = FALSE) {
    $title = ($title ? : $this->randomName());
    $bundle = ($bundle ? : $this->bundle);
    $custom_block = entity_create('custom_block', array(
      'info' => $title,
      'type' => $bundle,
      'langcode' => 'en'
    ));
    $custom_block->save();
    return $custom_block;
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getNewEntityValues().
   */
  protected function getNewEntityValues($langcode) {
    return array('info' => $this->name) + parent::getNewEntityValues($langcode);
  }

  /**
   * Test that no metadata is stored for a disabled bundle.
   */
  public function testDisabledBundle() {
    // Create a bundle that does not have translation enabled.
    $disabled_bundle = $this->randomName();
    $bundle = entity_create('custom_block_type', array(
      'id' => $disabled_bundle,
      'label' => $disabled_bundle,
      'revision' => FALSE
    ));
    $bundle->save();

    // Create a node for each bundle.
    $enabled_custom_block = $this->createCustomBlock();
    $disabled_custom_block = $this->createCustomBlock(FALSE, $bundle->id());

    // Make sure that only a single row was inserted into the
    // {content_translation} table.
    $rows = db_query('SELECT * FROM {content_translation}')->fetchAll();
    $this->assertEqual(1, count($rows));
    $this->assertEqual($enabled_custom_block->id(), reset($rows)->entity_id);
  }

}
