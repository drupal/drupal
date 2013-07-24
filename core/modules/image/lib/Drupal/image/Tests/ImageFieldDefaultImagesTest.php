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
  function testDefaultImages() {
    // Create files to use as the default images.
    $files = $this->drupalGetTestFiles('image');
    $default_images = array();
    foreach (array('field', 'instance', 'instance2', 'field_new', 'instance_new') as $image_target) {
      $file = entity_create('file', (array) array_pop($files));
      $file->save();
      $default_images[$image_target] = $file;
    }

    // Create an image field and add an instance to the article content type.
    $field_name = strtolower($this->randomName());
    $field_settings = array(
      'default_image' => $default_images['field']->id(),
    );
    $instance_settings = array(
      'default_image' => $default_images['instance']->id(),
    );
    $widget_settings = array(
      'preview_image_style' => 'medium',
    );
    $instance = $this->createImageField($field_name, 'article', $field_settings, $instance_settings, $widget_settings);
    $field = field_info_field($field_name);

    // Add another instance with another default image to the page content type.
    $instance2 = entity_create('field_instance', array(
      'field_name' => $field->id(),
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => $instance->label(),
      'required' => $instance->required,
      'settings' => array(
        'default_image' => $default_images['instance2']->id(),
      ),
    ));
    $instance2->save();

    $widget_settings = entity_get_form_display($instance['entity_type'], $instance['bundle'], 'default')->getComponent($field['field_name']);
    entity_get_form_display('node', 'page', 'default')
      ->setComponent($field->id(), $widget_settings)
      ->save();
    entity_get_display('node', 'page', 'default')
      ->setComponent($field->id())
      ->save();

    // Confirm the defaults are present on the article field settings form.
    $this->drupalGet("admin/structure/types/manage/article/fields/$instance->id/field");
    $this->assertFieldByXpath(
      '//input[@name="field[settings][default_image][fids]"]',
      $default_images['field']->id(),
      format_string(
        'Article image field default equals expected file ID of @fid.',
        array('@fid' => $default_images['field']->id())
      )
    );
    // Confirm the defaults are present on the article field edit form.
    $this->drupalGet("admin/structure/types/manage/article/fields/$instance->id");
    $this->assertFieldByXpath(
      '//input[@name="instance[settings][default_image][fids]"]',
      $default_images['instance']->id(),
      format_string(
        'Article image field instance default equals expected file ID of @fid.',
        array('@fid' => $default_images['instance']->id())
      )
    );

    // Confirm the defaults are present on the page field settings form.
    $this->drupalGet("admin/structure/types/manage/page/fields/$instance->id/field");
    $this->assertFieldByXpath(
      '//input[@name="field[settings][default_image][fids]"]',
      $default_images['field']->id(),
      format_string(
        'Page image field default equals expected file ID of @fid.',
        array('@fid' => $default_images['field']->id())
      )
    );
    // Confirm the defaults are present on the page field edit form.
    $this->drupalGet("admin/structure/types/manage/page/fields/$instance2->id");
    $this->assertFieldByXpath(
      '//input[@name="instance[settings][default_image][fids]"]',
      $default_images['instance2']->id(),
      format_string(
        'Page image field instance default equals expected file ID of @fid.',
        array('@fid' => $default_images['instance2']->id())
      )
    );

    // Confirm that the image default is shown for a new article node.
    $article = $this->drupalCreateNode(array('type' => 'article'));
    $article_built = node_view($article);
    $this->assertEqual(
      $article_built[$field_name]['#items'][0]['target_id'],
      $default_images['instance']->id(),
      format_string(
        'A new article node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance']->id())
      )
    );

    // Confirm that the image default is shown for a new page node.
    $page = $this->drupalCreateNode(array('type' => 'page'));
    $page_built = node_view($page);
    $this->assertEqual(
      $page_built[$field_name]['#items'][0]['target_id'],
      $default_images['instance2']->id(),
      format_string(
        'A new page node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance2']->id())
      )
    );

    // Upload a new default for the field.
    $field['settings']['default_image'] = array($default_images['field_new']->id());
    $field->save();

    // Confirm that the new default is used on the article field settings form.
    $this->drupalGet("admin/structure/types/manage/article/fields/$instance->id/field");
    $this->assertFieldByXpath(
      '//input[@name="field[settings][default_image][fids]"]',
      $default_images['field_new']->id(),
      format_string(
        'Updated image field default equals expected file ID of @fid.',
        array('@fid' => $default_images['field_new']->id())
      )
    );

    // Reload the nodes and confirm the field instance defaults are used.
    $article_built = node_view($article = node_load($article->id(), TRUE));
    $page_built = node_view($page = node_load($page->id(), TRUE));
    $this->assertEqual(
      $article_built[$field_name]['#items'][0]['target_id'],
      $default_images['instance']->id(),
      format_string(
        'An existing article node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance']->id())
      )
    );
    $this->assertEqual(
      $page_built[$field_name]['#items'][0]['target_id'],
      $default_images['instance2']->id(),
      format_string(
        'An existing page node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance2']->id())
      )
    );

    // Upload a new default for the article's field instance.
    $instance['settings']['default_image'] = $default_images['instance_new']->id();
    $instance->save();

    // Confirm the new field instance default is used on the article field
    // admin form.
    $this->drupalGet("admin/structure/types/manage/article/fields/$instance->id");
    $this->assertFieldByXpath(
      '//input[@name="instance[settings][default_image][fids]"]',
      $default_images['instance_new']->id(),
      format_string(
        'Updated article image field instance default equals expected file ID of @fid.',
        array('@fid' => $default_images['instance_new']->id())
      )
    );

    // Reload the nodes.
    $article_built = node_view($article = node_load($article->id(),  TRUE));
    $page_built = node_view($page = node_load($page->id(), TRUE));

    // Confirm the article uses the new default.
    $this->assertEqual(
      $article_built[$field_name]['#items'][0]['target_id'],
      $default_images['instance_new']->id(),
      format_string(
        'An existing article node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance_new']->id())
      )
    );
    // Confirm the page remains unchanged.
    $this->assertEqual(
      $page_built[$field_name]['#items'][0]['target_id'],
      $default_images['instance2']->id(),
      format_string(
        'An existing page node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance2']->id())
      )
    );

    // Remove the instance default from articles.
    $instance['settings']['default_image'] = 0;
    $instance->save();

    // Confirm the article field instance default has been removed.
    $this->drupalGet("admin/structure/types/manage/article/fields/$instance->id");
    $this->assertFieldByXpath(
      '//input[@name="instance[settings][default_image][fids]"]',
      '',
      'Updated article image field instance default has been successfully removed.'
    );

    // Reload the nodes.
    $article_built = node_view($article = node_load($article->id(), TRUE));
    $page_built = node_view($page = node_load($page->id(), TRUE));
    // Confirm the article uses the new field (not instance) default.
    $this->assertEqual(
      $article_built[$field_name]['#items'][0]['target_id'],
      $default_images['field_new']->id(),
      format_string(
        'An existing article node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['field_new']->id())
      )
    );
    // Confirm the page remains unchanged.
    $this->assertEqual(
      $page_built[$field_name]['#items'][0]['target_id'],
      $default_images['instance2']->id(),
      format_string(
        'An existing page node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance2']->id())
      )
    );
  }

}
