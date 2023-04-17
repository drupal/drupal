<?php

namespace Drupal\Tests\block_content\Functional\Views;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Base class for all block_content tests.
 */
abstract class BlockContentTestBase extends ViewTestBase {

  /**
   * Admin user.
   *
   * @var object
   */
  protected $adminUser;

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer blocks',
    'administer block content',
    'access block library',
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'block_content',
    'block_content_test_views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['block_content_test_views']): void {
    parent::setUp($import_test_views, $modules);
    // Ensure the basic bundle exists. This is provided by the standard profile.
    $this->createBlockContentType(['id' => 'basic']);

    $this->adminUser = $this->drupalCreateUser($this->permissions);
  }

  /**
   * Creates a content block.
   *
   * @param array $values
   *   (optional) The values for the block_content entity.
   *
   * @return \Drupal\block_content\Entity\BlockContent
   *   Created content block.
   */
  protected function createBlockContent(array $values = []) {
    $status = 0;
    $values += [
      'info' => $this->randomMachineName(),
      'type' => 'basic',
      'langcode' => 'en',
    ];
    if ($block_content = BlockContent::create($values)) {
      $status = $block_content->save();
    }
    $this->assertEquals(SAVED_NEW, $status, new FormattableMarkup('Created block content %info.', ['%info' => $block_content->label()]));
    return $block_content;
  }

  /**
   * Creates a block type (bundle).
   *
   * @param array $values
   *   An array of settings to change from the defaults.
   *
   * @return \Drupal\block_content\Entity\BlockContentType
   *   Created block type.
   */
  protected function createBlockContentType(array $values = []) {
    // Find a non-existent random type name.
    if (!isset($values['id'])) {
      do {
        $id = strtolower($this->randomMachineName(8));
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
    $status = $bundle->save();
    block_content_add_body_field($bundle->id());

    $this->assertEquals(SAVED_NEW, $status, new FormattableMarkup('Created block content type %bundle.', ['%bundle' => $bundle->id()]));
    return $bundle;
  }

}
