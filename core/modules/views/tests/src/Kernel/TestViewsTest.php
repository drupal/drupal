<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\config_test\TestInstallStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\TypedConfigManager;
use Drupal\KernelTests\KernelTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests that test views provided by all modules match schema.
 *
 * @group config
 */
class TestViewsTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    // For NodeType config entities to exist, its module must be installed.
    'node',
    // The `DRUPAL_OPTIONAL` constant is used by the NodeType config entity type
    // and only available if the system module is installed.
    // `system.menu.tools` is a config dependency. It is one of the default
    // config of the System module.
    // @see \DRUPAL_OPTIONAL
    // @see \Drupal\node\Entity\NodeType::$preview_mode
    // @see core/modules/views/tests/modules/views_test_config/test_views/views.view.test_row_render_cache_none.yml
    'system',
    // There are a number of `field.storage.*.*` config dependencies. For these
    // to be created, the Field module must be installed.
    'field',
    // Some of the `field.storage.*.*` config dependencies use the `link` field
    // type. For fields of this type to be created the module must be installed.
    // @see core/modules/link/tests/modules/link_test_views/test_views/views.view.test_link_tokens.yml
    // @see \Drupal\Tests\link\Functional\Views\LinkViewsTokensTest
    'link',
    // `field.storage.node.body` config entity is a config dependency. It is one
    // of the default config of the Node module. And it requires the
    // `text_with_summary` field type, which is provided by the Text module.
    // @see core/modules/node/tests/modules/node_test_views/test_views/views.view.test_node_tokens.yml
    'text',
    // For Vocabulary config entities to exist, its module must be installed.
    'taxonomy',
    // `user.role.authenticated` is a config dependency. It is one of the
    // default config of the User module.
    'user',
    // `field.storage.entity_test.field_test` is a config dependency. It uses
    // the `entity_type` content entity type, which is provided by the
    // entity_test module.
    // @see core/modules/views/tests/modules/views_test_config/test_views/views.view.test_field_field_attachment_test.yml
    // @see \Drupal\Tests\views\Kernel\Handler\FieldFieldTest::setUp()
    'entity_test',
    'views_test_data',
    // `block_content` is a module dependency.
    // @see core/modules/block_content/tests/modules/block_content_test_views/test_views/views.view.test_block_content_redirect_destination.yml
    'block_content',
    // `comment` is a module dependency.
    // @see core/modules/comment/tests/modules/comment_test_views/test_views/views.view.test_comment.yml
    'comment',
    // `comment_test_views` is a module dependency.
    // @see core/modules/comment/tests/modules/comment_test_views/test_views/views.view.test_comment_user_uid.yml
    'comment_test_views',
    // `contact` is a module dependency.
    // @see core/modules/contact/tests/modules/contact_test_views/test_views/views.view.test_contact_link.yml
    'contact',
    // `content_translation` is a module dependency.
    // @see core/modules/content_translation/tests/modules/content_translation_test_views/test_views/views.view.test_entity_translations_link.yml
    'content_translation',
    // `content_translation` is a module dependency.
    // @see core/modules/content_translation/tests/modules/content_translation_test_views/test_views/views.view.test_entity_translations_link.yml
    'content_translation',
    // The `language_content_settings` config entity type must exist because the
    // `content_translation` module A) depends on it, B) actively uses it
    // whenever new bundles are installed.
    // @see content_translation_entity_bundle_info_alter()
    'language',
    // `datetime` is a module dependency.
    // @see core/modules/datetime/tests/modules/datetime_test/test_views/views.view.test_exposed_filter_datetime.yml
    'datetime',
    // `dblog` is a module dependency.
    // @see core/modules/dblog/tests/modules/dblog_test_views/test_views/views.view.dblog_integration_test.yml
    'dblog',
    // `file` is a module dependency.
    // @see core/modules/image/tests/modules/image_test_views/test_views/views.view.test_image_user_image_data.yml
    'file',
    // `media` is a module dependency.
    // @see core/modules/media/tests/modules/media_test_views/test_views/views.view.test_media_revision_uid.yml
    'media',
    // `rest` is a module dependency.
    // @see core/modules/rest/tests/modules/rest_test_views/test_views/views.view.test_excluded_field_token_display.yml
    'rest',
    // `serialization` is a dependency of the `rest` module.
    'serialization',
    // `rest_test_views` is a module dependency.
    // @see core/modules/rest/tests/modules/rest_test_views/test_views/views.view.test_serializer_node_display_field.yml
    'rest_test_views',
    // `search` is a module dependency.
    // @see core/modules/views/tests/modules/views_test_config/test_views/views.view.test_argument_dependency.yml
    'search',
    // `history` is a module dependency.
    // @see core/modules/views/tests/modules/views_test_config/test_views/views.view.test_history.yml
    'history',
    // The `image` module is required by at least one of the Node module's
    // views.
    'image',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // `field.storage.node.body` config entity is a config dependency. It is one
    // of the default config of the Node module.
    // @see core/modules/node/tests/modules/node_test_views/test_views/views.view.test_node_tokens.yml
    $this->installEntitySchema('node');
    $this->installConfig('node');
    // `user.role.authenticated` is a config dependency. It is one of the
    // default config of the User module.
    // @see core/modules/views/tests/modules/views_test_config/test_views/views.view.test_feed_icon.yml
    $this->installConfig('user');
    // `system.menu.tools` is a config dependency. It is one of the default
    // config of the System module.
    // @see core/modules/views/tests/modules/views_test_config/test_views/views.view.test_row_render_cache_none.yml
    $this->installConfig('system');
    // `node.type.article` is a config dependency.
    // @see core/modules/options/tests/options_test_views/test_views/views.view.test_options_list_argument_numeric.yml
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    // `node.type.page` is a config dependency.
    // @see core/modules/views/tests/modules/views_test_config/test_views/views.view.test_argument_default_node.yml
    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    // `taxonomy.vocabulary.tags` is a config dependency.
    // @see core/modules/taxonomy/tests/modules/taxonomy_test_views/test_views/views.view.test_taxonomy_exposed_grouped_filter.yml
    Vocabulary::create(['vid' => 'tags', 'name' => 'Tags'])->save();
    // `taxonomy.vocabulary.test_exposed_checkboxes` is a config dependency.
    // @see core/modules/views/tests/modules/views_test_config/test_views/views.view.test_exposed_form_checkboxes.yml
    Vocabulary::create(['vid' => 'test_exposed_checkboxes', 'name' => 'Exposed checkboxes test'])->save();
    // `core.entity_view_mode.node.default` is a config dependency.
    // @see core/modules/views/tests/modules/views_test_config/test_views/views.view.test_entity_field_rendered_entity.yml
    EntityViewMode::create([
      'id' => 'node.default',
      'label' => 'Default',
      'targetEntityType' => 'node',
    ])->save();
    // `field.storage.node.field_link` is a config dependency.
    // @see core/modules/link/tests/modules/link_test_views/test_views/views.view.test_link_tokens.yml
    // @see \Drupal\Tests\link\Functional\Views\LinkViewsTokensTest
    FieldStorageConfig::create([
      'field_name' => 'field_link',
      'type' => 'link',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    // `field.storage.node.field_test` is a config dependency.
    // @see core/modules/field/tests/modules/field_test_views/test_views/views.view.test_view_field_delete.yml
    // @see \Drupal\Tests\field_ui\Functional\FieldUIDeleteTest::testDeleteField()
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'type' => 'integer',
      'entity_type' => 'node',
    ])->save();
    // `field.storage.entity_test.field_test` is a config dependency.
    // @see core/modules/views/tests/modules/views_test_config/test_views/views.view.test_field_field_attachment_test.yml
    // @see \Drupal\Tests\views\Kernel\Handler\FieldFieldTest::setUp()
    $this->installEntitySchema('entity_test');
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'integer',
    ])->save();
    // `views.view.test_group_rows` is a config dependency.
    // @see core/modules/views/tests/modules/views_test_config/test_views/views.view.test_group_rows.yml
    // @see \Drupal\Tests\views\Functional\Handler\FieldGroupRowsWebTest::setUp()
    FieldStorageConfig::create([
      'field_name' => 'field_views_testing_group_rows',
      'entity_type' => 'node',
      'type' => 'text',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    ViewTestData::createTestViews(self::class, ['views_test_data']);
  }

  /**
   * Tests default configuration data type.
   */
  public function testDefaultConfig(): void {
    // Create a typed config manager with access to configuration schema in
    // every module, profile and theme.
    $typed_config = new TypedConfigManager(
      \Drupal::service('config.storage'),
      new TestInstallStorage(InstallStorage::CONFIG_SCHEMA_DIRECTORY),
      \Drupal::service('cache.discovery'),
      \Drupal::service('module_handler'),
      \Drupal::service('class_resolver')
    );
    $typed_config->setValidationConstraintManager(\Drupal::service('validation.constraint'));
    // Avoid restricting to the config schemas discovered.
    $this->container->get('cache.discovery')->delete('typed_config_definitions');

    // Create a configuration storage with access to default configuration in
    // every module, profile and theme.
    $default_config_storage = new TestInstallStorage('test_views');

    foreach ($default_config_storage->listAll() as $config_name) {
      // Skip files provided by the config_schema_test module since that module
      // is explicitly for testing schema.
      if (str_starts_with($config_name, 'config_schema_test')) {
        continue;
      }

      $data = $default_config_storage->read($config_name);
      $this->assertConfigSchema($typed_config, $config_name, $data);
    }
  }

}
