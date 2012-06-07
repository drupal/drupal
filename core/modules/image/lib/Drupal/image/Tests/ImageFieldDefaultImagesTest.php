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

  public static function getInfo() {
    return array(
      'name' => 'Image field default images tests',
      'description' => 'Tests setting up default images both to the field and field instance.',
      'group' => 'Image',
    );
  }

  function setUp() {
    parent::setUp(array('field_ui'));
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
      'default_image' => $default_images['field']->fid,
    );
    $instance_settings = array(
      'default_image' => $default_images['instance']->fid,
    );
    $widget_settings = array(
      'preview_image_style' => 'medium',
    );
    $this->createImageField($field_name, 'article', $field_settings, $instance_settings, $widget_settings);
    $field = field_info_field($field_name);
    $instance = field_info_instance('node', $field_name, 'article');

    // Add another instance with another default image to the page content type.
    $instance2 = array_merge($instance, array(
      'bundle' => 'page',
      'settings' => array(
        'default_image' => $default_images['instance2']->fid,
      ),
    ));
    field_create_instance($instance2);
    $instance2 = field_info_instance('node', $field_name, 'page');


    // Confirm the defaults are present on the article field admin form.
    $this->drupalGet("admin/structure/types/manage/article/fields/$field_name");
    $this->assertFieldByXpath(
      '//input[@name="field[settings][default_image][fid]"]',
      $default_images['field']->fid,
      format_string(
        'Article image field default equals expected file ID of @fid.',
        array('@fid' => $default_images['field']->fid)
      )
    );
    $this->assertFieldByXpath(
      '//input[@name="instance[settings][default_image][fid]"]',
      $default_images['instance']->fid,
      format_string(
        'Article image field instance default equals expected file ID of @fid.',
        array('@fid' => $default_images['instance']->fid)
      )
    );

    // Confirm the defaults are present on the page field admin form.
    $this->drupalGet("admin/structure/types/manage/page/fields/$field_name");
    $this->assertFieldByXpath(
      '//input[@name="field[settings][default_image][fid]"]',
      $default_images['field']->fid,
      format_string(
        'Page image field default equals expected file ID of @fid.',
        array('@fid' => $default_images['field']->fid)
      )
    );
    $this->assertFieldByXpath(
      '//input[@name="instance[settings][default_image][fid]"]',
      $default_images['instance2']->fid,
      format_string(
        'Page image field instance default equals expected file ID of @fid.',
        array('@fid' => $default_images['instance2']->fid)
      )
    );

    // Confirm that the image default is shown for a new article node.
    $article = $this->drupalCreateNode(array('type' => 'article'));
    $article_built = node_view($article);
    $this->assertEqual(
      $article_built[$field_name]['#items'][0]['fid'],
      $default_images['instance']->fid,
      format_string(
        'A new article node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance']->fid)
      )
    );

    // Confirm that the image default is shown for a new page node.
    $page = $this->drupalCreateNode(array('type' => 'page'));
    $page_built = node_view($page);
    $this->assertEqual(
      $page_built[$field_name]['#items'][0]['fid'],
      $default_images['instance2']->fid,
      format_string(
        'A new page node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance2']->fid)
      )
    );

    // Upload a new default for the field.
    $field['settings']['default_image'] = $default_images['field_new']->fid;
    field_update_field($field);

    // Confirm that the new field default is used on the article admin form.
    $this->drupalGet("admin/structure/types/manage/article/fields/$field_name");
    $this->assertFieldByXpath(
      '//input[@name="field[settings][default_image][fid]"]',
      $default_images['field_new']->fid,
      format_string(
        'Updated image field default equals expected file ID of @fid.',
        array('@fid' => $default_images['field_new']->fid)
      )
    );

    // Reload the nodes and confirm the field instance defaults are used.
    $article_built = node_view($article = node_load($article->nid, NULL, $reset = TRUE));
    $page_built = node_view($page = node_load($page->nid, NULL, $reset = TRUE));
    $this->assertEqual(
      $article_built[$field_name]['#items'][0]['fid'],
      $default_images['instance']->fid,
      format_string(
        'An existing article node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance']->fid)
      )
    );
    $this->assertEqual(
      $page_built[$field_name]['#items'][0]['fid'],
      $default_images['instance2']->fid,
      format_string(
        'An existing page node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance2']->fid)
      )
    );

    // Upload a new default for the article's field instance.
    $instance['settings']['default_image'] = $default_images['instance_new']->fid;
    field_update_instance($instance);

    // Confirm the new field instance default is used on the article field
    // admin form.
    $this->drupalGet("admin/structure/types/manage/article/fields/$field_name");
    $this->assertFieldByXpath(
      '//input[@name="instance[settings][default_image][fid]"]',
      $default_images['instance_new']->fid,
      format_string(
        'Updated article image field instance default equals expected file ID of @fid.',
        array('@fid' => $default_images['instance_new']->fid)
      )
    );

    // Reload the nodes.
    $article_built = node_view($article = node_load($article->nid, NULL, $reset = TRUE));
    $page_built = node_view($page = node_load($page->nid, NULL, $reset = TRUE));

    // Confirm the article uses the new default.
    $this->assertEqual(
      $article_built[$field_name]['#items'][0]['fid'],
      $default_images['instance_new']->fid,
      format_string(
        'An existing article node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance_new']->fid)
      )
    );
    // Confirm the page remains unchanged.
    $this->assertEqual(
      $page_built[$field_name]['#items'][0]['fid'],
      $default_images['instance2']->fid,
      format_string(
        'An existing page node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance2']->fid)
      )
    );

    // Remove the instance default from articles.
    $instance['settings']['default_image'] = NULL;
    field_update_instance($instance);

    // Confirm the article field instance default has been removed.
    $this->drupalGet("admin/structure/types/manage/article/fields/$field_name");
    $this->assertFieldByXpath(
      '//input[@name="instance[settings][default_image][fid]"]',
      '',
      'Updated article image field instance default has been successfully removed.'
    );

    // Reload the nodes.
    $article_built = node_view($article = node_load($article->nid, NULL, $reset = TRUE));
    $page_built = node_view($page = node_load($page->nid, NULL, $reset = TRUE));
    // Confirm the article uses the new field (not instance) default.
    $this->assertEqual(
      $article_built[$field_name]['#items'][0]['fid'],
      $default_images['field_new']->fid,
      format_string(
        'An existing article node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['field_new']->fid)
      )
    );
    // Confirm the page remains unchanged.
    $this->assertEqual(
      $page_built[$field_name]['#items'][0]['fid'],
      $default_images['instance2']->fid,
      format_string(
        'An existing page node without an image has the expected default image file ID of @fid.',
        array('@fid' => $default_images['instance2']->fid)
      )
    );
  }
}
