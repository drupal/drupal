<?php

/**
 * @file
 * Definition of Drupal\image\Tests\ImageFieldDefaultImagesTest.
 */

namespace Drupal\image\Tests;

/**
 * Tests default image settings.
 */
class ImageFieldDefaultImagesTest extends ImageFieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_ui');

  public static function getInfo() {
    return array(
      'name' => 'Image field default images tests',
      'description' => 'Tests setting up default images both to the field and field instance.',
      'group' => 'Image',
    );
  }

  /**
   * Tests CRUD for fields and fields instances with default images.
   */
  public function testDefaultImages() {
    // Create files to use as the default images.
    $files = $this->drupalGetTestFiles('image');
    // Create 10 files so the default image fids are not a single value.
    for ($i = 1; $i <= 10; $i++) {
      $filename = $this->randomName() . "$i";
      $desired_filepath = 'public://' . $filename;
      file_unmanaged_copy($files[0]->uri, $desired_filepath, FILE_EXISTS_ERROR);
      $file = entity_create('file', array('uri' => $desired_filepath, 'filename' => $filename, 'name' => $filename));
      $file->save();
    }
    $default_images = array();
    foreach (array('field', 'instance', 'instance2', 'field_new', 'instance_new') as $image_target) {
      $file = entity_create('file', (array) array_pop($files));
      $file->save();
      $default_images[$image_target] = $file;
    }

    // Create an image field and add an instance to the article content type.
    $field_name = strtolower($this->randomName());
    $field_settings['default_image'] = array(
      'fid' => $default_images['field']->id(),
      'alt' => '',
      'title' => '',
      'width' => 0,
      'height' => 0,
    );
    $instance_settings['default_image'] = array(
      'fid' => $default_images['instance']->id(),
      'alt' => '',
      'title' => '',
      'width' => 0,
      'height' => 0,
    );
    $widget_settings = array(
      'preview_image_style' => 'medium',
    );
    $instance = $this->createImageField($field_name, 'article', $field_settings, $instance_settings, $widget_settings);

    // The instance default image id should be 2.
    $default_image = $instance->getSetting('default_image');
    $this->assertEqual($default_image['fid'], $default_images['instance']->id());

    // Also test \Drupal\field\Entity\FieldInstanceConfig::getSetting().
    $instance_field_settings = $instance->getSettings();
    $this->assertEqual($instance_field_settings['default_image']['fid'], $default_images['instance']->id());

    $field = $instance->getFieldStorageDefinition();

    // The field default image id should be 1.
    $default_image = $field->getSetting('default_image');
    $this->assertEqual($default_image['fid'], $default_images['field']->id());

    // Also test \Drupal\field\Entity\FieldConfig::getSettings().
    $field_field_settings = $field->getSettings();
    $this->assertEqual($field_field_settings['default_image']['fid'], $default_images['field']->id());

    // Add another instance with another default image to the page content type.
    $instance2 = entity_create('field_instance_config', array(
      'field_name' => $field->name,
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => $instance->label(),
      'required' => $instance->required,
      'settings' => array(
        'default_image' => array(
          'fid' => $default_images['instance2']->id(),
          'alt' => '',
          'title' => '',
          'width' => 0,
          'height' => 0,
        ),
      ),
    ));
    $instance2->save();

    $widget_settings = entity_get_form_display('node', $instance->bundle, 'default')->getComponent($field_name);
    entity_get_form_display('node', 'page', 'default')
      ->setComponent($field_name, $widget_settings)
      ->save();
    entity_get_display('node', 'page', 'default')
      ->setComponent($field_name)
      ->save();

    // Confirm the defaults are present on the article field settings form.
    $instance_id = $instance->id();
    $this->drupalGet("admin/structure/types/manage/article/fields/$instance_id/field");
    $this->assertFieldByXpath(
      '//input[@name="field[settings][default_image][fid][fids]"]',
      $default_images['field']->id(),
      format_string(
        'Article image field default equals expected file ID of @fid.',
        array('@fid' => $default_images['field']->id())
      )
    );
    // Confirm the defaults are present on the article field edit form.
    $this->drupalGet("admin/structure/types/manage/article/fields/$instance_id");
    $this->assertFieldByXpath(
      '//input[@name="instance[settings][default_image][fid][fids]"]',
      $default_images['instance']->id(),
      format_string(
        'Article image field instance default equals expected file ID of @fid.',
        array('@fid' => $default_images['instance']->id())
      )
    );

    // Confirm the defaults are present on the page field settings form.
    $this->drupalGet("admin/structure/types/manage/page/fields/$instance_id/field");
    $this->assertFieldByXpath(
      '//input[@name="field[settings][default_image][fid][fids]"]',
      $default_images['field']->id(),
      format_string(
        'Page image field default equals expected file ID of @fid.',
        array('@fid' => $default_images['field']->id())
      )
    );
    // Confirm the defaults are present on the page field edit form.
    $instance2_id = $instance2->id();
    $this->drupalGet("admin/structure/types/manage/page/fields/$instance2_id");
    $this->assertFieldByXpath(
      '//input[@name="instance[settings][default_image][fid][fids]"]',
      $default_images['instance2']->id(),
      format_string(
        'Page image field instance default equals expected file ID of @fid.',
        array('@fid' => $default_images['instance2']->id())
      )
    );

    // Confirm that the image default is shown for a new article node.
    $article = $this->drupalCreateNode(array('type' => 'article'));
    $article_built = $this->drupalBuildEntityView($article);
    $this->assertEqual(
      $article_built[$field_name]['#items'][0]->target_id,
      $default_images['instance']->id(),
      format_string(
        'A new article node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance']->id())
      )
    );

    // Confirm that the image default is shown for a new page node.
    $page = $this->drupalCreateNode(array('type' => 'page'));
    $page_built = $this->drupalBuildEntityView($page);
    $this->assertEqual(
      $page_built[$field_name]['#items'][0]->target_id,
      $default_images['instance2']->id(),
      format_string(
        'A new page node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance2']->id())
      )
    );

    // Upload a new default for the field.
    $field->settings['default_image']['fid'] = $default_images['field_new']->id();
    $field->save();

    // Confirm that the new default is used on the article field settings form.
    $this->drupalGet("admin/structure/types/manage/article/fields/$instance_id/field");
    $this->assertFieldByXpath(
      '//input[@name="field[settings][default_image][fid][fids]"]',
      $default_images['field_new']->id(),
      format_string(
        'Updated image field default equals expected file ID of @fid.',
        array('@fid' => $default_images['field_new']->id())
      )
    );

    // Reload the nodes and confirm the field instance defaults are used.
    $article_built = $this->drupalBuildEntityView($article = node_load($article->id(), TRUE));
    $page_built = $this->drupalBuildEntityView($page = node_load($page->id(), TRUE));
    $this->assertEqual(
      $article_built[$field_name]['#items'][0]->target_id,
      $default_images['instance']->id(),
      format_string(
        'An existing article node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance']->id())
      )
    );
    $this->assertEqual(
      $page_built[$field_name]['#items'][0]->target_id,
      $default_images['instance2']->id(),
      format_string(
        'An existing page node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance2']->id())
      )
    );

    // Upload a new default for the article's field instance.
    $instance->settings['default_image']['fid'] = $default_images['instance_new']->id();
    $instance->save();

    // Confirm the new field instance default is used on the article field
    // admin form.
    $this->drupalGet("admin/structure/types/manage/article/fields/$instance_id");
    $this->assertFieldByXpath(
      '//input[@name="instance[settings][default_image][fid][fids]"]',
      $default_images['instance_new']->id(),
      format_string(
        'Updated article image field instance default equals expected file ID of @fid.',
        array('@fid' => $default_images['instance_new']->id())
      )
    );

    // Reload the nodes.
    $article_built = $this->drupalBuildEntityView($article = node_load($article->id(),  TRUE));
    $page_built = $this->drupalBuildEntityView($page = node_load($page->id(), TRUE));

    // Confirm the article uses the new default.
    $this->assertEqual(
      $article_built[$field_name]['#items'][0]->target_id,
      $default_images['instance_new']->id(),
      format_string(
        'An existing article node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance_new']->id())
      )
    );
    // Confirm the page remains unchanged.
    $this->assertEqual(
      $page_built[$field_name]['#items'][0]->target_id,
      $default_images['instance2']->id(),
      format_string(
        'An existing page node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance2']->id())
      )
    );

    // Remove the instance default from articles.
    $instance->settings['default_image']['fid'] = 0;
    $instance->save();

    // Confirm the article field instance default has been removed.
    $this->drupalGet("admin/structure/types/manage/article/fields/$instance_id");
    $this->assertFieldByXpath(
      '//input[@name="instance[settings][default_image][fid][fids]"]',
      '',
      'Updated article image field instance default has been successfully removed.'
    );

    // Reload the nodes.
    $article_built = $this->drupalBuildEntityView($article = node_load($article->id(), TRUE));
    $page_built = $this->drupalBuildEntityView($page = node_load($page->id(), TRUE));
    // Confirm the article uses the new field (not instance) default.
    $this->assertEqual(
      $article_built[$field_name]['#items'][0]->target_id,
      $default_images['field_new']->id(),
      format_string(
        'An existing article node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['field_new']->id())
      )
    );
    // Confirm the page remains unchanged.
    $this->assertEqual(
      $page_built[$field_name]['#items'][0]->target_id,
      $default_images['instance2']->id(),
      format_string(
        'An existing page node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance2']->id())
      )
    );
  }

  /**
   * Tests image field and instance having an invalid default image.
   */
  public  function testInvalidDefaultImage() {
    $field = array(
      'name' => drupal_strtolower($this->randomName()),
      'entity_type' => 'node',
      'type' => 'image',
      'settings' => array(
        'default_image' => array(
          'fid' => 100000,
        )
      ),
    );
    $instance = array(
      'field_name' => $field['name'],
      'label' => $this->randomName(),
      'entity_type' => 'node',
      'bundle' => 'page',
      'settings' => array(
        'default_image' => array(
          'fid' => 100000,
        )
      ),
    );
    $field_config = entity_create('field_config', $field);
    $field_config->save();
    $settings = $field_config->getSettings();
    // The non-existent default image should not be saved.
    $this->assertNull($settings['default_image']['fid']);

    $field_instance_config = entity_create('field_instance_config', $instance);
    $field_instance_config->save();
    $settings = $field_instance_config->getSettings();
    // The non-existent default image should not be saved.
    $this->assertNull($settings['default_image']['fid']);

  }

}
