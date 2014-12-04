<?php

/**
 * @file
 * Definition of Drupal\image\Tests\ImageAdminStylesTest.
 */

namespace Drupal\image\Tests;

use Drupal\Component\Utility\String;
use Drupal\image\ImageStyleInterface;
use Drupal\node\Entity\Node;

/**
 * Tests creation, deletion, and editing of image styles and effects.
 *
 * @group image
 */
class ImageAdminStylesTest extends ImageFieldTestBase {

  /**
   * Given an image style, generate an image.
   */
  function createSampleImage(ImageStyleInterface $style) {
    static $file_path;

    // First, we need to make sure we have an image in our testing
    // file directory. Copy over an image on the first run.
    if (!isset($file_path)) {
      $files = $this->drupalGetTestFiles('image');
      $file = reset($files);
      $file_path = file_unmanaged_copy($file->uri);
    }

    return $style->buildUrl($file_path) ? $file_path : FALSE;
  }

  /**
   * Count the number of images currently create for a style.
   */
  function getImageCount(ImageStyleInterface $style) {
    return count(file_scan_directory('public://styles/' . $style->id(), '/.*/'));
  }

  /**
   * Test creating an image style with a numeric name and ensuring it can be
   * applied to an image.
   */
  function testNumericStyleName() {
    $style_name = rand();
    $style_label = $this->randomString();
    $edit = array(
      'name' => $style_name,
      'label' => $style_label,
    );
    $this->drupalPostForm('admin/config/media/image-styles/add', $edit, t('Create new style'));
    $this->assertRaw(t('Style %name was created.', array('%name' => $style_label)));
    $options = image_style_options();
    $this->assertTrue(array_key_exists($style_name, $options), format_string('Array key %key exists.', array('%key' => $style_name)));
  }

