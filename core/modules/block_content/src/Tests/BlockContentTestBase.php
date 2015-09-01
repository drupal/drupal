<?php

/**
 * @file
 * Contains \Drupal\block_content\Tests\BlockContentTestBase.
 */

namespace Drupal\block_content\Tests;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\WebTestBase;

/**
 * Sets up block content types.
 */
abstract class BlockContentTestBase extends WebTestBase {

  /**
   * Profile to use.
   */
  protected $profile = 'testing';

  /**
   * Admin user
   *
   * @var object
   */
  protected $adminUser;

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = array(
    'administer blocks'
  );

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'block_content');

  /**
   * Whether or not to auto-create the basic block type during setup.
   *
   * @var bool
   */
  protected $autoCreateBasicBlockType = TRUE;

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    if ($this->autoCreateBasicBlockType) {
      $this->createBlockContentType('basic', TRUE);
    }

    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Creates a custom block.
   *
   * @param string $title
   *   (optional) Title of block. When no value is given uses a random name.
   *   Defaults to FALSE.
   * @param string $bundle
   *   (optional) Bundle name. Defaults to 'basic'.
   *
   * @return \Drupal\block_content\Entity\BlockContent
   *   Created custom block.
   */
  protected function createBlockContent($title = FALSE, $bundle = 'basic') {
    $title = ($title ? : $this->randomMachineName());
    if ($block_content = entity_create('block_content', array(
      'info' => $title,
      'type' => $bundle,
      'langcode' => 'en'
    ))) {
      $block_content->save();
    }
    return $block_content;
  }

  /**
   * Creates a custom block type (bundle).
   *
   * @param string $label
   *   The block type label.
   * @param bool $create_body
   *   Whether or not to create the body field
   *
   * @return \Drupal\block_content\Entity\BlockContentType
   *   Created custom block type.
   */
  protected function createBlockContentType($label, $create_body = FALSE) {
    $bundle = entity_create('block_content_type', array(
      'id' => $label,
      'label' => $label,
      'revision' => FALSE,
    ));
    $bundle->save();
    if ($create_body) {
      block_content_add_body_field($bundle->id());
    }
    return $bundle;
  }

}
