<?php

/**
 * @file
 * Definition of Drupal\image\Tests\ImageAdminStylesTest.
 */

namespace Drupal\image\Tests;

/**
 * Tests creation, deletion, and editing of image styles and effects.
 */
class ImageAdminStylesTest extends ImageFieldTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Image styles and effects UI configuration',
      'description' => 'Tests creation, deletion, and editing of image styles and effects at the UI level.',
      'group' => 'Image',
    );
  }

  /**
   * Given an image style, generate an image.
   */
  function createSampleImage($style) {
    static $file_path;

    // First, we need to make sure we have an image in our testing
    // file directory. Copy over an image on the first run.
    if (!isset($file_path)) {
      $files = $this->drupalGetTestFiles('image');
      $file = reset($files);
      $file_path = file_unmanaged_copy($file->uri);
    }

    return image_style_url($style['name'], $file_path) ? $file_path : FALSE;
  }

  /**
   * Count the number of images currently create for a style.
   */
  function getImageCount($style) {
    return count(file_scan_directory('public://styles/' . $style['name'], '/.*/'));
  }

  /**
   * Test creating an image style with a numeric name and ensuring it can be
   * applied to an image.
   */
  function testNumericStyleName() {
    $style_name = rand();
    $edit = array(
      'name' => $style_name,
    );
    $this->drupalPost('admin/config/media/image-styles/add', $edit, t('Create new style'));
    $this->assertRaw(t('Style %name was created.', array('%name' => $style_name)), t('Image style successfully created.'));
    $options = image_style_options();
    $this->assertTrue(array_key_exists($style_name, $options), t('Array key %key exists.', array('%key' => $style_name)));
  }

  /**
   * General test to add a style, add/remove/edit effects to it, then delete it.
   */
  function testStyle() {
    // Setup a style to be created and effects to add to it.
    $style_name = strtolower($this->randomName(10));
    $style_path = 'admin/config/media/image-styles/edit/' . $style_name;
    $effect_edits = array(
      'image_resize' => array(
        'data[width]' => 100,
        'data[height]' => 101,
      ),
      'image_scale' => array(
        'data[width]' => 110,
        'data[height]' => 111,
        'data[upscale]' => 1,
      ),
      'image_scale_and_crop' => array(
        'data[width]' => 120,
        'data[height]' => 121,
      ),
      'image_crop' => array(
        'data[width]' => 130,
        'data[height]' => 131,
        'data[anchor]' => 'center-center',
      ),
      'image_desaturate' => array(
        // No options for desaturate.
      ),
      'image_rotate' => array(
        'data[degrees]' => 5,
        'data[random]' => 1,
        'data[bgcolor]' => '#FFFF00',
      ),
    );

    // Add style form.

    $edit = array(
      'name' => $style_name,
    );
    $this->drupalPost('admin/config/media/image-styles/add', $edit, t('Create new style'));
    $this->assertRaw(t('Style %name was created.', array('%name' => $style_name)), t('Image style successfully created.'));

    // Add effect form.

    // Add each sample effect to the style.
    foreach ($effect_edits as $effect => $edit) {
      // Add the effect.
      $this->drupalPost($style_path, array('new' => $effect), t('Add'));
      if (!empty($edit)) {
        $this->drupalPost(NULL, $edit, t('Add effect'));
      }
    }

    // Edit effect form.

    // Revisit each form to make sure the effect was saved.
    drupal_static_reset('image_styles');
    $style = image_style_load($style_name);

    foreach ($style['effects'] as $ieid => $effect) {
      $this->drupalGet($style_path . '/effects/' . $ieid);
      foreach ($effect_edits[$effect['name']] as $field => $value) {
        $this->assertFieldByName($field, $value, t('The %field field in the %effect effect has the correct value of %value.', array('%field' => $field, '%effect' => $effect['name'], '%value' => $value)));
      }
    }

    // Image style overview form (ordering and renaming).

    // Confirm the order of effects is maintained according to the order we
    // added the fields.
    $effect_edits_order = array_keys($effect_edits);
    $effects_order = array_values($style['effects']);
    $order_correct = TRUE;
    foreach ($effects_order as $index => $effect) {
      if ($effect_edits_order[$index] != $effect['name']) {
        $order_correct = FALSE;
      }
    }
    $this->assertTrue($order_correct, t('The order of the effects is correctly set by default.'));

    // Test the style overview form.
    // Change the name of the style and adjust the weights of effects.
    $style_name = strtolower($this->randomName(10));
    $weight = count($effect_edits);
    $edit = array(
      'name' => $style_name,
    );
    foreach ($style['effects'] as $ieid => $effect) {
      $edit['effects[' . $ieid . '][weight]'] = $weight;
      $weight--;
    }

    // Create an image to make sure it gets flushed after saving.
    $image_path = $this->createSampleImage($style);
    $this->assertEqual($this->getImageCount($style), 1, t('Image style %style image %file successfully generated.', array('%style' => $style['name'], '%file' => $image_path)));

    $this->drupalPost($style_path, $edit, t('Update style'));

    // Note that after changing the style name, the style path is changed.
    $style_path = 'admin/config/media/image-styles/edit/' . $style_name;

    // Check that the URL was updated.
    $this->drupalGet($style_path);
    $this->assertResponse(200, t('Image style %original renamed to %new', array('%original' => $style['name'], '%new' => $style_name)));

    // Check that the image was flushed after updating the style.
    // This is especially important when renaming the style. Make sure that
    // the old image directory has been deleted.
    $this->assertEqual($this->getImageCount($style), 0, t('Image style %style was flushed after renaming the style and updating the order of effects.', array('%style' => $style['name'])));

    // Load the style by the new name with the new weights.
    drupal_static_reset('image_styles');
    $style = image_style_load($style_name);

    // Confirm the new style order was saved.
    $effect_edits_order = array_reverse($effect_edits_order);
    $effects_order = array_values($style['effects']);
    $order_correct = TRUE;
    foreach ($effects_order as $index => $effect) {
      if ($effect_edits_order[$index] != $effect['name']) {
        $order_correct = FALSE;
      }
    }
    $this->assertTrue($order_correct, t('The order of the effects is correctly set by default.'));

    // Image effect deletion form.

    // Create an image to make sure it gets flushed after deleting an effect.
    $image_path = $this->createSampleImage($style);
    $this->assertEqual($this->getImageCount($style), 1, t('Image style %style image %file successfully generated.', array('%style' => $style['name'], '%file' => $image_path)));

    // Test effect deletion form.
    $effect = array_pop($style['effects']);
    $this->drupalPost($style_path . '/effects/' . $effect['ieid'] . '/delete', array(), t('Delete'));
    $this->assertRaw(t('The image effect %name has been deleted.', array('%name' => $effect['label'])), t('Image effect deleted.'));

    // Style deletion form.

    // Delete the style.
    $this->drupalPost('admin/config/media/image-styles/delete/' . $style_name, array(), t('Delete'));

    // Confirm the style directory has been removed.
    $directory = file_default_scheme() . '://styles/' . $style_name;
    $this->assertFalse(is_dir($directory), t('Image style %style directory removed on style deletion.', array('%style' => $style['name'])));

    drupal_static_reset('image_styles');
    $this->assertFalse(image_style_load($style_name), t('Image style %style successfully deleted.', array('%style' => $style['name'])));

  }

  /**
   * Test deleting a style and choosing a replacement style.
   */
  function testStyleReplacement() {
    // Create a new style.
    $style_name = strtolower($this->randomName(10));
    image_style_save(array('name' => $style_name));
    $style_path = 'admin/config/media/image-styles/edit/' . $style_name;

    // Create an image field that uses the new style.
    $field_name = strtolower($this->randomName(10));
    $this->createImageField($field_name, 'article');
    $instance = field_info_instance('node', $field_name, 'article');
    $instance['display']['default']['type'] = 'image';
    $instance['display']['default']['settings']['image_style'] = $style_name;
    field_update_instance($instance);

    // Create a new node with an image attached.
    $test_image = current($this->drupalGetTestFiles('image'));
    $nid = $this->uploadNodeImage($test_image, $field_name, 'article');
    $node = node_load($nid);

    // Test that image is displayed using newly created style.
    $this->drupalGet('node/' . $nid);
    $this->assertRaw(image_style_url($style_name, $node->{$field_name}[LANGUAGE_NOT_SPECIFIED][0]['uri']), t('Image displayed using style @style.', array('@style' => $style_name)));

    // Rename the style and make sure the image field is updated.
    $new_style_name = strtolower($this->randomName(10));
    $edit = array(
      'name' => $new_style_name,
    );
    $this->drupalPost('admin/config/media/image-styles/edit/' . $style_name, $edit, t('Update style'));
    $this->assertText(t('Changes to the style have been saved.'), t('Style %name was renamed to %new_name.', array('%name' => $style_name, '%new_name' => $new_style_name)));
    $this->drupalGet('node/' . $nid);
    $this->assertRaw(image_style_url($new_style_name, $node->{$field_name}[LANGUAGE_NOT_SPECIFIED][0]['uri']), t('Image displayed using style replacement style.'));

    // Delete the style and choose a replacement style.
    $edit = array(
      'replacement' => 'thumbnail',
    );
    $this->drupalPost('admin/config/media/image-styles/delete/' . $new_style_name, $edit, t('Delete'));
    $message = t('Style %name was deleted.', array('%name' => $new_style_name));
    $this->assertRaw($message, $message);

    $this->drupalGet('node/' . $nid);
    $this->assertRaw(image_style_url('thumbnail', $node->{$field_name}[LANGUAGE_NOT_SPECIFIED][0]['uri']), t('Image displayed using style replacement style.'));
  }
}
