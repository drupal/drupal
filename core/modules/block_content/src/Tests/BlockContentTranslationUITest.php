<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\BlockContentTranslationUITest.
 */

namespace Drupal\block_content\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\content_translation\Tests\ContentTranslationUITest;

/**
 * Tests the node translation UI.
 *
 * @group block_content
 */
class BlockContentTranslationUITest extends ContentTranslationUITest {

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
    'block_content'
  );

  /**
   * Overrides \Drupal\simpletest\WebTestBase::setUp().
   */
  protected function setUp() {
    $this->entityTypeId = 'block_content';
    $this->bundle = 'basic';
    $this->testLanguageSelector = FALSE;
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function setupBundle() {
    // Create the basic bundle since it is provided by standard.
    $bundle = entity_create('block_content_type', array(
      'id' => $this->bundle,
      'label' => $this->bundle,
      'revision' => FALSE
    ));
    $bundle->save();
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getTranslatorPermission().
   */
  public function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array(
      'translate any entity',
      'access administration pages',
      'administer blocks',
      'administer block_content fields'
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
   * @return \Drupal\block_content\Entity\BlockContent
   *   Created custom block.
   */
  protected function createBlockContent($title = FALSE, $bundle = FALSE) {
    $title = ($title ? : $this->randomMachineName());
    $bundle = ($bundle ? : $this->bundle);
    $block_content = entity_create('block_content', array(
      'info' => $title,
      'type' => $bundle,
      'langcode' => 'en'
    ));
    $block_content->save();
    return $block_content;
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getNewEntityValues().
   */
  protected function getNewEntityValues($langcode) {
    return array('info' => Unicode::strtolower($this->randomMachineName())) + parent::getNewEntityValues($langcode);
  }

  /**
   * Returns an edit array containing the values to be posted.
   */
  protected function getEditValues($values, $langcode, $new = FALSE) {
    $edit = parent::getEditValues($values, $langcode, $new);
    foreach ($edit as $property => $value) {
      if ($property == 'info') {
        $edit['info[0][value]'] = $value;
        unset($edit[$property]);
      }
    }
    return $edit;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestBasicTranslation() {
    parent::doTestBasicTranslation();

    // Ensure that a block translation can be created using the same description
    // as in the original language.
    $default_langcode = $this->langcodes[0];
    $values = $this->getNewEntityValues($default_langcode);
    $storage = \Drupal::entityManager()->getStorage($this->entityTypeId);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $storage->create(array('type' => 'basic') + $values);
    $entity->save();
    $entity->addTranslation('it', $values);

    try {
      $message = 'Blocks can have translations with the same "info" value.';
      $entity->save();
      $this->pass($message);
    }
    catch (\Exception $e) {
      $this->fail($message);
    }
  }

  /**
   * Test that no metadata is stored for a disabled bundle.
   */
  public function testDisabledBundle() {
    // Create a bundle that does not have translation enabled.
    $disabled_bundle = $this->randomMachineName();
    $bundle = entity_create('block_content_type', array(
      'id' => $disabled_bundle,
      'label' => $disabled_bundle,
      'revision' => FALSE
    ));
    $bundle->save();

    // Create a node for each bundle.
    $enabled_block_content = $this->createBlockContent();
    $disabled_block_content = $this->createBlockContent(FALSE, $bundle->id());

    // Make sure that only a single row was inserted into the
    // {content_translation} table.
    $rows = db_query('SELECT * FROM {content_translation}')->fetchAll();
    $this->assertEqual(1, count($rows));
    $this->assertEqual($enabled_block_content->id(), reset($rows)->entity_id);
  }

}
