<?php

namespace Drupal\Tests\image\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\system\Tests\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\user\RoleInterface;
use Drupal\image\Entity\ImageStyle;

/**
 * Tests the display of image fields.
 *
 * @group image
 */
class ImageFieldDisplayTest extends ImageFieldTestBase {

  use AssertPageCacheContextsAndTagsTrait;
  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }

  protected $dumpHeaders = TRUE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_ui'];

  /**
   * Test image formatters on node display for public files.
   */
  public function testImageFieldFormattersPublic() {
    $this->_testImageFieldFormatters('public');
  }

  /**
   * Test image formatters on node display for private files.
   */
  public function testImageFieldFormattersPrivate() {
    // Remove access content permission from anonymous users.
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, ['access content' => FALSE]);
    $this->_testImageFieldFormatters('private');
  }

  /**
   * Test image formatters on node display.
   */
  public function _testImageFieldFormatters($scheme) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $field_name = strtolower($this->randomMachineName());
    $field_settings = ['alt_field_required' => 0];
    $instance = $this->createImageField($field_name, 'article', ['uri_scheme' => $scheme], $field_settings);

    // Go to manage display page.
    $this->drupalGet("admin/structure/types/manage/article/display");

    // Test for existence of link to image styles configuration.
    $this->drupalPostForm(NULL, [], "{$field_name}_settings_edit");
    $this->assertLinkByHref(\Drupal::url('entity.image_style.collection'), 0, 'Link to image styles configuration is found');

    // Remove 'administer image styles' permission from testing admin user.
    $admin_user_roles = $this->adminUser->getRoles(TRUE);
    user_role_change_permissions(reset($admin_user_roles), ['administer image styles' => FALSE]);

    // Go to manage display page again.
    $this->drupalGet("admin/structure/types/manage/article/display");

    // Test for absence of link to image styles configuration.
    $this->drupalPostForm(NULL, [], "{$field_name}_settings_edit");
    $this->assertNoLinkByHref(\Drupal::url('entity.image_style.collection'), 'Link to image styles configuration is absent when permissions are insufficient');

    // Restore 'administer image styles' permission to testing admin user
    user_role_change_permissions(reset($admin_user_roles), ['administer image styles' => TRUE]);

    // Create a new node with an image attached.
    $test_image = current($this->drupalGetTestFiles('image'));

    // Ensure that preview works.
    $this->previewNodeImage($test_image, $field_name, 'article');

    // After previewing, make the alt field required. It cannot be required
    // during preview because the form validation will fail.
    $instance->setSetting('alt_field_required', 1);
    $instance->save();

    // Create alt text for the image.
    $alt = $this->randomMachineName();

    // Save node.
    $nid = $this->uploadNodeImage($test_image, $field_name, 'article', $alt);
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);

    // Test that the default formatter is being used.
    $file = $node->{$field_name}->entity;
    $image_uri = $file->getFileUri();
    $image = [
      '#theme' => 'image',
      '#uri' => $image_uri,
      '#width' => 40,
      '#height' => 20,
      '#alt' => $alt,
    ];
    $default_output = str_replace("\n", NULL, $renderer->renderRoot($image));
    $this->assertRaw($default_output, 'Default formatter displaying correctly on full node view.');

    // Test the image linked to file formatter.
    $display_options = [
      'type' => 'image',
      'settings' => ['image_link' => 'file'],
    ];
    $display = entity_get_display('node', $node->getType(), 'default');
    $display->setComponent($field_name, $display_options)
      ->save();

    $image = [
      '#theme' => 'image',
      '#uri' => $image_uri,
      '#width' => 40,
      '#height' => 20,
      '#alt' => $alt,
    ];
    $default_output = '<a href="' . file_create_url($image_uri) . '">' . $renderer->renderRoot($image) . '</a>';
    $this->drupalGet('node/' . $nid);
    $this->assertCacheTag($file->getCacheTags()[0]);
    // @todo Remove in https://www.drupal.org/node/2646744.
    $this->assertCacheContext('url.site');
    $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(!preg_match('/ image_style\:/', $cache_tags_header), 'No image style cache tag found.');
    $this->assertRaw($default_output, 'Image linked to file formatter displaying correctly on full node view.');
    // Verify that the image can be downloaded.
    $this->assertEqual(file_get_contents($test_image->uri), $this->drupalGet(file_create_url($image_uri)), 'File was downloaded successfully.');
    if ($scheme == 'private') {
      // Only verify HTTP headers when using private scheme and the headers are
      // sent by Drupal.
      $this->assertEqual($this->drupalGetHeader('Content-Type'), 'image/png', 'Content-Type header was sent.');
      $this->assertTrue(strstr($this->drupalGetHeader('Cache-Control'), 'private') !== FALSE, 'Cache-Control header was sent.');

      // Log out and try to access the file.
      $this->drupalLogout();
      $this->drupalGet(file_create_url($image_uri));
      $this->assertResponse('403', 'Access denied to original image as anonymous user.');

      // Log in again.
      $this->drupalLogin($this->adminUser);
    }

    // Test the image linked to content formatter.
    $display_options['settings']['image_link'] = 'content';
    $display->setComponent($field_name, $display_options)
      ->save();
    $image = [
      '#theme' => 'image',
      '#uri' => $image_uri,
      '#width' => 40,
      '#height' => 20,
    ];
    $this->drupalGet('node/' . $nid);
    $this->assertCacheTag($file->getCacheTags()[0]);
    $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(!preg_match('/ image_style\:/', $cache_tags_header), 'No image style cache tag found.');
    $elements = $this->xpath(
      '//a[@href=:path]/img[@src=:url and @alt=:alt and @width=:width and @height=:height]',
      [
        ':path' => $node->url(),
        ':url' => file_url_transform_relative(file_create_url($image['#uri'])),
        ':width' => $image['#width'],
        ':height' => $image['#height'],
        ':alt' => $alt,
      ]
    );
    $this->assertEqual(count($elements), 1, 'Image linked to content formatter displaying correctly on full node view.');

    // Test the image style 'thumbnail' formatter.
    $display_options['settings']['image_link'] = '';
    $display_options['settings']['image_style'] = 'thumbnail';
    $display->setComponent($field_name, $display_options)
      ->save();

    // Ensure the derivative image is generated so we do not have to deal with
    // image style callback paths.
    $this->drupalGet(ImageStyle::load('thumbnail')->buildUrl($image_uri));
    $image_style = [
      '#theme' => 'image_style',
      '#uri' => $image_uri,
      '#width' => 40,
      '#height' => 20,
      '#style_name' => 'thumbnail',
      '#alt' => $alt,
    ];
    $default_output = $renderer->renderRoot($image_style);
    $this->drupalGet('node/' . $nid);
    $image_style = ImageStyle::load('thumbnail');
    $this->assertCacheTag($image_style->getCacheTags()[0]);
    $this->assertRaw($default_output, 'Image style thumbnail formatter displaying correctly on full node view.');

    if ($scheme == 'private') {
      // Log out and try to access the file.
      $this->drupalLogout();
      $this->drupalGet(ImageStyle::load('thumbnail')->buildUrl($image_uri));
      $this->assertResponse('403', 'Access denied to image style thumbnail as anonymous user.');
    }

    // Test the image URL formatter without an image style.
    $display_options = [
      'type' => 'image_url',
      'settings' => ['image_style' => ''],
    ];
    $expected_url = file_url_transform_relative(file_create_url($image_uri));
    $this->assertEqual($expected_url, $node->{$field_name}->view($display_options)[0]['#markup']);

    // Test the image URL formatter with an image style.
    $display_options['settings']['image_style'] = 'thumbnail';
    $expected_url = file_url_transform_relative(ImageStyle::load('thumbnail')->buildUrl($image_uri));
    $this->assertEqual($expected_url, $node->{$field_name}->view($display_options)[0]['#markup']);
  }

  /**
   * Tests for image field settings.
   */
  public function testImageFieldSettings() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $test_image = current($this->drupalGetTestFiles('image'));
    list(, $test_image_extension) = explode('.', $test_image->filename);
    $field_name = strtolower($this->randomMachineName());
    $field_settings = [
      'alt_field' => 1,
      'file_extensions' => $test_image_extension,
      'max_filesize' => '50 KB',
      'max_resolution' => '100x100',
      'min_resolution' => '10x10',
      'title_field' => 1,
    ];
    $widget_settings = [
      'preview_image_style' => 'medium',
    ];
    $field = $this->createImageField($field_name, 'article', [], $field_settings, $widget_settings);

    // Verify that the min/max resolution set on the field are properly
    // extracted, and displayed, on the image field's configuration form.
    $this->drupalGet('admin/structure/types/manage/article/fields/' . $field->id());
    $this->assertFieldByName('settings[max_resolution][x]', '100', 'Expected max resolution X value of 100.');
    $this->assertFieldByName('settings[max_resolution][y]', '100', 'Expected max resolution Y value of 100.');
    $this->assertFieldByName('settings[min_resolution][x]', '10', 'Expected min resolution X value of 10.');
    $this->assertFieldByName('settings[min_resolution][y]', '10', 'Expected min resolution Y value of 10.');

    $this->drupalGet('node/add/article');
    $this->assertText(t('50 KB limit.'), 'Image widget max file size is displayed on article form.');
    $this->assertText(t('Allowed types: @extensions.', ['@extensions' => $test_image_extension]), 'Image widget allowed file types displayed on article form.');
    $this->assertText(t('Images must be larger than 10x10 pixels. Images larger than 100x100 pixels will be resized.'), 'Image widget allowed resolution displayed on article form.');

    // We have to create the article first and then edit it because the alt
    // and title fields do not display until the image has been attached.

    // Create alt text for the image.
    $alt = $this->randomMachineName();

    $nid = $this->uploadNodeImage($test_image, $field_name, 'article', $alt);
    $this->drupalGet('node/' . $nid . '/edit');

    // Verify that the optional fields alt & title are saved & filled.
    $this->assertFieldByName($field_name . '[0][alt]', $alt, 'Alt field displayed on article form.');
    $this->assertFieldByName($field_name . '[0][title]', '', 'Title field displayed on article form.');

    // Verify that the attached image is being previewed using the 'medium'
    // style.
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $file = $node->{$field_name}->entity;

    $url = file_url_transform_relative(file_create_url(ImageStyle::load('medium')->buildUrl($file->getFileUri())));
    $this->assertTrue($this->cssSelect('img[width=40][height=20][class=image-style-medium][src="' . $url . '"]'));

    // Add alt/title fields to the image and verify that they are displayed.
    $image = [
      '#theme' => 'image',
      '#uri' => $file->getFileUri(),
      '#alt' => $alt,
      '#title' => $this->randomMachineName(),
      '#width' => 40,
      '#height' => 20,
    ];
    $edit = [
      $field_name . '[0][alt]' => $image['#alt'],
      $field_name . '[0][title]' => $image['#title'],
    ];
    $this->drupalPostForm('node/' . $nid . '/edit', $edit, t('Save'));
    $default_output = str_replace("\n", NULL, $renderer->renderRoot($image));
    $this->assertRaw($default_output, 'Image displayed using user supplied alt and title attributes.');

    // Verify that alt/title longer than allowed results in a validation error.
    $test_size = 2000;
    $edit = [
      $field_name . '[0][alt]' => $this->randomMachineName($test_size),
      $field_name . '[0][title]' => $this->randomMachineName($test_size),
    ];
    $this->drupalPostForm('node/' . $nid . '/edit', $edit, t('Save'));
    $schema = $field->getFieldStorageDefinition()->getSchema();
    $this->assertRaw(t('Alternative text cannot be longer than %max characters but is currently %length characters long.', [
      '%max' => $schema['columns']['alt']['length'],
      '%length' => $test_size,
    ]));
    $this->assertRaw(t('Title cannot be longer than %max characters but is currently %length characters long.', [
      '%max' => $schema['columns']['title']['length'],
      '%length' => $test_size,
    ]));

    // Set cardinality to unlimited and add upload a second image.
    // The image widget is extending on the file widget, but the image field
    // type does not have the 'display_field' setting which is expected by
    // the file widget. This resulted in notices before when cardinality is not
    // 1, so we need to make sure the file widget prevents these notices by
    // providing all settings, even if they are not used.
    // @see FileWidget::formMultipleElements().
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.' . $field_name . '/storage', ['cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED], t('Save field settings'));
    $edit = [
      'files[' . $field_name . '_1][]' => \Drupal::service('file_system')->realpath($test_image->uri),
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));
    // Add the required alt text.
    $this->drupalPostForm(NULL, [$field_name . '[1][alt]' => $alt], t('Save'));
    $this->assertText(format_string('Article @title has been updated.', ['@title' => $node->getTitle()]));

    // Assert ImageWidget::process() calls FieldWidget::process().
    $this->drupalGet('node/' . $node->id() . '/edit');
    $edit = [
      'files[' . $field_name . '_2][]' => \Drupal::service('file_system')->realpath($test_image->uri),
    ];
    $this->drupalPostForm(NULL, $edit, $field_name . '_2_upload_button');
    $this->assertSession()->elementNotExists('css', 'input[name="files[' . $field_name . '_2][]"]');
    $this->assertSession()->elementExists('css', 'input[name="files[' . $field_name . '_3][]"]');
  }

  /**
   * Test use of a default image with an image field.
   */
  public function testImageFieldDefaultImage() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    // Create a new image field.
    $field_name = strtolower($this->randomMachineName());
    $this->createImageField($field_name, 'article');

    // Create a new node, with no images and verify that no images are
    // displayed.
    $node = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalGet('node/' . $node->id());
    // Verify that no image is displayed on the page by checking for the class
    // that would be used on the image field.
    $this->assertNoPattern('<div class="(.*?)field--name-' . strtr($field_name, '_', '-') . '(.*?)">', 'No image displayed when no image is attached and no default image specified.');
    $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(!preg_match('/ image_style\:/', $cache_tags_header), 'No image style cache tag found.');

    // Add a default image to the public image field.
    $images = $this->drupalGetTestFiles('image');
    $alt = $this->randomString(512);
    $title = $this->randomString(1024);
    $edit = [
      // Get the path of the 'image-test.png' file.
      'files[settings_default_image_uuid]' => \Drupal::service('file_system')->realpath($images[0]->uri),
      'settings[default_image][alt]' => $alt,
      'settings[default_image][title]' => $title,
    ];
    $this->drupalPostForm("admin/structure/types/manage/article/fields/node.article.$field_name/storage", $edit, t('Save field settings'));
    // Clear field definition cache so the new default image is detected.
    \Drupal::entityManager()->clearCachedFieldDefinitions();
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $default_image = $field_storage->getSetting('default_image');
    $file = \Drupal::entityManager()->loadEntityByUuid('file', $default_image['uuid']);
    $this->assertTrue($file->isPermanent(), 'The default image status is permanent.');
    $image = [
      '#theme' => 'image',
      '#uri' => $file->getFileUri(),
      '#alt' => $alt,
      '#title' => $title,
      '#width' => 40,
      '#height' => 20,
    ];
    $default_output = str_replace("\n", NULL, $renderer->renderRoot($image));
    $this->drupalGet('node/' . $node->id());
    $this->assertCacheTag($file->getCacheTags()[0]);
    $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(!preg_match('/ image_style\:/', $cache_tags_header), 'No image style cache tag found.');
    $this->assertRaw($default_output, 'Default image displayed when no user supplied image is present.');

    // Create a node with an image attached and ensure that the default image
    // is not displayed.

    // Create alt text for the image.
    $alt = $this->randomMachineName();

    // Upload the 'image-test.gif' file.
    $nid = $this->uploadNodeImage($images[2], $field_name, 'article', $alt);
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $file = $node->{$field_name}->entity;
    $image = [
      '#theme' => 'image',
      '#uri' => $file->getFileUri(),
      '#width' => 40,
      '#height' => 20,
      '#alt' => $alt,
    ];
    $image_output = str_replace("\n", NULL, $renderer->renderRoot($image));
    $this->drupalGet('node/' . $nid);
    $this->assertCacheTag($file->getCacheTags()[0]);
    $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(!preg_match('/ image_style\:/', $cache_tags_header), 'No image style cache tag found.');
    $this->assertNoRaw($default_output, 'Default image is not displayed when user supplied image is present.');
    $this->assertRaw($image_output, 'User supplied image is displayed.');

    // Remove default image from the field and make sure it is no longer used.
    // Can't use fillField cause Mink can't fill hidden fields.
    $this->drupalGet("admin/structure/types/manage/article/fields/node.article.$field_name/storage");
    $this->getSession()->getPage()->find('css', 'input[name="settings[default_image][uuid][fids]"]')->setValue(0);
    $this->getSession()->getPage()->pressButton(t('Save field settings'));

    // Clear field definition cache so the new default image is detected.
    \Drupal::entityManager()->clearCachedFieldDefinitions();
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $default_image = $field_storage->getSetting('default_image');
    $this->assertFalse($default_image['uuid'], 'Default image removed from field.');
    // Create an image field that uses the private:// scheme and test that the
    // default image works as expected.
    $private_field_name = strtolower($this->randomMachineName());
    $this->createImageField($private_field_name, 'article', ['uri_scheme' => 'private']);
    // Add a default image to the new field.
    $edit = [
      // Get the path of the 'image-test.gif' file.
      'files[settings_default_image_uuid]' => \Drupal::service('file_system')->realpath($images[2]->uri),
      'settings[default_image][alt]' => $alt,
      'settings[default_image][title]' => $title,
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.' . $private_field_name . '/storage', $edit, t('Save field settings'));
    // Clear field definition cache so the new default image is detected.
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    $private_field_storage = FieldStorageConfig::loadByName('node', $private_field_name);
    $default_image = $private_field_storage->getSetting('default_image');
    $file = \Drupal::entityManager()->loadEntityByUuid('file', $default_image['uuid']);
    $this->assertEqual('private', file_uri_scheme($file->getFileUri()), 'Default image uses private:// scheme.');
    $this->assertTrue($file->isPermanent(), 'The default image status is permanent.');
    // Create a new node with no image attached and ensure that default private
    // image is displayed.
    $node = $this->drupalCreateNode(['type' => 'article']);
    $image = [
      '#theme' => 'image',
      '#uri' => $file->getFileUri(),
      '#alt' => $alt,
      '#title' => $title,
      '#width' => 40,
      '#height' => 20,
    ];
    $default_output = str_replace("\n", NULL, $renderer->renderRoot($image));
    $this->drupalGet('node/' . $node->id());
    $this->assertCacheTag($file->getCacheTags()[0]);
    $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(!preg_match('/ image_style\:/', $cache_tags_header), 'No image style cache tag found.');
    $this->assertRaw($default_output, 'Default private image displayed when no user supplied image is present.');
  }

}
