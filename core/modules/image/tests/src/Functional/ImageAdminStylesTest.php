<?php

namespace Drupal\Tests\image\Functional;

use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\ImageStyleInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests creation, deletion, and editing of image styles and effects.
 *
 * @group image
 */
class ImageAdminStylesTest extends ImageFieldTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Given an image style, generate an image.
   */
  public function createSampleImage(ImageStyleInterface $style) {
    static $file_path;

    // First, we need to make sure we have an image in our testing
    // file directory. Copy over an image on the first run.
    if (!isset($file_path)) {
      $files = $this->drupalGetTestFiles('image');
      $file = reset($files);
      $file_path = \Drupal::service('file_system')->copy($file->uri, 'public://');
    }

    return $style->buildUrl($file_path) ? $file_path : FALSE;
  }

  /**
   * Count the number of images currently create for a style.
   */
  public function getImageCount(ImageStyleInterface $style) {
    $count = 0;
    if (is_dir('public://styles/' . $style->id())) {
      $count = count(\Drupal::service('file_system')->scanDirectory('public://styles/' . $style->id(), '/.*/'));
    }
    return $count;
  }

  /**
   * Test creating an image style with a numeric name and ensuring it can be
   * applied to an image.
   */
  public function testNumericStyleName() {
    $style_name = rand();
    $style_label = $this->randomString();
    $edit = [
      'name' => $style_name,
      'label' => $style_label,
    ];
    $this->drupalPostForm('admin/config/media/image-styles/add', $edit, 'Create new style');
    $this->assertRaw(t('Style %name was created.', ['%name' => $style_label]));
    $options = image_style_options();
    $this->assertArrayHasKey($style_name, $options);
  }

  /**
   * General test to add a style, add/remove/edit effects to it, then delete it.
   */
  public function testStyle() {
    $admin_path = 'admin/config/media/image-styles';

    // Setup a style to be created and effects to add to it.
    $style_name = strtolower($this->randomMachineName(10));
    $style_label = $this->randomString();
    $style_path = $admin_path . '/manage/' . $style_name;
    $effect_edits = [
      'image_resize' => [
        'width' => 100,
        'height' => 101,
      ],
      'image_scale' => [
        'width' => 110,
        'height' => 111,
        'upscale' => 1,
      ],
      'image_scale_and_crop' => [
        'width' => 120,
        'height' => 121,
      ],
      'image_crop' => [
        'width' => 130,
        'height' => 131,
        'anchor' => 'left-top',
      ],
      'image_desaturate' => [
        // No options for desaturate.
      ],
      'image_rotate' => [
        'degrees' => 5,
        'random' => 1,
        'bgcolor' => '#FFFF00',
      ],
    ];

    // Add style form.

    $edit = [
      'name' => $style_name,
      'label' => $style_label,
    ];
    $this->drupalPostForm($admin_path . '/add', $edit, 'Create new style');
    $this->assertRaw(t('Style %name was created.', ['%name' => $style_label]));

    // Ensure that the expected entity operations are there.
    $this->drupalGet($admin_path);
    $this->assertSession()->linkByHrefExists($style_path);
    $this->assertSession()->linkByHrefExists($style_path . '/flush');
    $this->assertSession()->linkByHrefExists($style_path . '/delete');

    // Add effect form.

    // Add each sample effect to the style.
    foreach ($effect_edits as $effect => $edit) {
      $edit_data = [];
      foreach ($edit as $field => $value) {
        $edit_data['data[' . $field . ']'] = $value;
      }
      // Add the effect.
      $this->drupalPostForm($style_path, ['new' => $effect], 'Add');
      if (!empty($edit)) {
        $this->submitForm($edit_data, 'Add effect');
      }
    }

    // Load the saved image style.
    $style = ImageStyle::load($style_name);

    // Ensure that third party settings were added to the config entity.
    // These are added by a hook_image_style_presave() implemented in
    // image_module_test module.
    $this->assertEqual('bar', $style->getThirdPartySetting('image_module_test', 'foo'), 'Third party settings were added to the image style.');

    // Ensure that the image style URI matches our expected path.
    $style_uri_path = $style->toUrl()->toString();
    $this->assertStringContainsString($style_path, $style_uri_path, 'The image style URI is correct.');

    // Confirm that all effects on the image style have settings that match
    // what was saved.
    $uuids = [];
    foreach ($style->getEffects() as $uuid => $effect) {
      // Store the uuid for later use.
      $uuids[$effect->getPluginId()] = $uuid;
      $effect_configuration = $effect->getConfiguration();
      foreach ($effect_edits[$effect->getPluginId()] as $field => $value) {
        $this->assertEqual($effect_configuration['data'][$field], $value, new FormattableMarkup('The %field field in the %effect effect has the correct value of %value.', ['%field' => $field, '%effect' => $effect->getPluginId(), '%value' => $value]));
      }
    }

    // Assert that every effect was saved.
    foreach (array_keys($effect_edits) as $effect_name) {
      $this->assertTrue(isset($uuids[$effect_name]), new FormattableMarkup(
        'A %effect_name effect was saved with ID %uuid',
        [
          '%effect_name' => $effect_name,
          '%uuid' => $uuids[$effect_name],
        ]));
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
    $edit = [
      'name' => $style_name,
      'label' => $style_label,
    ];
    foreach ($style->getEffects() as $uuid => $effect) {
      $edit['effects[' . $uuid . '][weight]'] = $weight;
      $weight--;
    }

    // Create an image to make sure it gets flushed after saving.
    $image_path = $this->createSampleImage($style);
    $this->assertEqual(1, $this->getImageCount($style), new FormattableMarkup('Image style %style image %file successfully generated.', ['%style' => $style->label(), '%file' => $image_path]));

    $this->drupalPostForm($style_path, $edit, 'Save');

    // Note that after changing the style name, the style path is changed.
    $style_path = 'admin/config/media/image-styles/manage/' . $style_name;

    // Check that the URL was updated.
    $this->drupalGet($style_path);
    $this->assertSession()->titleEquals("Edit style $style_label | Drupal");

    // Check that the available image effects are properly sorted.
    $option = $this->assertSession()->selectExists('edit-new--2')->findAll('css', 'option');
    $this->assertEquals('Ajax test', $option[1]->getText(), '"Ajax test" is the first selectable effect.');

    // Check that the image was flushed after updating the style.
    // This is especially important when renaming the style. Make sure that
    // the old image directory has been deleted.
    $this->assertEqual(0, $this->getImageCount($style), new FormattableMarkup('Image style %style was flushed after renaming the style and updating the order of effects.', ['%style' => $style->label()]));

    // Load the style by the new name with the new weights.
    $style = ImageStyle::load($style_name);

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
    $this->assertEqual(1, $this->getImageCount($style), new FormattableMarkup('Image style %style image %file successfully generated.', ['%style' => $style->label(), '%file' => $image_path]));

    // Delete the 'image_crop' effect from the style.
    $this->drupalPostForm($style_path . '/effects/' . $uuids['image_crop'] . '/delete', [], 'Delete');
    // Confirm that the form submission was successful.
    $this->assertSession()->statusCodeEquals(200);
    $image_crop_effect = $style->getEffect($uuids['image_crop']);
    $this->assertRaw(t('The image effect %name has been deleted.', ['%name' => $image_crop_effect->label()]));
    // Confirm that there is no longer a link to the effect.
    $this->assertSession()->linkByHrefNotExists($style_path . '/effects/' . $uuids['image_crop'] . '/delete');
    // Refresh the image style information and verify that the effect was
    // actually deleted.
    $entity_type_manager = $this->container->get('entity_type.manager');
    $style = $entity_type_manager->getStorage('image_style')->loadUnchanged($style->id());
    $this->assertFalse($style->getEffects()->has($uuids['image_crop']), new FormattableMarkup(
      'Effect with ID %uuid no longer found on image style %style',
      [
        '%uuid' => $uuids['image_crop'],
        '%style' => $style->label(),
      ]));

    // Additional test on Rotate effect, for transparent background.
    $edit = [
      'data[degrees]' => 5,
      'data[random]' => 0,
      'data[bgcolor]' => '',
    ];
    $this->drupalPostForm($style_path, ['new' => 'image_rotate'], 'Add');
    $this->submitForm($edit, 'Add effect');
    $entity_type_manager = $this->container->get('entity_type.manager');
    $style = $entity_type_manager->getStorage('image_style')->loadUnchanged($style_name);
    $this->assertCount(6, $style->getEffects(), 'Rotate effect with transparent background was added.');

    // Style deletion form.

    // Delete the style.
    $this->drupalPostForm($style_path . '/delete', [], 'Delete');

    // Confirm the style directory has been removed.
    $directory = 'public://styles/' . $style_name;
    $this->assertDirectoryNotExists($directory);

    $this->assertNull(ImageStyle::load($style_name), new FormattableMarkup('Image style %style successfully deleted.', ['%style' => $style->label()]));

    // Test empty text when there are no image styles.

    // Delete all image styles.
    foreach (ImageStyle::loadMultiple() as $image_style) {
      $image_style->delete();
    }

    // Confirm that the empty text is correct on the image styles page.
    $this->drupalGet($admin_path);
    $this->assertRaw(t('There are currently no styles. <a href=":url">Add a new one</a>.', [
      ':url' => Url::fromRoute('image.style_add')->toString(),
    ]));

  }

  /**
   * Test deleting a style and choosing a replacement style.
   */
  public function testStyleReplacement() {
    // Create a new style.
    $style_name = strtolower($this->randomMachineName(10));
    $style_label = $this->randomString();
    $style = ImageStyle::create(['name' => $style_name, 'label' => $style_label]);
    $style->save();
    $style_path = 'admin/config/media/image-styles/manage/';

    // Create an image field that uses the new style.
    $field_name = strtolower($this->randomMachineName(10));
    $this->createImageField($field_name, 'article');
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'article')
      ->setComponent($field_name, [
        'type' => 'image',
        'settings' => ['image_style' => $style_name],
      ])
      ->save();

    // Create a new node with an image attached.
    $test_image = current($this->drupalGetTestFiles('image'));
    $nid = $this->uploadNodeImage($test_image, $field_name, 'article', $this->randomMachineName());
    $node = Node::load($nid);

    // Get node field original image URI.
    $fid = $node->get($field_name)->target_id;
    $original_uri = File::load($fid)->getFileUri();

    // Test that image is displayed using newly created style.
    $this->drupalGet('node/' . $nid);
    $this->assertRaw(file_url_transform_relative($style->buildUrl($original_uri)));

    // Rename the style and make sure the image field is updated.
    $new_style_name = strtolower($this->randomMachineName(10));
    $new_style_label = $this->randomString();
    $edit = [
      'name' => $new_style_name,
      'label' => $new_style_label,
    ];
    $this->drupalPostForm($style_path . $style_name, $edit, 'Save');
    $this->assertText('Changes to the style have been saved.');
    $this->drupalGet('node/' . $nid);

    // Reload the image style using the new name.
    $style = ImageStyle::load($new_style_name);
    $this->assertRaw(file_url_transform_relative($style->buildUrl($original_uri)));

    // Delete the style and choose a replacement style.
    $edit = [
      'replacement' => 'thumbnail',
    ];
    $this->drupalPostForm($style_path . $new_style_name . '/delete', $edit, 'Delete');
    $message = t('The image style %name has been deleted.', ['%name' => $new_style_label]);
    $this->assertRaw($message);

    $replacement_style = ImageStyle::load('thumbnail');
    $this->drupalGet('node/' . $nid);
    $this->assertRaw(file_url_transform_relative($replacement_style->buildUrl($original_uri)));
  }

  /**
   * Verifies that editing an image effect does not cause it to be duplicated.
   */
  public function testEditEffect() {
    // Add a scale effect.
    $style_name = 'test_style_effect_edit';
    $this->drupalGet('admin/config/media/image-styles/add');
    $this->submitForm(['label' => 'Test style effect edit', 'name' => $style_name], 'Create new style');
    $this->submitForm(['new' => 'image_scale_and_crop'], 'Add');
    $this->submitForm(['data[width]' => '300', 'data[height]' => '200'], 'Add effect');
    $this->assertText('Scale and crop 300×200');

    // There should normally be only one edit link on this page initially.
    $this->clickLink(t('Edit'));
    $this->submitForm(['data[width]' => '360', 'data[height]' => '240'], 'Update effect');
    $this->assertText('Scale and crop 360×240');

    // Check that the previous effect is replaced.
    $this->assertNoText('Scale and crop 300×200');

    // Add another scale effect.
    $this->drupalGet('admin/config/media/image-styles/add');
    $this->submitForm(['label' => 'Test style scale edit scale', 'name' => 'test_style_scale_edit_scale'], 'Create new style');
    $this->submitForm(['new' => 'image_scale'], 'Add');
    $this->submitForm(['data[width]' => '12', 'data[height]' => '19'], 'Add effect');

    // Edit the scale effect that was just added.
    $this->clickLink(t('Edit'));
    $this->submitForm(['data[width]' => '24', 'data[height]' => '19'], 'Update effect');

    // Add another scale effect and make sure both exist. Click through from
    // the overview to make sure that it is possible to add new effect then.
    $this->drupalGet('admin/config/media/image-styles');
    $rows = $this->xpath('//table/tbody/tr');
    $i = 0;
    foreach ($rows as $row) {
      if ($row->find('css', 'td')->getText() === 'Test style scale edit scale') {
        $this->clickLink('Edit', $i);
        break;
      }
      $i++;
    }
    $this->submitForm(['new' => 'image_scale'], 'Add');
    $this->submitForm(['data[width]' => '12', 'data[height]' => '19'], 'Add effect');
    $this->assertText('Scale 24×19');
    $this->assertText('Scale 12×19');

    // Try to edit a nonexistent effect.
    $uuid = $this->container->get('uuid');
    $this->drupalGet('admin/config/media/image-styles/manage/' . $style_name . '/effects/' . $uuid->generate());
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Test flush user interface.
   */
  public function testFlushUserInterface() {
    $admin_path = 'admin/config/media/image-styles';

    // Create a new style.
    $style_name = strtolower($this->randomMachineName(10));
    $style = ImageStyle::create(['name' => $style_name, 'label' => $this->randomString()]);
    $style->save();

    // Create an image to make sure it gets flushed.
    $files = $this->drupalGetTestFiles('image');
    $image_uri = $files[0]->uri;
    $derivative_uri = $style->buildUri($image_uri);
    $this->assertTrue($style->createDerivative($image_uri, $derivative_uri));
    $this->assertEqual(1, $this->getImageCount($style));

    // Go to image styles list page and check if the flush operation link
    // exists.
    $this->drupalGet($admin_path);
    $flush_path = $admin_path . '/manage/' . $style_name . '/flush';
    $this->assertSession()->linkByHrefExists($flush_path);

    // Flush the image style derivatives using the user interface.
    $this->drupalPostForm($flush_path, [], 'Flush');

    // The derivative image file should have been deleted.
    $this->assertEqual(0, $this->getImageCount($style));
  }

  /**
   * Tests image style configuration import that does a delete.
   */
  public function testConfigImport() {
    // Create a new style.
    $style_name = strtolower($this->randomMachineName(10));
    $style_label = $this->randomString();
    $style = ImageStyle::create(['name' => $style_name, 'label' => $style_label]);
    $style->save();

    // Create an image field that uses the new style.
    $field_name = strtolower($this->randomMachineName(10));
    $this->createImageField($field_name, 'article');
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'article')
      ->setComponent($field_name, [
        'type' => 'image',
        'settings' => ['image_style' => $style_name],
      ])
      ->save();

    // Create a new node with an image attached.
    $test_image = current($this->drupalGetTestFiles('image'));
    $nid = $this->uploadNodeImage($test_image, $field_name, 'article', $this->randomMachineName());
    $node = Node::load($nid);

    // Get node field original image URI.
    $fid = $node->get($field_name)->target_id;
    $original_uri = File::load($fid)->getFileUri();

    // Test that image is displayed using newly created style.
    $this->drupalGet('node/' . $nid);
    $this->assertRaw(file_url_transform_relative($style->buildUrl($original_uri)));

    // Copy config to sync, and delete the image style.
    $sync = $this->container->get('config.storage.sync');
    $active = $this->container->get('config.storage');
    // Remove the image field from the display, to avoid a dependency error
    // during import.
    EntityViewDisplay::load('node.article.default')
      ->removeComponent($field_name)
      ->save();
    $this->copyConfig($active, $sync);
    $sync->delete('image.style.' . $style_name);
    $this->configImporter()->import();

    $this->assertNull(ImageStyle::load($style_name), 'Style deleted after config import.');
    $this->assertEqual(0, $this->getImageCount($style), 'Image style was flushed after being deleted by config import.');
  }

  /**
   * Tests access for the image style listing.
   */
  public function testImageStyleAccess() {
    $style = ImageStyle::create(['name' => 'style_foo', 'label' => $this->randomString()]);
    $style->save();

    $this->drupalGet('admin/config/media/image-styles');
    $this->clickLink(t('Edit'));
    $this->assertRaw(t('Select a new effect'));
  }

}
