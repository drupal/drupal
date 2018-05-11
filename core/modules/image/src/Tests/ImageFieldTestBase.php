<?php

namespace Drupal\image\Tests;

@trigger_error('The ' . __NAMESPACE__ . '\ImageFieldTestBase class is deprecated in Drupal 8.5.x and will be removed before Drupal 9.0.0. Use \Drupal\Tests\image\Functional\ImageFieldTestBase instead. See https://www.drupal.org/node/2863626.', E_USER_DEPRECATED);

use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\simpletest\WebTestBase;

/**
 * TODO: Test the following functions.
 *
 * In file:
 * - image.effects.inc:
 *   image_style_generate()
 *   \Drupal\image\ImageStyleInterface::createDerivative()
 *
 * - image.module:
 *   image_style_options()
 *   \Drupal\image\ImageStyleInterface::flush()
 *   image_filter_keyword()
 */

/**
 * This class provides methods specifically for testing Image's field handling.
 *
 * @deprecated Scheduled for removal in Drupal 9.0.0.
 *   Use \Drupal\Tests\image\Functional\ImageFieldTestBase instead.
 */
abstract class ImageFieldTestBase extends WebTestBase {

  use ImageFieldCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'image', 'field_ui', 'image_module_test'];

  /**
   * An user with permissions to administer content types and image styles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    $this->adminUser = $this->drupalCreateUser(['access content', 'access administration pages', 'administer site configuration', 'administer content types', 'administer node fields', 'administer nodes', 'create article content', 'edit any article content', 'delete any article content', 'administer image styles', 'administer node display']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Preview an image in a node.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   A file object representing the image to upload.
   * @param string $field_name
   *   Name of the image field the image should be attached to.
   * @param string $type
   *   The type of node to create.
   */
  public function previewNodeImage($image, $field_name, $type) {
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
    ];
    $edit['files[' . $field_name . '_0]'] = \Drupal::service('file_system')->realpath($image->uri);
    $this->drupalPostForm('node/add/' . $type, $edit, t('Preview'));
  }

  /**
   * Upload an image to a node.
   *
   * @param $image
   *   A file object representing the image to upload.
   * @param $field_name
   *   Name of the image field the image should be attached to.
   * @param $type
   *   The type of node to create.
   * @param $alt
   *   The alt text for the image. Use if the field settings require alt text.
   */
  public function uploadNodeImage($image, $field_name, $type, $alt = '') {
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
    ];
    $edit['files[' . $field_name . '_0]'] = \Drupal::service('file_system')->realpath($image->uri);
    $this->drupalPostForm('node/add/' . $type, $edit, t('Save'));
    if ($alt) {
      // Add alt text.
      $this->drupalPostForm(NULL, [$field_name . '[0][alt]' => $alt], t('Save'));
    }

    // Retrieve ID of the newly created node from the current URL.
    $matches = [];
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    return isset($matches[1]) ? $matches[1] : FALSE;
  }

  /**
   * Retrieves the fid of the last inserted file.
   */
  protected function getLastFileId() {
    return (int) db_query('SELECT MAX(fid) FROM {file_managed}')->fetchField();
  }

}
