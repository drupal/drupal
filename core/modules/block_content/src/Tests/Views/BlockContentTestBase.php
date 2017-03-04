<?php

namespace Drupal\block_content\Tests\Views;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Base class for all block_content tests.
 */
abstract class BlockContentTestBase extends ViewTestBase {

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
  protected $permissions = [
    'administer blocks',
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'block_content', 'block_content_test_views'];

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);
    // Ensure the basic bundle exists. This is provided by the standard profile.
    $this->createBlockContentType(['id' => 'basic']);

    $this->adminUser = $this->drupalCreateUser($this->permissions);

    if ($import_test_views) {
      ViewTestData::createTestViews(get_class($this), ['block_content_test_views']);
    }
  }

  /**
   * Creates a custom block.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the block_content, as
   *   used in entity_create().
   *
   * @return \Drupal\block_content\Entity\BlockContent
   *   Created custom block.
   */
  protected function createBlockContent(array $settings = []) {
    $status = 0;
    $settings += [
      'info' => $this->randomMachineName(),
      'type' => 'basic',
      'langcode' => 'en',
    ];
    if ($block_content = BlockContent::create($settings)) {
      $status = $block_content->save();
    }
    $this->assertEqual($status, SAVED_NEW, SafeMarkup::format('Created block content %info.', ['%info' => $block_content->label()]));
    return $block_content;
  }

  /**
   * Creates a custom block type (bundle).
   *
   * @param array $values
   *   An array of settings to change from the defaults.
   *
   * @return \Drupal\block_content\Entity\BlockContentType
   *   Created custom block type.
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
      'revision' => FALSE
    ];
    $bundle = BlockContentType::create($values);
    $status = $bundle->save();
    block_content_add_body_field($bundle->id());

    $this->assertEqual($status, SAVED_NEW, SafeMarkup::format('Created block content type %bundle.', ['%bundle' => $bundle->id()]));
    return $bundle;
  }

}