  /**
   * General test to add a style, add/remove/edit effects to it, then delete it.
   */
  function testStyle() {
    $admin_path = 'admin/config/media/image-styles';

    // Setup a style to be created and effects to add to it.
    $style_name = strtolower($this->randomMachineName(10));
    $style_label = $this->randomString();
    $style_path = $admin_path . '/manage/' . $style_name;
    $effect_edits = array(
      'image_resize' => array(
        'width' => 100,
        'height' => 101,
      ),
      'image_scale' => array(
        'width' => 110,
        'height' => 111,
        'upscale' => 1,
      ),
      'image_scale_and_crop' => array(
        'width' => 120,
        'height' => 121,
      ),
      'image_crop' => array(
        'width' => 130,
        'height' => 131,
        'anchor' => 'left-top',
      ),
      'image_desaturate' => array(
        // No options for desaturate.
      ),
      'image_rotate' => array(
        'degrees' => 5,
        'random' => 1,
        'bgcolor' => '#FFFF00',
      ),
    );

    // Add style form.

    $edit = array(
      'name' => $style_name,
      'label' => $style_label,
    );
    $this->drupalPostForm($admin_path . '/add', $edit, t('Create new style'));
    $this->assertRaw(t('Style %name was created.', array('%name' => $style_label)));

    // Ensure that the expected entity operations are there.
    $this->drupalGet($admin_path);
    $this->assertLinkByHref($style_path);
    $this->assertLinkByHref($style_path . '/flush');
    $this->assertLinkByHref($style_path . '/delete');

    // Add effect form.

    // Add each sample effect to the style.
    foreach ($effect_edits as $effect => $edit) {
      $edit_data = array();
      foreach ($edit as $field => $value) {
        $edit_data['data[' . $field . ']'] = $value;
      }
      // Add the effect.
      $this->drupalPostForm($style_path, array('new' => $effect), t('Add'));
      if (!empty($edit)) {
        $this->drupalPostForm(NULL, $edit_data, t('Add effect'));
      }
    }

    // Load the saved image style.
    $style = entity_load('image_style', $style_name);

    // Ensure that third party settings were added to the config entity.
    // These are added by a hook_image_style_presave() implemented in
    // image_module_test module.
    $this->assertEqual('bar', $style->getThirdPartySetting('image_module_test', 'foo'), 'Third party settings were added to the image style.');

    // Ensure that the image style URI matches our expected path.
    $style_uri_path = $style->url();
    $this->assertTrue(strpos($style_uri_path, $style_path) !== FALSE, 'The image style URI is correct.');

    // Confirm that all effects on the image style have settings that match
    // what was saved.
    $uuids = array();
    foreach ($style->getEffects() as $uuid => $effect) {
      // Store the uuid for later use.
      $uuids[$effect->getPluginId()] = $uuid;
      $effect_configuration = $effect->getConfiguration();
      foreach ($effect_edits[$effect->getPluginId()] as $field => $value) {
        $this->assertEqual($value, $effect_configuration['data'][$field], String::format('The %field field in the %effect effect has the correct value of %value.', array('%field' => $field, '%effect' => $effect->getPluginId(), '%value' => $value)));
      }
    }

    // Assert that every effect was saved.
    foreach (array_keys($effect_edits) as $effect_name) {
      $this->assertTrue(isset($uuids[$effect_name]), format_string(
        'A %effect_name effect was saved with ID %uuid',
        array(
          '%effect_name' => $effect_name,
          '%uuid' => $uuids[$effect_name],
        )));
    }

    // Image style overview form (ordering and renaming).

    // Confirm the order of effects is maintained according to the order we
    // added the fields.
    $effect_edits_order = array_keys($effect_edits);
    $order_correct = TRUE;
    $index = 0;
    foreach ($style->getEffects() as $effect) {
      if ($effect_edits_order[$index] != $effect->getPluginId()) {
        $order_correct = FALSE;
      }
      $index++;
    }
    $this->assertTrue($order_correct, 'The order of the effects is correctly set by default.');

    // Test the style overview form.
    // Change the name of the style and adjust the weights of effects.
    $style_name = strtolower($this->randomMachineName(10));
    $style_label = $this->randomMachineName();
    $weight = count($effect_edits);
    $edit = array(
      'name' => $style_name,
      'label' => $style_label,
    );
    foreach ($style->getEffects() as $uuid => $effect) {
      $edit['effects[' . $uuid . '][weight]'] = $weight;
      $weight--;
    }

    // Create an image to make sure it gets flushed after saving.
    $image_path = $this->createSampleImage($style);
    $this->assertEqual($this->getImageCount($style), 1, format_string('Image style %style image %file successfully generated.', array('%style' => $style->label(), '%file' => $image_path)));

    $this->drupalPostForm($style_path, $edit, t('Update style'));

    // Note that after changing the style name, the style path is changed.
    $style_path = 'admin/config/media/image-styles/manage/' . $style_name;

    // Check that the URL was updated.
    $this->drupalGet($style_path);
    $this->assertTitle(t('Edit style @name | Drupal', array('@name' => $style_label)));
    $this->assertResponse(200, format_string('Image style %original renamed to %new', array('%original' => $style->id(), '%new' => $style_name)));

    // Check that the image was flushed after updating the style.
    // This is especially important when renaming the style. Make sure that
    // the old image directory has been deleted.
    $this->assertEqual($this->getImageCount($style), 0, format_string('Image style %style was flushed after renaming the style and updating the order of effects.', array('%style' => $style->label())));

    // Load the style by the new name with the new weights.
    $style = entity_load('image_style', $style_name);

    // Confirm the new style order was saved.
    $effect_edits_order = array_reverse($effect_edits_order);
    $order_correct = TRUE;
    $index = 0;
    foreach ($style->getEffects() as $effect) {
      if ($effect_edits_order[$index] != $effect->getPluginId()) {
        $order_correct = FALSE;
      }
      $index++;
    }
    $this->assertTrue($order_correct, 'The order of the effects is correctly set by default.');

    // Image effect deletion form.

    // Create an image to make sure it gets flushed after deleting an effect.
    $image_path = $this->createSampleImage($style);
    $this->assertEqual($this->getImageCount($style), 1, format_string('Image style %style image %file successfully generated.', array('%style' => $style->label(), '%file' => $image_path)));

    // Delete the 'image_crop' effect from the style.
    $this->drupalPostForm($style_path . '/effects/' . $uuids['image_crop'] . '/delete', array(), t('Delete'));
    // Confirm that the form submission was successful.
    $this->assertResponse(200);
    $image_crop_effect = $style->getEffect($uuids['image_crop']);
    $this->assertRaw(t('The image effect %name has been deleted.', array('%name' => $image_crop_effect->label())));
    // Confirm that there is no longer a link to the effect.
    $this->assertNoLinkByHref($style_path . '/effects/' . $uuids['image_crop'] . '/delete');
    // Refresh the image style information and verify that the effect was
    // actually deleted.
    $style = entity_load_unchanged('image_style', $style->id());
    $this->assertFalse($style->getEffects()->has($uuids['image_crop']), format_string(
      'Effect with ID %uuid no longer found on image style %style',
      array(
        '%uuid' => $uuids['image_crop'],
        '%style' => $style->label(),
      )));

    // Additional test on Rotate effect, for transparent background.
    $edit = array(
      'data[degrees]' => 5,
      'data[random]' => 0,
      'data[bgcolor]' => '',
    );
    $this->drupalPostForm($style_path, array('new' => 'image_rotate'), t('Add'));
    $this->drupalPostForm(NULL, $edit, t('Add effect'));
    $style = entity_load_unchanged('image_style', $style_name);
    $this->assertEqual(count($style->getEffects()), 6, 'Rotate effect with transparent background was added.');

    // Style deletion form.

    // Delete the style.
    $this->drupalPostForm($style_path . '/delete', array(), t('Delete'));

    // Confirm the style directory has been removed.
    $directory = file_default_scheme() . '://styles/' . $style_name;
    $this->assertFalse(is_dir($directory), format_string('Image style %style directory removed on style deletion.', array('%style' => $style->label())));

    $this->assertFalse(entity_load('image_style', $style_name), format_string('Image style %style successfully deleted.', array('%style' => $style->label())));

  }

