<?php

/**
 * @file
 * Contains \Drupal\custom_block\Tests\CustomBlockTestBase.
 */

namespace Drupal\custom_block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Sets up page and article content types.
 */
abstract class CustomBlockTestBase extends WebTestBase {

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
  public static $modules = array('block', 'custom_block');

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
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
   * @return \Drupal\custom_block\Entity\CustomBlock
   *   Created custom block.
   */
  protected function createCustomBlock($title = FALSE, $bundle = 'basic') {
    $title = ($title ? : $this->randomName());
    if ($custom_block = entity_create('custom_block', array(
      'info' => $title,
      'type' => $bundle,
      'langcode' => 'en'
    ))) {
      $custom_block->save();
    }
    return $custom_block;
  }

  /**
   * Creates a custom block type (bundle).
   *
   * @param string $label
   *   The block type label.
   *
   * @return \Drupal\custom_block\Entity\CustomBlockType
   *   Created custom block type.
   */
  protected function createCustomBlockType($label) {
    $bundle = entity_create('custom_block_type', array(
      'id' => $label,
      'label' => $label,
      'revision' => FALSE
    ));
    $bundle->save();
    return $bundle;
  }

}
