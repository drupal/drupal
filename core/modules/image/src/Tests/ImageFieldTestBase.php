<?php

/**
 * @file
 * Definition of Drupal\image\Tests\ImageFieldTestBase.
 */

namespace Drupal\image\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * TODO: Test the following functions.
 *
 * image.effects.inc:
 *   image_style_generate()
 *   \Drupal\image\ImageStyleInterface::createDerivative()
 *
 * image.module:
 *   image_style_options()
 *   \Drupal\image\ImageStyleInterface::flush()
 *   image_filter_keyword()
 */

/**
 * This class provides methods specifically for testing Image's field handling.
 */
abstract class ImageFieldTestBase extends WebTestBase {

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = TRUE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'image', 'field_ui', 'image_module_test');

  protected $admin_user;

  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }

    $this->admin_user = $this->drupalCreateUser(array('access content', 'access administration pages', 'administer site configuration', 'administer content types', 'administer node fields', 'administer nodes', 'create article content', 'edit any article content', 'delete any article content', 'administer image styles'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Create a new image field.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $type_name
   *   The node type that this field will be added to.
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   */
  function createImageField($name, $type_name, $storage_settings = array(), $field_settings = array(), $widget_settings = array()) {
    entity_create('field_storage_config', array(
      'field_name' => $name,
      'entity_type' => 'node',
      'type' => 'image',
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ))->save();

    $field_config = entity_create('field_config', array(
      'field_name' => $name,
      'label' => $name,
      'entity_type' => 'node',
      'bundle' => $type_name,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
    ));
    $field_config->save();

    entity_get_form_display('node', $type_name, 'default')
      ->setComponent($name, array(
        'type' => 'image_image',
        'settings' => $widget_settings,
      ))
      ->save();

    entity_get_display('node', $type_name, 'default')
      ->setComponent($name)
      ->save();

    return $field_config;

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
  function previewNodeImage($image, $field_name, $type) {
    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
    );
    $edit['files[' . $field_name . '_0]'] = drupal_realpath($image->uri);
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
   */
  function uploadNodeImage($image, $field_name, $type) {
    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
    );
    $edit['files[' . $field_name . '_0]'] = drupal_realpath($image->uri);
    $this->drupalPostForm('node/add/' . $type, $edit, t('Save and publish'));

    // Retrieve ID of the newly created node from the current URL.
    $matches = array();
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    return isset($matches[1]) ? $matches[1] : FALSE;
  }

}
