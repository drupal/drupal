<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\comment\Entity\CommentType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Content translation settings.
 *
 * @group content_translation
 */
class ContentTranslationStandardFieldsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'content_translation',
    'node',
    'comment',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer languages',
      'administer content translation',
      'administer content types',
      'administer node fields',
      'administer comment fields',
      'administer comments',
      'administer comment types',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that translatable fields are being rendered.
   */
  public function testFieldTranslatableArticle() {
    // Install block and field modules.
    \Drupal::service('module_installer')->install(
      [
        'block',
        'block_content',
        'filter',
        'image',
        'text',
      ]);

    // Create a basic block type with a body field.
    $bundle = BlockContentType::create([
      'id' => 'basic',
      'label' => 'Basic',
      'revision' => FALSE,
    ]);
    $bundle->save();
    block_content_add_body_field($bundle->id());

    // Create a comment type with a body field.
    $bundle = CommentType::create([
      'id' => 'comment',
      'label' => 'Comment',
      'target_entity_type_id' => 'node',
    ]);
    $bundle->save();
    \Drupal::service('comment.manager')->addBodyField('comment');

    // Create the article content type and add a comment, image and tag field.
    $this->drupalCreateContentType(['type' => 'article', 'title' => 'Article']);

    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_type_manager->getStorage('field_storage_config')->create([
      'entity_type' => 'node',
      'field_name' => 'comment',
      'type' => 'text',
    ])->save();

    $entity_type_manager->getStorage('field_config')->create([
      'label' => 'Comments',
      'field_name' => 'comment',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    $entity_type_manager->getStorage('field_storage_config')->create([
      'entity_type' => 'node',
      'field_name' => 'field_image',
      'type' => 'image',
    ])->save();

    $entity_type_manager->getStorage('field_config')->create([
      'label' => 'Image',
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    $entity_type_manager->getStorage('field_storage_config')->create([
      'entity_type' => 'node',
      'field_name' => 'field_tags',
      'type' => 'text',
    ])->save();

    $entity_type_manager->getStorage('field_config')->create([
      'label' => 'Tags',
      'field_name' => 'field_tags',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    $entity_type_manager->getStorage('field_storage_config')->create([
      'entity_type' => 'user',
      'field_name' => 'user_picture',
      'type' => 'image',
    ])->save();

    // Add a user picture field to the user entity.
    $entity_type_manager->getStorage('field_config')->create([
      'label' => 'Tags',
      'field_name' => 'user_picture',
      'entity_type' => 'user',
      'bundle' => 'user',
    ])->save();

    $path = 'admin/config/regional/content-language';
    $this->drupalGet($path);

    // Check content block fields.
    $this->assertSession()->checkboxChecked('edit-settings-block-content-basic-fields-body');

    // Check comment fields.
    $this->assertSession()->checkboxChecked('edit-settings-comment-comment-fields-comment-body');

    // Check node fields.
    $this->assertSession()->checkboxChecked('edit-settings-node-article-fields-comment');
    $this->assertSession()->checkboxChecked('edit-settings-node-article-fields-field-image');
    $this->assertSession()->checkboxChecked('edit-settings-node-article-fields-field-tags');

    // Check user fields.
    $this->assertSession()->checkboxChecked('edit-settings-user-user-fields-user-picture');
  }

  /**
   * Tests that revision_log is not translatable.
   */
  public function testRevisionLogNotTranslatable() {
    $path = 'admin/config/regional/content-language';
    $this->drupalGet($path);
    $this->assertSession()->fieldNotExists('edit-settings-node-article-fields-revision-log');
  }

}