  /**
   * Test deleting a style and choosing a replacement style.
   */
  function testStyleReplacement() {
    // Create a new style.
    $style_name = strtolower($this->randomMachineName(10));
    $style_label = $this->randomString();
    $style = entity_create('image_style', array('name' => $style_name, 'label' => $style_label));
    $style->save();
    $style_path = 'admin/config/media/image-styles/manage/';

    // Create an image field that uses the new style.
    $field_name = strtolower($this->randomMachineName(10));
    $this->createImageField($field_name, 'article');
    entity_get_display('node', 'article', 'default')
      ->setComponent($field_name, array(
        'type' => 'image',
        'settings' => array('image_style' => $style_name),
      ))
      ->save();

    // Create a new node with an image attached.
    $test_image = current($this->drupalGetTestFiles('image'));
    $nid = $this->uploadNodeImage($test_image, $field_name, 'article');
    $node = Node::load($nid);

    // Get node field original image URI.
    $fid = $node->get($field_name)->target_id;
    $original_uri = file_load($fid)->getFileUri();

    // Test that image is displayed using newly created style.
    $this->drupalGet('node/' . $nid);
    $this->assertRaw($style->buildUrl($original_uri), format_string('Image displayed using style @style.', array('@style' => $style_name)));

    // Rename the style and make sure the image field is updated.
    $new_style_name = strtolower($this->randomMachineName(10));
    $new_style_label = $this->randomString();
    $edit = array(
      'name' => $new_style_name,
      'label' => $new_style_label,
    );
    $this->drupalPostForm($style_path . $style_name, $edit, t('Update style'));
    $this->assertText(t('Changes to the style have been saved.'), format_string('Style %name was renamed to %new_name.', array('%name' => $style_name, '%new_name' => $new_style_name)));
    $this->drupalGet('node/' . $nid);

    // Reload the image style using the new name.
    $style = entity_load('image_style', $new_style_name);
    $this->assertRaw($style->buildUrl($original_uri), 'Image displayed using style replacement style.');

    // Delete the style and choose a replacement style.
    $edit = array(
      'replacement' => 'thumbnail',
    );
    $this->drupalPostForm($style_path . $new_style_name . '/delete', $edit, t('Delete'));
    $message = t('Style %name was deleted.', array('%name' => $new_style_label));
    $this->assertRaw($message);

    $replacement_style = entity_load('image_style', 'thumbnail');
    $this->drupalGet('node/' . $nid);
    $this->assertRaw($replacement_style->buildUrl($original_uri), 'Image displayed using style replacement style.');
  }

