<?php

namespace Drupal\Tests\image\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\File\FileSystemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\EntityViewTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests setting up default images both to the field and field storage.
 *
 * @group image
 */
class ImageFieldDefaultImagesTest extends ImageFieldTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }
  use EntityViewTrait {
    buildEntityView as drupalBuildEntityView;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests CRUD for fields and field storages with default images.
   */
  public function testDefaultImages() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    // Create files to use as the default images.
    $files = $this->drupalGetTestFiles('image');
    // Create 10 files so the default image fids are not a single value.
    for ($i = 1; $i <= 10; $i++) {
      $filename = $this->randomMachineName() . "$i";
      $desired_filepath = 'public://' . $filename;
      \Drupal::service('file_system')->copy($files[0]->uri, $desired_filepath, FileSystemInterface::EXISTS_ERROR);
      $file = File::create(['uri' => $desired_filepath, 'filename' => $filename, 'name' => $filename]);
      $file->save();
    }
    $default_images = [];
    foreach (['field_storage', 'field', 'field2', 'field_storage_new', 'field_new', 'field_storage_private', 'field_private'] as $image_target) {
      $file = File::create((array) array_pop($files));
      $file->save();
      $default_images[$image_target] = $file;
    }

    // Create an image field storage and add a field to the article content
    // type.
    $field_name = strtolower($this->randomMachineName());
    $storage_settings['default_image'] = [
      'uuid' => $default_images['field_storage']->uuid(),
      'alt' => '',
      'title' => '',
      'width' => 0,
      'height' => 0,
    ];
    $field_settings['default_image'] = [
      'uuid' => $default_images['field']->uuid(),
      'alt' => '',
      'title' => '',
      'width' => 0,
      'height' => 0,
    ];
    $widget_settings = [
      'preview_image_style' => 'medium',
    ];
    $field = $this->createImageField($field_name, 'article', $storage_settings, $field_settings, $widget_settings);

    // The field default image id should be 2.
    $this->assertEqual($field->getSetting('default_image')['uuid'], $default_images['field']->uuid());

    // Also test \Drupal\field\Entity\FieldConfig::getSettings().
    $this->assertEqual($field->getSettings()['default_image']['uuid'], $default_images['field']->uuid());

    $field_storage = $field->getFieldStorageDefinition();

    // The field storage default image id should be 1.
    $this->assertEqual($field_storage->getSetting('default_image')['uuid'], $default_images['field_storage']->uuid());

    // Also test \Drupal\field\Entity\FieldStorageConfig::getSettings().
    $this->assertEqual($field_storage->getSettings()['default_image']['uuid'], $default_images['field_storage']->uuid());

    // Add another field with another default image to the page content type.
    $field2 = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'label' => $field->label(),
      'required' => $field->isRequired(),
      'settings' => [
        'default_image' => [
          'uuid' => $default_images['field2']->uuid(),
          'alt' => '',
          'title' => '',
          'width' => 0,
          'height' => 0,
        ],
      ],
    ]);
    $field2->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $widget_settings = $display_repository->getFormDisplay('node', $field->getTargetBundle())->getComponent($field_name);
    $display_repository->getFormDisplay('node', 'page')
      ->setComponent($field_name, $widget_settings)
      ->save();
    $display_repository->getViewDisplay('node', 'page')
      ->setComponent($field_name)
      ->save();

    // Confirm the defaults are present on the article field storage settings
    // form.
    $field_id = $field->id();
    $this->drupalGet("admin/structure/types/manage/article/fields/$field_id/storage");
    $this->assertSession()->hiddenFieldValueEquals('settings[default_image][uuid][fids]', $default_images['field_storage']->id());
    // Confirm the defaults are present on the article field edit form.
    $this->drupalGet("admin/structure/types/manage/article/fields/$field_id");
    $this->assertSession()->hiddenFieldValueEquals('settings[default_image][uuid][fids]', $default_images['field']->id());

    // Confirm the defaults are present on the page field storage settings form.
    $this->drupalGet("admin/structure/types/manage/page/fields/$field_id/storage");
    $this->assertSession()->hiddenFieldValueEquals('settings[default_image][uuid][fids]', $default_images['field_storage']->id());
    // Confirm the defaults are present on the page field edit form.
    $field2_id = $field2->id();
    $this->drupalGet("admin/structure/types/manage/page/fields/$field2_id");
    $this->assertSession()->hiddenFieldValueEquals('settings[default_image][uuid][fids]', $default_images['field2']->id());

    // Confirm that the image default is shown for a new article node.
    $article = $this->drupalCreateNode(['type' => 'article']);
    $article_built = $this->drupalBuildEntityView($article);
    $this->assertEqual(
      $article_built[$field_name][0]['#item']->target_id,
      $default_images['field']->id(),
      new FormattableMarkup(
        'A new article node without an image has the expected default image file ID of @fid.',
        ['@fid' => $default_images['field']->id()]
      )
    );

    // Also check that the field renders without warnings when the label is
    // hidden.
    EntityViewDisplay::load('node.article.default')
      ->setComponent($field_name, ['label' => 'hidden', 'type' => 'image'])
      ->save();
    $this->drupalGet('node/' . $article->id());

    // Confirm that the image default is shown for a new page node.
    $page = $this->drupalCreateNode(['type' => 'page']);
    $page_built = $this->drupalBuildEntityView($page);
    $this->assertEqual(
      $page_built[$field_name][0]['#item']->target_id,
      $default_images['field2']->id(),
      new FormattableMarkup(
        'A new page node without an image has the expected default image file ID of @fid.',
        ['@fid' => $default_images['field2']->id()]
      )
    );

    // Upload a new default for the field storage.
    $default_image_settings = $field_storage->getSetting('default_image');
    $default_image_settings['uuid'] = $default_images['field_storage_new']->uuid();
    $field_storage->setSetting('default_image', $default_image_settings);
    $field_storage->save();

    // Confirm that the new default is used on the article field storage
    // settings form.
    $this->drupalGet("admin/structure/types/manage/article/fields/$field_id/storage");
    $this->assertSession()->hiddenFieldValueEquals('settings[default_image][uuid][fids]', $default_images['field_storage_new']->id());

    // Reload the nodes and confirm the field defaults are used.
    $node_storage->resetCache([$article->id(), $page->id()]);
    $article_built = $this->drupalBuildEntityView($article = $node_storage->load($article->id()));
    $page_built = $this->drupalBuildEntityView($page = $node_storage->load($page->id()));
    $this->assertEqual(
      $article_built[$field_name][0]['#item']->target_id,
      $default_images['field']->id(),
      new FormattableMarkup(
        'An existing article node without an image has the expected default image file ID of @fid.',
        ['@fid' => $default_images['field']->id()]
      )
    );
    $this->assertEqual(
      $page_built[$field_name][0]['#item']->target_id,
      $default_images['field2']->id(),
      new FormattableMarkup(
        'An existing page node without an image has the expected default image file ID of @fid.',
        ['@fid' => $default_images['field2']->id()]
      )
    );

    // Upload a new default for the article's field.
    $default_image_settings = $field->getSetting('default_image');
    $default_image_settings['uuid'] = $default_images['field_new']->uuid();
    $field->setSetting('default_image', $default_image_settings);
    $field->save();

    // Confirm the new field default is used on the article field admin form.
    $this->drupalGet("admin/structure/types/manage/article/fields/$field_id");
    $this->assertSession()->hiddenFieldValueEquals('settings[default_image][uuid][fids]', $default_images['field_new']->id());

    // Reload the nodes.
    $node_storage->resetCache([$article->id(), $page->id()]);
    $article_built = $this->drupalBuildEntityView($article = $node_storage->load($article->id()));
    $page_built = $this->drupalBuildEntityView($page = $node_storage->load($page->id()));

    // Confirm the article uses the new default.
    $this->assertEqual(
      $article_built[$field_name][0]['#item']->target_id,
      $default_images['field_new']->id(),
      new FormattableMarkup(
        'An existing article node without an image has the expected default image file ID of @fid.',
        ['@fid' => $default_images['field_new']->id()]
      )
    );
    // Confirm the page remains unchanged.
    $this->assertEqual(
      $page_built[$field_name][0]['#item']->target_id,
      $default_images['field2']->id(),
      new FormattableMarkup(
        'An existing page node without an image has the expected default image file ID of @fid.',
        ['@fid' => $default_images['field2']->id()]
      )
    );

    // Confirm the default image is shown on the node form.
    $file = File::load($default_images['field_new']->id());
    $this->drupalGet('node/add/article');
    $this->assertRaw($file->getFilename());

    // Remove the field default from articles.
    $default_image_settings = $field->getSetting('default_image');
    $default_image_settings['uuid'] = 0;
    $field->setSetting('default_image', $default_image_settings);
    $field->save();

    // Confirm the article field default has been removed.
    $this->drupalGet("admin/structure/types/manage/article/fields/$field_id");
    $this->assertSession()->hiddenFieldValueEquals('settings[default_image][uuid][fids]', '');

    // Reload the nodes.
    $node_storage->resetCache([$article->id(), $page->id()]);
    $article_built = $this->drupalBuildEntityView($article = $node_storage->load($article->id()));
    $page_built = $this->drupalBuildEntityView($page = $node_storage->load($page->id()));
    // Confirm the article uses the new field storage (not field) default.
    $this->assertEqual(
      $article_built[$field_name][0]['#item']->target_id,
      $default_images['field_storage_new']->id(),
      new FormattableMarkup(
        'An existing article node without an image has the expected default image file ID of @fid.',
        ['@fid' => $default_images['field_storage_new']->id()]
      )
    );
    // Confirm the page remains unchanged.
    $this->assertEqual(
      $page_built[$field_name][0]['#item']->target_id,
      $default_images['field2']->id(),
      new FormattableMarkup(
        'An existing page node without an image has the expected default image file ID of @fid.',
        ['@fid' => $default_images['field2']->id()]
      )
    );

    $non_image = $this->drupalGetTestFiles('text');
    $this->submitForm(['files[settings_default_image_uuid]' => \Drupal::service('file_system')->realpath($non_image[0]->uri)], 'Upload');
    $this->assertText('The specified file text-0.txt could not be uploaded.');
    $this->assertText('Only files with the following extensions are allowed: png gif jpg jpeg.');

    // Confirm the default image is shown on the node form.
    $file = File::load($default_images['field_storage_new']->id());
    $this->drupalGet('node/add/article');
    $this->assertRaw($file->getFilename());

    // Change the default image for the field storage and also change the upload
    // destination to the private filesystem at the same time.
    $default_image_settings = $field_storage->getSetting('default_image');
    $default_image_settings['uuid'] = $default_images['field_storage_private']->uuid();
    $field_storage->setSetting('default_image', $default_image_settings);
    $field_storage->setSetting('uri_scheme', 'private');
    $field_storage->save();

    // Confirm that the new default is used on the article field storage
    // settings form.
    $this->drupalGet("admin/structure/types/manage/article/fields/$field_id/storage");
    $this->assertSession()->hiddenFieldValueEquals('settings[default_image][uuid][fids]', $default_images['field_storage_private']->id());

    // Upload a new default for the article's field after setting the field
    // storage upload destination to 'private'.
    $default_image_settings = $field->getSetting('default_image');
    $default_image_settings['uuid'] = $default_images['field_private']->uuid();
    $field->setSetting('default_image', $default_image_settings);
    $field->save();

    // Confirm the new field field default is used on the article field
    // admin form.
    $this->drupalGet("admin/structure/types/manage/article/fields/$field_id");
    $this->assertSession()->hiddenFieldValueEquals('settings[default_image][uuid][fids]', $default_images['field_private']->id());
  }

  /**
   * Tests image field and field storage having an invalid default image.
   */
  public function testInvalidDefaultImage() {
    $field_storage = FieldStorageConfig::create([
      'field_name' => mb_strtolower($this->randomMachineName()),
      'entity_type' => 'node',
      'type' => 'image',
      'settings' => [
        'default_image' => [
          'uuid' => 100000,
        ],
      ],
    ]);
    $field_storage->save();
    $settings = $field_storage->getSettings();
    // The non-existent default image should not be saved.
    $this->assertNull($settings['default_image']['uuid']);

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'label' => $this->randomMachineName(),
      'settings' => [
        'default_image' => [
          'uuid' => 100000,
        ],
      ],
    ]);
    $field->save();
    $settings = $field->getSettings();
    // The non-existent default image should not be saved.
    $this->assertNull($settings['default_image']['uuid']);
  }

}
