<?php

/**
 * @file
 * Definition of Drupal\image\Tests\ImageFieldDisplayTest.
 */

namespace Drupal\image\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the display of image fields.
 *
 * @group image
 */
class ImageFieldDisplayTest extends ImageFieldTestBase {

  protected $dumpHeaders = TRUE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_ui');

  /**
   * Test image formatters on node display for public files.
   */
  function testImageFieldFormattersPublic() {
    $this->_testImageFieldFormatters('public');
  }

  /**
   * Test image formatters on node display for private files.
   */
  function testImageFieldFormattersPrivate() {
    // Remove access content permission from anonymous users.
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array('access content' => FALSE));
    $this->_testImageFieldFormatters('private');
  }

  /**
   * Test image formatters on node display.
   */
  function _testImageFieldFormatters($scheme) {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $field_name = strtolower($this->randomMachineName());
    $this->createImageField($field_name, 'article', array('uri_scheme' => $scheme));

    // Create a new node with an image attached.
    $test_image = current($this->drupalGetTestFiles('image'));

    // Ensure that preview works.
    $this->previewNodeImage($test_image, $field_name, 'article');

    // Save node.
    $nid = $this->uploadNodeImage($test_image, $field_name, 'article');
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);

    // Test that the default formatter is being used.
    $image_uri = file_load($node->{$field_name}->target_id)->getFileUri();
    $image = array(
      '#theme' => 'image',
      '#uri' => $image_uri,
      '#width' => 40,
      '#height' => 20,
    );
    $default_output = str_replace("\n", NULL, drupal_render($image));
    $this->assertRaw($default_output, 'Default formatter displaying correctly on full node view.');

    // Test the image linked to file formatter.
    $display_options = array(
      'type' => 'image',
      'settings' => array('image_link' => 'file'),
    );
    $display = entity_get_display('node', $node->getType(), 'default');
    $display->setComponent($field_name, $display_options)
      ->save();

    $image = array(
      '#theme' => 'image',
      '#uri' => $image_uri,
      '#width' => 40,
      '#height' => 20,
    );
    $default_output = '<a href="' . file_create_url($image_uri) . '">' . drupal_render($image) . '</a>';
    $this->drupalGet('node/' . $nid);
    $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(!preg_match('/ image_style\:/', $cache_tags_header), 'No image style cache tag found.');
    $this->assertRaw($default_output, 'Image linked to file formatter displaying correctly on full node view.');
    // Verify that the image can be downloaded.
    $this->assertEqual(file_get_contents($test_image->uri), $this->drupalGet(file_create_url($image_uri)), 'File was downloaded successfully.');
    if ($scheme == 'private') {
      // Only verify HTTP headers when using private scheme and the headers are
      // sent by Drupal.
      $this->assertEqual($this->drupalGetHeader('Content-Type'), 'image/png', 'Content-Type header was sent.');
      $this->assertTrue(strstr($this->drupalGetHeader('Cache-Control'),'private') !== FALSE, 'Cache-Control header was sent.');

      // Log out and try to access the file.
      $this->drupalLogout();
      $this->drupalGet(file_create_url($image_uri));
      $this->assertResponse('403', 'Access denied to original image as anonymous user.');

      // Log in again.
      $this->drupalLogin($this->admin_user);
    }

    // Test the image linked to content formatter.
    $display_options['settings']['image_link'] = 'content';
    $display->setComponent($field_name, $display_options)
      ->save();
    $image = array(
      '#theme' => 'image',
      '#uri' => $image_uri,
      '#width' => 40,
      '#height' => 20,
    );
    $this->drupalGet('node/' . $nid);
    $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(!preg_match('/ image_style\:/', $cache_tags_header), 'No image style cache tag found.');
    $elements = $this->xpath(
      '//a[@href=:path]/img[@src=:url and @alt="" and @width=:width and @height=:height]',
      array(
        ':path' => $node->url(),
        ':url' => file_create_url($image['#uri']),
        ':width' => $image['#width'],
        ':height' => $image['#height'],
      )
    );
    $this->assertEqual(count($elements), 1, 'Image linked to content formatter displaying correctly on full node view.');

    // Test the image style 'thumbnail' formatter.
    $display_options['settings']['image_link'] = '';
    $display_options['settings']['image_style'] = 'thumbnail';
    $display->setComponent($field_name, $display_options)
      ->save();

    // Ensure the derivative image is generated so we do not have to deal with
    // image style callback paths.
    $this->drupalGet(entity_load('image_style', 'thumbnail')->buildUrl($image_uri));
    $image_style = array(
      '#theme' => 'image_style',
      '#uri' => $image_uri,
      '#width' => 40,
      '#height' => 20,
      '#style_name' => 'thumbnail',
    );
    $default_output = drupal_render($image_style);
    $this->drupalGet('node/' . $nid);
    $cache_tags = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'));
    $this->assertTrue(in_array('image_style:thumbnail', $cache_tags));
    $this->assertRaw($default_output, 'Image style thumbnail formatter displaying correctly on full node view.');

    if ($scheme == 'private') {
      // Log out and try to access the file.
      $this->drupalLogout();
      $this->drupalGet(entity_load('image_style', 'thumbnail')->buildUrl($image_uri));
      $this->assertResponse('403', 'Access denied to image style thumbnail as anonymous user.');
    }
  }

  /**
   * Tests for image field settings.
   */
  function testImageFieldSettings() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $test_image = current($this->drupalGetTestFiles('image'));
    list(, $test_image_extension) = explode('.', $test_image->filename);
    $field_name = strtolower($this->randomMachineName());
    $field_settings = array(
      'alt_field' => 1,
      'file_extensions' => $test_image_extension,
      'max_filesize' => '50 KB',
      'max_resolution' => '100x100',
      'min_resolution' => '10x10',
      'title_field' => 1,
      'description' => '[site:name]_description',
    );
    $widget_settings = array(
      'preview_image_style' => 'medium',
    );
    $field = $this->createImageField($field_name, 'article', array(), $field_settings, $widget_settings);

    $this->drupalGet('node/add/article');
    $this->assertText(t('50 KB limit.'), 'Image widget max file size is displayed on article form.');
    $this->assertText(t('Allowed types: @extensions.', array('@extensions' => $test_image_extension)), 'Image widget allowed file types displayed on article form.');
    $this->assertText(t('Images must be larger than 10x10 pixels. Images larger than 100x100 pixels will be resized.'), 'Image widget allowed resolution displayed on article form.');

    // We have to create the article first and then edit it because the alt
    // and title fields do not display until the image has been attached.
    $nid = $this->uploadNodeImage($test_image, $field_name, 'article');
    $this->drupalGet('node/' . $nid . '/edit');
    $this->assertFieldByName($field_name . '[0][alt]', '', 'Alt field displayed on article form.');
    $this->assertFieldByName($field_name . '[0][title]', '', 'Title field displayed on article form.');
    // Verify that the attached image is being previewed using the 'medium'
    // style.
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);
    $image_style = array(
      '#theme' => 'image_style',
      '#uri' => file_load($node->{$field_name}->target_id)->getFileUri(),
      '#width' => 40,
      '#height' => 20,
      '#style_name' => 'medium',
    );
    $default_output = drupal_render($image_style);
    $this->assertRaw($default_output, "Preview image is displayed using 'medium' style.");

    // Add alt/title fields to the image and verify that they are displayed.
    $image = array(
      '#theme' => 'image',
      '#uri' => file_load($node->{$field_name}->target_id)->getFileUri(),
      '#alt' => $this->randomMachineName(),
      '#title' => $this->randomMachineName(),
      '#width' => 40,
      '#height' => 20,
    );
    $edit = array(
      $field_name . '[0][alt]' => $image['#alt'],
      $field_name . '[0][title]' => $image['#title'],
    );
    $this->drupalPostForm('node/' . $nid . '/edit', $edit, t('Save and keep published'));
    $default_output = str_replace("\n", NULL, drupal_render($image));
    $this->assertRaw($default_output, 'Image displayed using user supplied alt and title attributes.');

    // Verify that alt/title longer than allowed results in a validation error.
    $test_size = 2000;
    $edit = array(
      $field_name . '[0][alt]' => $this->randomMachineName($test_size),
      $field_name . '[0][title]' => $this->randomMachineName($test_size),
    );
    $this->drupalPostForm('node/' . $nid . '/edit', $edit, t('Save and keep published'));
    $schema = $field->getFieldStorageDefinition()->getSchema();
    $this->assertRaw(t('Alternative text cannot be longer than %max characters but is currently %length characters long.', array(
      '%max' => $schema['columns']['alt']['length'],
      '%length' => $test_size,
    )));
    $this->assertRaw(t('Title cannot be longer than %max characters but is currently %length characters long.', array(
      '%max' => $schema['columns']['title']['length'],
      '%length' => $test_size,
    )));

    // Set cardinality to unlimited and add upload a second image.
    // The image widget is extending on the file widget, but the image field
    // type does not have the 'display_field' setting which is expected by
    // the file widget. This resulted in notices before when cardinality is not
    // 1, so we need to make sure the file widget prevents these notices by
    // providing all settings, even if they are not used.
    // @see FileWidget::formMultipleElements().
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.' . $field_name . '/storage', array('field_storage[cardinality]' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED), t('Save field settings'));
    $edit = array();
    $edit['files[' . $field_name . '_1][]'] = drupal_realpath($test_image->uri);
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));
    $this->assertText(format_string('Article @title has been updated.', array('@title' => $node->getTitle())));

    // Assert ImageWidget::process() calls FieldWidget::process().
    $this->drupalGet('node/' . $node->id() . '/edit');
    $edit = array();
    $edit['files[' . $field_name . '_2][]'] = drupal_realpath($test_image->uri);
    $this->drupalPostAjaxForm(NULL, $edit, $field_name . '_2_upload_button');
    $this->assertNoRaw('<input multiple type="file" id="edit-' . strtr($field_name, '_', '-') . '-2-upload" name="files[' . $field_name . '_2][]" size="22" class="form-file">');
    $this->assertRaw('<input multiple type="file" id="edit-' . strtr($field_name, '_', '-') . '-3-upload" name="files[' . $field_name . '_3][]" size="22" class="form-file">');
  }

  /**
   * Test use of a default image with an image field.
   */
  function testImageFieldDefaultImage() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    // Create a new image field.
    $field_name = strtolower($this->randomMachineName());
    $this->createImageField($field_name, 'article');

    // Create a new node, with no images and verify that no images are
    // displayed.
    $node = $this->drupalCreateNode(array('type' => 'article'));
    $this->drupalGet('node/' . $node->id());
    // Verify that no image is displayed on the page by checking for the class
    // that would be used on the image field.
    $this->assertNoPattern('<div class="(.*?)field-name-' . strtr($field_name, '_', '-') . '(.*?)">', 'No image displayed when no image is attached and no default image specified.');
    $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(!preg_match('/ image_style\:/', $cache_tags_header), 'No image style cache tag found.');

    // Add a default image to the public image field.
    $images = $this->drupalGetTestFiles('image');
    $alt = $this->randomString(512);
    $title = $this->randomString(1024);
    $edit = array(
      'files[field_storage_settings_default_image_fid]' => drupal_realpath($images[0]->uri),
      'field_storage[settings][default_image][alt]' => $alt,
      'field_storage[settings][default_image][title]' => $title,
    );
    $this->drupalPostForm("admin/structure/types/manage/article/fields/node.article.$field_name/storage", $edit, t('Save field settings'));
    // Clear field definition cache so the new default image is detected.
    \Drupal::entityManager()->clearCachedFieldDefinitions();
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $default_image = $field_storage->getSetting('default_image');
    $file = file_load($default_image['fid']);
    $this->assertTrue($file->isPermanent(), 'The default image status is permanent.');
    $image = array(
      '#theme' => 'image',
      '#uri' => $file->getFileUri(),
      '#alt' => $alt,
      '#title' => $title,
      '#width' => 40,
      '#height' => 20,
    );
    $default_output = str_replace("\n", NULL, drupal_render($image));
    $this->drupalGet('node/' . $node->id());
    $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(!preg_match('/ image_style\:/', $cache_tags_header), 'No image style cache tag found.');
    $this->assertRaw($default_output, 'Default image displayed when no user supplied image is present.');

    // Create a node with an image attached and ensure that the default image
    // is not displayed.
    $nid = $this->uploadNodeImage($images[1], $field_name, 'article');
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);
    $image = array(
      '#theme' => 'image',
      '#uri' => file_load($node->{$field_name}->target_id)->getFileUri(),
      '#width' => 40,
      '#height' => 20,
    );
    $image_output = str_replace("\n", NULL, drupal_render($image));
    $this->drupalGet('node/' . $nid);
    $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(!preg_match('/ image_style\:/', $cache_tags_header), 'No image style cache tag found.');
    $this->assertNoRaw($default_output, 'Default image is not displayed when user supplied image is present.');
    $this->assertRaw($image_output, 'User supplied image is displayed.');

    // Remove default image from the field and make sure it is no longer used.
    $edit = array(
      'field_storage[settings][default_image][fid][fids]' => 0,
    );
    $this->drupalPostForm("admin/structure/types/manage/article/fields/node.article.$field_name/storage", $edit, t('Save field settings'));
    // Clear field definition cache so the new default image is detected.
    \Drupal::entityManager()->clearCachedFieldDefinitions();
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $default_image = $field_storage->getSetting('default_image');
    $this->assertFalse($default_image['fid'], 'Default image removed from field.');
    // Create an image field that uses the private:// scheme and test that the
    // default image works as expected.
    $private_field_name = strtolower($this->randomMachineName());
    $this->createImageField($private_field_name, 'article', array('uri_scheme' => 'private'));
    // Add a default image to the new field.
    $edit = array(
      'files[field_storage_settings_default_image_fid]' => drupal_realpath($images[1]->uri),
      'field_storage[settings][default_image][alt]' => $alt,
      'field_storage[settings][default_image][title]' => $title,
    );
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.' . $private_field_name . '/storage', $edit, t('Save field settings'));
    // Clear field definition cache so the new default image is detected.
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    $private_field_storage = FieldStorageConfig::loadByName('node', $private_field_name);
    $default_image = $private_field_storage->getSetting('default_image');
    $file = file_load($default_image['fid']);
    $this->assertEqual('private', file_uri_scheme($file->getFileUri()), 'Default image uses private:// scheme.');
    $this->assertTrue($file->isPermanent(), 'The default image status is permanent.');
    // Create a new node with no image attached and ensure that default private
    // image is displayed.
    $node = $this->drupalCreateNode(array('type' => 'article'));
    $image = array(
      '#theme' => 'image',
      '#uri' => $file->getFileUri(),
      '#alt' => $alt,
      '#title' => $title,
      '#width' => 40,
      '#height' => 20,
    );
    $default_output = str_replace("\n", NULL, drupal_render($image));
    $this->drupalGet('node/' . $node->id());
    $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(!preg_match('/ image_style\:/', $cache_tags_header), 'No image style cache tag found.');
    $this->assertRaw($default_output, 'Default private image displayed when no user supplied image is present.');
  }

}
