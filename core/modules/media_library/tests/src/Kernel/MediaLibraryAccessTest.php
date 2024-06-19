<?php

declare(strict_types=1);

namespace Drupal\Tests\media_library\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\entity_test\Entity\EntityTestWithBundle;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media_library\MediaLibraryState;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Views;

/**
 * Tests the media library access.
 *
 * @group media_library
 */
class MediaLibraryAccessTest extends KernelTestBase {

  use UserCreationTrait;
  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'media',
    'media_library',
    'media_library_test',
    'filter',
    'file',
    'field',
    'image',
    'system',
    'views',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');
    $this->installEntitySchema('entity_test_with_bundle');
    $this->installEntitySchema('filter_format');
    $this->installEntitySchema('media');
    $this->installConfig([
      'field',
      'system',
      'file',
      'image',
      'media',
      'media_library',
    ]);

    EntityTestBundle::create(['id' => 'test'])->save();

    $field_storage = FieldStorageConfig::create([
      'type' => 'entity_reference',
      'field_name' => 'field_test_media',
      'entity_type' => 'entity_test_with_bundle',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test',
    ])->save();

    // Create an account with special UID 1.
    $this->createUser([]);
  }

  /**
   * Tests that the field widget opener respects entity creation permissions.
   */
  public function testFieldWidgetEntityCreateAccess(): void {
    /** @var \Drupal\media_library\MediaLibraryUiBuilder $ui_builder */
    $ui_builder = $this->container->get('media_library.ui_builder');

    // Create a media library state to test access.
    $state = MediaLibraryState::create('media_library.opener.field_widget', ['file', 'image'], 'file', 2, [
      'entity_type_id' => 'entity_test_with_bundle',
      'bundle' => 'test',
      'field_name' => 'field_test_media',
    ]);

    $access_result = $ui_builder->checkAccess($this->createUser(), $state);
    $this->assertAccess($access_result, FALSE, "The following permissions are required: 'administer entity_test content' OR 'administer entity_test_with_bundle content' OR 'create test entity_test_with_bundle entities'.", [], ['url.query_args', 'user.permissions']);

    // Create a user with the appropriate permissions and assert that access is
    // granted.
    $account = $this->createUser([
      'create test entity_test_with_bundle entities',
      'view media',
    ]);
    $access_result = $ui_builder->checkAccess($account, $state);
    $this->assertAccess($access_result, TRUE, NULL, Views::getView('media_library')->storage->getCacheTags(), ['url.query_args', 'user.permissions']);
  }

  /**
   * @covers \Drupal\media_library\MediaLibraryEditorOpener::checkAccess
   *
   * @param bool $media_embed_enabled
   *   Whether to test with media_embed filter enabled on the text format.
   * @param bool $can_use_format
   *   Whether the logged in user is allowed to use the text format.
   *
   * @dataProvider editorOpenerAccessProvider
   */
  public function testEditorOpenerAccess($media_embed_enabled, $can_use_format): void {
    $format = $this->container
      ->get('entity_type.manager')
      ->getStorage('filter_format')->create([
        'format' => $this->randomMachineName(),
        'name' => $this->randomString(),
        'filters' => [
          'media_embed' => ['status' => $media_embed_enabled],
        ],
      ]);
    $format->save();

    $permissions = [
      'access media overview',
      'view media',
    ];
    if ($can_use_format) {
      $permissions[] = $format->getPermissionName();
    }

    $state = MediaLibraryState::create(
      'media_library.opener.editor',
      ['image'],
      'image',
      1,
      ['filter_format_id' => $format->id()]
    );

    $access_result = $this->container
      ->get('media_library.ui_builder')
      ->checkAccess($this->createUser($permissions), $state);

    if ($media_embed_enabled && $can_use_format) {
      $this->assertAccess($access_result, TRUE, NULL, Views::getView('media_library')->storage->getCacheTags(), ['user.permissions']);
    }
    else {
      $this->assertAccess($access_result, FALSE, NULL, [], ['user.permissions']);
    }
  }

  /**
   * Data provider for ::testEditorOpenerAccess.
   */
  public static function editorOpenerAccessProvider() {
    return [
      'media_embed filter enabled' => [
        TRUE,
        TRUE,
      ],
      'media_embed filter disabled' => [
        FALSE,
        TRUE,
      ],
      'media_embed filter enabled, user not allowed to use text format' => [
        TRUE,
        FALSE,
      ],
    ];
  }

  /**
   * Tests that the field widget opener respects entity-specific access.
   */
  public function testFieldWidgetEntityEditAccess(): void {
    /** @var \Drupal\media_library\MediaLibraryUiBuilder $ui_builder */
    $ui_builder = $this->container->get('media_library.ui_builder');

    $forbidden_entity = EntityTestWithBundle::create([
      'type' => 'test',
      // This label will automatically cause an access denial.
      // @see \Drupal\entity_test\EntityTestAccessControlHandler::checkAccess()
      'name' => 'forbid_access',
    ]);
    $forbidden_entity->save();

    // Create a media library state to test access.
    $state = MediaLibraryState::create('media_library.opener.field_widget', ['file', 'image'], 'file', 2, [
      'entity_type_id' => $forbidden_entity->getEntityTypeId(),
      'bundle' => $forbidden_entity->bundle(),
      'field_name' => 'field_test_media',
      'entity_id' => $forbidden_entity->id(),
    ]);

    $access_result = $ui_builder->checkAccess($this->createUser(), $state);
    $this->assertAccess($access_result, FALSE, NULL, [], ['url.query_args']);

    $neutral_entity = EntityTestWithBundle::create([
      'type' => 'test',
      // This label will result in neutral access.
      // @see \Drupal\entity_test\EntityTestAccessControlHandler::checkAccess()
      'name' => $this->randomString(),
    ]);
    $neutral_entity->save();

    $parameters = $state->getOpenerParameters();
    $parameters['entity_id'] = $neutral_entity->id();
    $state = MediaLibraryState::create(
      $state->getOpenerId(),
      $state->getAllowedTypeIds(),
      $state->getSelectedTypeId(),
      $state->getAvailableSlots(),
      $parameters
    );

    $access_result = $ui_builder->checkAccess($this->createUser(), $state);
    $this->assertTrue($access_result->isNeutral());
    $this->assertAccess($access_result, FALSE, NULL, [], ['url.query_args', 'user.permissions']);

    // Give the user permission to edit the entity and assert that access is
    // granted.
    $account = $this->createUser([
      'administer entity_test content',
      'view media',
    ]);
    $access_result = $ui_builder->checkAccess($account, $state);
    $this->assertAccess($access_result, TRUE, NULL, Views::getView('media_library')->storage->getCacheTags(), ['url.query_args', 'user.permissions']);
  }

  /**
   * Data provider for ::testFieldWidgetEntityFieldAccess().
   *
   * @return array[]
   *   Sets of arguments to pass to the test method.
   */
  public static function providerFieldWidgetEntityFieldAccess(): array {
    return [
      ['entity_reference'],
      ['entity_reference_subclass'],
    ];
  }

  /**
   * Tests that the field widget opener respects entity field-level access.
   *
   * @param string $field_type
   *   The field type.
   *
   * @dataProvider providerFieldWidgetEntityFieldAccess
   */
  public function testFieldWidgetEntityFieldAccess(string $field_type): void {
    $field_storage = FieldStorageConfig::create([
      'type' => $field_type,
      'entity_type' => 'entity_test_with_bundle',
      // The media_library_test module will deny access to this field.
      // @see media_library_test_entity_field_access()
      'field_name' => 'field_media_no_access',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test',
    ])->save();

    /** @var \Drupal\media_library\MediaLibraryUiBuilder $ui_builder */
    $ui_builder = $this->container->get('media_library.ui_builder');

    // Create an account with administrative access to the test entity type,
    // so that we can be certain that field access is checked.
    $account = $this->createUser(['administer entity_test content']);

    // Test that access is denied even without an entity to work with.
    $state = MediaLibraryState::create('media_library.opener.field_widget', ['file', 'image'], 'file', 2, [
      'entity_type_id' => 'entity_test_with_bundle',
      'bundle' => 'test',
      'field_name' => $field_storage->getName(),
    ]);
    $access_result = $ui_builder->checkAccess($account, $state);
    $this->assertAccess($access_result, FALSE, 'Field access denied by test module', [], ['url.query_args', 'user.permissions']);

    // Assert that field access is also checked with a real entity.
    $entity = EntityTestWithBundle::create([
      'type' => 'test',
      'name' => $this->randomString(),
    ]);
    $entity->save();

    $parameters = $state->getOpenerParameters();
    $parameters['entity_id'] = $entity->id();

    $state = MediaLibraryState::create(
      $state->getOpenerId(),
      $state->getAllowedTypeIds(),
      $state->getSelectedTypeId(),
      $state->getAvailableSlots(),
      $parameters
    );
    $access_result = $ui_builder->checkAccess($account, $state);
    $this->assertAccess($access_result, FALSE, 'Field access denied by test module', [], ['url.query_args', 'user.permissions']);
  }

  /**
   * Tests that media library access respects the media_library view.
   */
  public function testViewAccess(): void {
    /** @var \Drupal\media_library\MediaLibraryUiBuilder $ui_builder */
    $ui_builder = $this->container->get('media_library.ui_builder');

    // Create a media library state to test access.
    $state = MediaLibraryState::create('media_library.opener.field_widget', ['file', 'image'], 'file', 2, [
      'entity_type_id' => 'entity_test_with_bundle',
      'bundle' => 'test',
      'field_name' => 'field_test_media',
    ]);

    // Create a clone of the view so we can reset the original later.
    $view_original = clone Views::getView('media_library');

    // Create our test users. Both have permission to create entity_test content
    // so that we can specifically test Views-related access checking.
    // @see ::testEntityCreateAccess()
    $forbidden_account = $this->createUser([
      'create test entity_test_with_bundle entities',
    ]);
    $allowed_account = $this->createUser([
      'create test entity_test_with_bundle entities',
      'view media',
    ]);

    // Assert the 'view media' permission is needed to access the library and
    // validate the cache dependencies.
    $access_result = $ui_builder->checkAccess($forbidden_account, $state);
    $this->assertAccess($access_result, FALSE, "The 'view media' permission is required.", $view_original->storage->getCacheTags(), ['url.query_args', 'user.permissions']);

    // Assert that the media library access is denied when the view widget
    // display is deleted.
    $view_storage = Views::getView('media_library')->storage;
    $displays = $view_storage->get('display');
    unset($displays['widget']);
    $view_storage->set('display', $displays);
    $view_storage->save();
    $access_result = $ui_builder->checkAccess($allowed_account, $state);
    $this->assertAccess($access_result, FALSE, 'The media library widget display does not exist.', $view_original->storage->getCacheTags());

    // Restore the original view and assert that the media library controller
    // works again.
    $view_original->storage->save();
    $access_result = $ui_builder->checkAccess($allowed_account, $state);
    $this->assertAccess($access_result, TRUE, NULL, $view_original->storage->getCacheTags(), ['url.query_args', 'user.permissions']);

    // Assert that the media library access is denied when the entire media
    // library view is deleted.
    Views::getView('media_library')->storage->delete();
    $access_result = $ui_builder->checkAccess($allowed_account, $state);
    $this->assertAccess($access_result, FALSE, 'The media library view does not exist.');
  }

  /**
   * Tests that the media library respects arbitrary access to the add form.
   */
  public function testAddFormAccess(): void {
    // Access is denied if the media library is trying to create media whose
    // type name is 'deny_access'. Also create a second media type that we *can*
    // add, so we can be certain that the add form is otherwise visible.
    // @see media_library_test_media_create_access()
    $media_types = [
      $this->createMediaType('image', ['id' => 'deny_access'])->id(),
      $this->createMediaType('image')->id(),
    ];

    $account = $this->createUser(['create media']);
    $this->setCurrentUser($account);

    /** @var \Drupal\media_library\MediaLibraryUiBuilder $ui_builder */
    $ui_builder = $this->container->get('media_library.ui_builder');

    $state = MediaLibraryState::create('test', $media_types, $media_types[0], 1);
    $build = $ui_builder->buildUi($state);
    $this->assertEmpty($build['content']['form']);

    $state = MediaLibraryState::create('test', $media_types, $media_types[1], 1);
    $build = $ui_builder->buildUi($state);
    $this->assertNotEmpty($build['content']['form']);
  }

  /**
   * Asserts various aspects of an access result.
   *
   * @param \Drupal\Core\Access\AccessResult $access_result
   *   The access result.
   * @param bool $is_allowed
   *   The expected access status.
   * @param string $expected_reason
   *   (optional) The expected reason attached to the access result.
   * @param string[] $expected_cache_tags
   *   (optional) The expected cache tags attached to the access result.
   * @param string[] $expected_cache_contexts
   *   (optional) The expected cache contexts attached to the access result.
   */
  private function assertAccess(AccessResult $access_result, bool $is_allowed, ?string $expected_reason = NULL, array $expected_cache_tags = [], array $expected_cache_contexts = []): void {
    $this->assertSame($is_allowed, $access_result->isAllowed());
    if ($access_result instanceof AccessResultReasonInterface && isset($expected_reason)) {
      $this->assertSame($expected_reason, $access_result->getReason());
    }
    $this->assertEqualsCanonicalizing($expected_cache_tags, $access_result->getCacheTags());
    $this->assertEqualsCanonicalizing($expected_cache_contexts, $access_result->getCacheContexts());
  }

}
