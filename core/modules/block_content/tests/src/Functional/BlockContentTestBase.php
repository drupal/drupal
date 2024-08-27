<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Tests\BrowserTestBase;

/**
 * Sets up block content types.
 */
abstract class BlockContentTestBase extends BrowserTestBase {

  /**
   * Profile to use.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer blocks',
    'access block library',
    'administer block types',
    'administer block content',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'block_content'];

  /**
   * Whether or not to auto-create the basic block type during setup.
   *
   * @var bool
   */
  protected $autoCreateBasicBlockType = TRUE;

  /**
   * Sets the test up.
   */
  protected function setUp(): void {
    parent::setUp();
    if ($this->autoCreateBasicBlockType) {
      $this->createBlockContentType('basic', TRUE);
    }

    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Creates a content block.
   *
   * @param bool|string $title
   *   (optional) Title of block. When no value is given uses a random name.
   *   Defaults to FALSE.
   * @param string $bundle
   *   (optional) Bundle name. Defaults to 'basic'.
   * @param bool $save
   *   (optional) Whether to save the block. Defaults to TRUE.
   *
   * @return \Drupal\block_content\Entity\BlockContent
   *   Created content block.
   */
  protected function createBlockContent($title = FALSE, $bundle = 'basic', $save = TRUE) {
    $title = $title ?: $this->randomMachineName();
    $block_content = BlockContent::create([
      'info' => $title,
      'type' => $bundle,
      'langcode' => 'en',
    ]);
    if ($block_content && $save === TRUE) {
      $block_content->save();
    }
    return $block_content;
  }

  /**
   * Creates a block type (bundle).
   *
   * @param array|string $values
   *   The value to create the block content type. If $values is an array
   *   it should be like: ['id' => 'foo', 'label' => 'Foo']. If $values
   *   is a string, it will be considered that it represents the label.
   * @param bool $create_body
   *   Whether or not to create the body field
   *
   * @return \Drupal\block_content\Entity\BlockContentType
   *   Created block type.
   */
  protected function createBlockContentType($values, $create_body = FALSE) {
    if (is_array($values)) {
      if (!isset($values['id'])) {
        do {
          $id = $this->randomMachineName(8);
        } while (BlockContentType::load($id));
      }
      else {
        $id = $values['id'];
      }
      $values += [
        'id' => $id,
        'label' => $id,
        'revision' => FALSE,
      ];
      $bundle = BlockContentType::create($values);
    }
    else {
      $bundle = BlockContentType::create([
        'id' => $values,
        'label' => $values,
        'revision' => FALSE,
      ]);
    }
    $bundle->save();
    if ($create_body) {
      block_content_add_body_field($bundle->id());
    }
    return $bundle;
  }

}