  /**
   * Verifies that editing an image effect does not cause it to be duplicated.
   */
  function testEditEffect() {
    // Add a scale effect.
    $style_name = 'test_style_effect_edit';
    $this->drupalGet('admin/config/media/image-styles/add');
    $this->drupalPostForm(NULL, array('label' => 'Test style effect edit', 'name' => $style_name), t('Create new style'));
    $this->drupalPostForm(NULL, array('new' => 'image_scale_and_crop'), t('Add'));
    $this->drupalPostForm(NULL, array('data[width]' => '300', 'data[height]' => '200'), t('Add effect'));
    $this->assertText(t('Scale and crop 300×200'));

    // There should normally be only one edit link on this page initially.
    $this->clickLink(t('Edit'));
    $this->drupalPostForm(NULL, array('data[width]' => '360', 'data[height]' => '240'), t('Update effect'));
    $this->assertText(t('Scale and crop 360×240'));

    // Check that the previous effect is replaced.
    $this->assertNoText(t('Scale and crop 300×200'));

    // Add another scale effect.
    $this->drupalGet('admin/config/media/image-styles/add');
    $this->drupalPostForm(NULL, array('label' => 'Test style scale edit scale', 'name' => 'test_style_scale_edit_scale'), t('Create new style'));
    $this->drupalPostForm(NULL, array('new' => 'image_scale'), t('Add'));
    $this->drupalPostForm(NULL, array('data[width]' => '12', 'data[height]' => '19'), t('Add effect'));

    // Edit the scale effect that was just added.
    $this->clickLink(t('Edit'));
    $this->drupalPostForm(NULL, array('data[width]' => '24', 'data[height]' => '19'), t('Update effect'));
    $this->drupalPostForm(NULL, array('new' => 'image_scale'), t('Add'));

    // Add another scale effect and make sure both exist.
    $this->drupalPostForm(NULL, array('data[width]' => '12', 'data[height]' => '19'), t('Add effect'));
    $this->assertText(t('Scale 24×19'));
    $this->assertText(t('Scale 12×19'));

    // Try to edit a nonexistent effect.
    $uuid = $this->container->get('uuid');
    $this->drupalGet('admin/config/media/image-styles/manage/' . $style_name . '/effects/' . $uuid->generate());
    $this->assertResponse(404);
  }

  /**
   * Test flush user interface.
   */
  public function testFlushUserInterface() {
    $admin_path = 'admin/config/media/image-styles';

    // Create a new style.
    $style_name = strtolower($this->randomMachineName(10));
    $style = entity_create('image_style', array('name' => $style_name, 'label' => $this->randomString()));
    $style->save();

    // Create an image to make sure it gets flushed.
    $files = $this->drupalGetTestFiles('image');
    $image_uri = $files[0]->uri;
    $derivative_uri = $style->buildUri($image_uri);
    $this->assertTrue($style->createDerivative($image_uri, $derivative_uri));
    $this->assertEqual($this->getImageCount($style), 1);

    // Go to image styles list page and check if the flush operation link
    // exists.
    $this->drupalGet($admin_path);
    $flush_path = $admin_path . '/manage/' . $style_name . '/flush';
    $this->assertLinkByHref($flush_path);

    // Flush the image style derivatives using the user interface.
    $this->drupalPostForm($flush_path, array(), t('Flush'));

    // The derivative image file should have been deleted.
    $this->assertEqual($this->getImageCount($style), 0);
  }

  /**
   * Tests image style configuration import that does a delete.
   */
  function testConfigImport() {
    // Create a new style.
    $style_name = strtolower($this->randomMachineName(10));
    $style_label = $this->randomString();
    $style = entity_create('image_style', array('name' => $style_name, 'label' => $style_label));
    $style->save();

    // Create an image field that uses the new style.
    $field_name = strtolower($this->randomMachineName(10));
    $this->createImageField($field_name, 'article');
    entity_get_display('node', 'article', 'default')
      ->setComponent($field_name, array(
        'type' => 'image',
        'settings' => array('image_style' => $style_name),
      ))
      ->save();

    // Create a new node with an image attached.
    $test_image = current($this->drupalGetTestFiles('image'));
    $nid = $this->uploadNodeImage($test_image, $field_name, 'article');
    $node = Node::load($nid);

    // Get node field original image URI.
    $fid = $node->get($field_name)->target_id;
    $original_uri = file_load($fid)->getFileUri();

    // Test that image is displayed using newly created style.
    $this->drupalGet('node/' . $nid);
    $this->assertRaw($style->buildUrl($original_uri), format_string('Image displayed using style @style.', array('@style' => $style_name)));

    // Copy config to staging, and delete the image style.
    $staging = $this->container->get('config.storage.staging');
    $active = $this->container->get('config.storage');
    $this->copyConfig($active, $staging);
    $staging->delete('image.style.' . $style_name);
    $this->configImporter()->import();

    $this->assertFalse(entity_load('image_style', $style_name), 'Style deleted after config import.');
    $this->assertEqual($this->getImageCount($style), 0, 'Image style was flushed after being deleted by config import.');
  }

  /**
   * Tests access for the image style listing.
   */
  public function testImageStyleAccess() {
    $style = entity_create('image_style', array('name' => 'style_foo', 'label' => $this->randomString()));
    $style->save();

    $this->drupalGet('admin/config/media/image-styles');
    $this->clickLink(t('Edit'));
    $this->assertRaw(t('Select a new effect'));
  }

}
