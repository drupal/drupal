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
 *   image_style_create_derivative()
 *
 * image.module:
 *   image_style_load()
 *   image_style_save()
 *   image_style_delete()
 *   image_style_options()
 *   image_style_flush()
 *   image_effect_definition_load()
 *   image_effect_load()
 *   image_effect_save()
 *   image_effect_delete()
 *   image_filter_keyword()
 */

/**
 * This class provides methods specifically for testing Image's field handling.
 */
class ImageFieldTestBase extends WebTestBase {
  protected $admin_user;

  function setUp() {
    $modules = func_get_args();
    if (isset($modules[0]) && is_array($modules[0])) {
      $modules = $modules[0];
    }
    $modules[] = 'node';
    $modules[] = 'image';
    parent::setUp($modules);

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
      $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    }

    $this->admin_user = $this->drupalCreateUser(array('access content', 'access administration pages', 'administer site configuration', 'administer content types', 'administer nodes', 'create article content', 'edit any article content', 'delete any article content', 'administer image styles'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Create a new image field.
   *
   * @param $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param $type_name
   *   The node type that this field will be added to.
   * @param $field_settings
   *   A list of field settings that will be added to the defaults.
   * @param $instance_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   */
  function createImageField($name, $type_name, $field_settings = array(), $instance_settings = array(), $widget_settings = array()) {
    $field = array(
      'field_name' => $name,
      'type' => 'image',
      'settings' => array(),
      'cardinality' => !empty($field_settings['cardinality']) ? $field_settings['cardinality'] : 1,
    );
    $field['settings'] = array_merge($field['settings'], $field_settings);
    field_create_field($field);

    $instance = array(
      'field_name' => $field['field_name'],
      'entity_type' => 'node',
      'label' => $name,
      'bundle' => $type_name,
      'required' => !empty($instance_settings['required']),
      'settings' => array(),
      'widget' => array(
        'type' => 'image_image',
        'settings' => array(),
      ),
    );
    $instance['settings'] = array_merge($instance['settings'], $instance_settings);
    $instance['widget']['settings'] = array_merge($instance['widget']['settings'], $widget_settings);
    return field_create_instance($instance);
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
      'title' => $this->randomName(),
    );
    $edit['files[' . $field_name . '_' . LANGUAGE_NOT_SPECIFIED . '_0]'] = drupal_realpath($image->uri);
    $this->drupalPost('node/add/' . $type, $edit, t('Save'));

    // Retrieve ID of the newly created node from the current URL.
    $matches = array();
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    return isset($matches[1]) ? $matches[1] : FALSE;
  }
}
