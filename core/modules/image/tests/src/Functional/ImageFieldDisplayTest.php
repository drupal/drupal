<?php

namespace Drupal\Tests\image\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\RoleInterface;

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
  protected static $modules = ['field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Tests image formatters on node display for public files.
   */
  public function testImageFieldFormattersPublic() {
    $this->_testImageFieldFormatters('public');
  }

  /**
   * Tests image formatters on node display for private files.
   */
  public function testImageFieldFormattersPrivate() {
    // Remove access content permission from anonymous users.
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, ['access content' => FALSE]);
    $this->_testImageFieldFormatters('private');
  }

  /**
   * Tests image formatters on node display.
   */
  public function _testImageFieldFormatters($scheme) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $field_name = strtolower($this->randomMachineName());
    $field_settings = ['alt_field_required' => 0];
    $instance = $this->createImageField($field_name, 'article', ['uri_scheme' => $scheme], $field_settings);

    // Go to manage display page.
    $this->drupalGet("admin/structure/types/manage/article/display");

    // Test for existence of link to image styles configuration.
    $this->submitForm([], "{$field_name}_settings_edit");
    $this->assertSession()->linkByHrefExists(Url::fromRoute('entity.image_style.collection')->toString(), 0, 'Link to image styles configuration is found');

    // Remove 'administer image styles' permission from testing admin user.
    $admin_user_roles = $this->adminUser->getRoles(TRUE);
    user_role_change_permissions(reset($admin_user_roles), ['administer image styles' => FALSE]);

    // Go to manage display page again.
    $this->drupalGet("admin/structure/types/manage/article/display");

    // Test for absence of link to image styles configuration.
    $this->submitForm([], "{$field_name}_settings_edit");
    $this->assertSession()->linkByHrefNotExists(Url::fromRoute('entity.image_style.collection')->toString(), 'Link to image styles configuration is absent when permissions are insufficient');

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
    $this->assertSession()->responseContains($default_output);

    // Test the image linked to file formatter.
    $display_options = [
      'type' => 'image',
      'settings' => ['image_link' => 'file'],
    ];
    $display = \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', $node->getType());
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
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $file->getCacheTags()[0]);
    // @todo Remove in https://www.drupal.org/node/2646744.
    $this->assertCacheContext('url.site');
    // Verify that no image style cache tags are found.
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'image_style:');
    $this->assertSession()->responseContains($default_output);
    // Verify that the image can be downloaded.
    $this->assertEquals(file_get_contents($test_image->uri), $this->drupalGet(file_create_url($image_uri)), 'File was downloaded successfully.');
    if ($scheme == 'private') {
      // Only verify HTTP headers when using private scheme and the headers are
      // sent by Drupal.
      $this->assertSession()->responseHeaderEquals('Content-Type', 'image/png');
      $this->assertSession()->responseHeaderContains('Cache-Control', 'private');

      // Log out and ensure the file cannot be accessed.
      $this->drupalLogout();
      $this->drupalGet(file_create_url($image_uri));
      $this->assertSession()->statusCodeEquals(403);

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
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $file->getCacheTags()[0]);
    // Verify that no image style cache tags are found.
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'image_style:');
    $elements = $this->xpath(
      '//a[@href=:path]/img[@src=:url and @alt=:alt and @width=:width and @height=:height]',
      [
        ':path' => $node->toUrl()->toString(),
        ':url' => file_url_transform_relative(file_create_url($image['#uri'])),
        ':width' => $image['#width'],
        ':height' => $image['#height'],
        ':alt' => $alt,
      ]
    );
    $this->assertCount(1, $elements, 'Image linked to content formatter displaying correctly on full node view.');

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
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $image_style->getCacheTags()[0]);
    $this->assertSession()->responseContains($default_output);

    if ($scheme == 'private') {
      // Log out and ensure the file cannot be accessed.
      $this->drupalLogout();
      $this->drupalGet(ImageStyle::load('thumbnail')->buildUrl($image_uri));
      $this->assertSession()->statusCodeEquals(403);
    }

    // Test the image URL formatter without an image style.
    $display_options = [
      'type' => 'image_url',
      'settings' => ['image_style' => ''],
    ];
    $expected_url = file_url_transform_relative(file_create_url($image_uri));
    $this->assertEquals($expected_url, $node->{$field_name}->view($display_options)[0]['#markup']);

    // Test the image URL formatter with an image style.
    $display_options['settings']['image_style'] = 'thumbnail';
    $expected_url = file_url_transform_relative(ImageStyle::load('thumbnail')->buildUrl($image_uri));
    $this->assertEquals($expected_url, $node->{$field_name}->view($display_options)[0]['#markup']);
  }

  /**
   * Tests for image field settings.
   */
  public function testImageFieldSettings() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
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
    $this->assertSession()->fieldValueEquals('settings[max_resolution][x]', '100');
    $this->assertSession()->fieldValueEquals('settings[max_resolution][y]', '100');
    $this->assertSession()->fieldValueEquals('settings[min_resolution][x]', '10');
    $this->assertSession()->fieldValueEquals('settings[min_resolution][y]', '10');

    $this->drupalGet('node/add/article');
    $this->assertSession()->pageTextContains('50 KB limit.');
    $this->assertSession()->pageTextContains('Allowed types: ' . $test_image_extension . '.');
    $this->assertSession()->pageTextContains('Images must be larger than 10x10 pixels. Images larger than 100x100 pixels will be resized.');

    // We have to create the article first and then edit it because the alt
    // and title fields do not display until the image has been attached.

    // Create alt text for the image.
    $alt = $this->randomMachineName();

    $nid = $this->uploadNodeImage($test_image, $field_name, 'article', $alt);
    $this->drupalGet('node/' . $nid . '/edit');

    // Verify that the optional fields alt & title are saved & filled.
    $this->assertSession()->fieldValueEquals($field_name . '[0][alt]', $alt);
    $this->assertSession()->fieldValueEquals($field_name . '[0][title]', '');

    // Verify that the attached image is being previewed using the 'medium'
    // style.
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $file = $node->{$field_name}->entity;

    $url = file_url_transform_relative(ImageStyle::load('medium')->buildUrl($file->getFileUri()));
    $this->assertSession()->elementExists('css', 'img[width=40][height=20][class=image-style-medium][src="' . $url . '"]');

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
    $this->drupalGet('node/' . $nid . '/edit');
    $this->submitForm($edit, 'Save');
    $default_output = str_replace("\n", NULL, $renderer->renderRoot($image));
    $this->assertSession()->responseContains($default_output);

    // Verify that alt/title longer than allowed results in a validation error.
    $test_size = 2000;
    $edit = [
      $field_name . '[0][alt]' => $this->randomMachineName($test_size),
      $field_name . '[0][title]' => $this->randomMachineName($test_size),
    ];
    $this->drupalGet('node/' . $nid . '/edit');
    $this->submitForm($edit, 'Save');
    $schema = $field->getFieldStorageDefinition()->getSchema();
    $this->assertSession()->pageTextContains("Alternative text cannot be longer than {$schema['columns']['alt']['length']} characters but is currently {$test_size} characters long.");
    $this->assertSession()->pageTextContains("Title cannot be longer than {$schema['columns']['title']['length']} characters but is currently {$test_size} characters long.");

    // Set cardinality to unlimited and add upload a second image.
    // The image widget is extending on the file widget, but the image field
    // type does not have the 'display_field' setting which is expected by
    // the file widget. This resulted in notices before when cardinality is not
    // 1, so we need to make sure the file widget prevents these notices by
    // providing all settings, even if they are not used.
    // @see FileWidget::formMultipleElements().
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.' . $field_name . '/storage');
    $this->submitForm([
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ], 'Save field settings');
    $edit = [
      'files[' . $field_name . '_1][]' => \Drupal::service('file_system')->realpath($test_image->uri),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    // Add the required alt text.
    $this->submitForm([$field_name . '[1][alt]' => $alt], 'Save');
    $this->assertSession()->pageTextContains('Article ' . $node->getTitle() . ' has been updated.');

    // Assert ImageWidget::process() calls FieldWidget::process().
    $this->drupalGet('node/' . $node->id() . '/edit');
    $edit = [
      'files[' . $field_name . '_2][]' => \Drupal::service('file_system')->realpath($test_image->uri),
    ];
    $this->submitForm($edit, $field_name . '_2_upload_button');
    $this->assertSession()->elementNotExists('css', 'input[name="files[' . $field_name . '_2][]"]');
    $this->assertSession()->elementExists('css', 'input[name="files[' . $field_name . '_3][]"]');
  }

  /**
   * Tests use of a default image with an image field.
   */
  public function testImageFieldDefaultImage() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    // Create a new image field.
    $field_name = strtolower($this->randomMachineName());
    $this->createImageField($field_name, 'article');

    // Create a new node, with no images and verify that no images are
    // displayed.
    $node = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalGet('node/' . $node->id());
    // Verify that no image is displayed on the page by checking for the class
    // that would be used on the image field.
    $this->assertSession()->responseNotMatches('<div class="(.*?)field--name-' . strtr($field_name, '_', '-') . '(.*?)">');
    // Verify that no image style cache tags are found.
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'image_style:');

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
    $this->drupalGet("admin/structure/types/manage/article/fields/node.article.{$field_name}/storage");
    $this->submitForm($edit, 'Save field settings');
    // Clear field definition cache so the new default image is detected.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $default_image = $field_storage->getSetting('default_image');
    $file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $default_image['uuid']);
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
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $file->getCacheTags()[0]);
    // Verify that no image style cache tags are found.
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'image_style:');
    $this->assertSession()->responseContains($default_output);

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
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $file->getCacheTags()[0]);
    // Verify that no image style cache tags are found.
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'image_style:');
    // Default image should not be displayed.
    $this->assertSession()->responseNotContains($default_output);
    // User supplied image should be displayed.
    $this->assertSession()->responseContains($image_output);

    // Remove default image from the field and make sure it is no longer used.
    // Can't use fillField cause Mink can't fill hidden fields.
    $this->drupalGet("admin/structure/types/manage/article/fields/node.article.$field_name/storage");
    $this->getSession()->getPage()->find('css', 'input[name="settings[default_image][uuid][fids]"]')->setValue(0);
    $this->getSession()->getPage()->pressButton(t('Save field settings'));

    // Clear field definition cache so the new default image is detected.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $field_storage = FieldStorageConfig::loadByName('node', $field_name);
    $default_image = $field_storage->getSetting('default_image');
    $this->assertEmpty($default_image['uuid'], 'Default image removed from field.');
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
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.' . $private_field_name . '/storage');
    $this->submitForm($edit, 'Save field settings');
    // Clear field definition cache so the new default image is detected.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    $private_field_storage = FieldStorageConfig::loadByName('node', $private_field_name);
    $default_image = $private_field_storage->getSetting('default_image');
    $file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $default_image['uuid']);

    $this->assertEquals('private', StreamWrapperManager::getScheme($file->getFileUri()), 'Default image uses private:// scheme.');
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
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $file->getCacheTags()[0]);
    // Verify that no image style cache tags are found.
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'image_style:');
    // Default private image should be displayed when no user supplied image
    // is present.
    $this->assertSession()->responseContains($default_output);
  }

}
