<?php

namespace Drupal\Tests\media_library\Kernel;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\MediaType;
use Drupal\media_library\MediaLibraryState;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the media library widget.
 *
 * @coversDefaultClass \Drupal\media_library\Plugin\Field\FieldWidget\MediaLibraryWidget
 * @group media_library
 */
class MediaLibraryWidgetTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
    'media_library',
    'field',
    'image',
    'system',
    'views',
    'user',
    'entity_test',
  ];

  /**
   * An admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The base field definition.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition
   */
  protected BaseFieldDefinition $baseField;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->baseField = BaseFieldDefinition::create('entity_reference')
      ->setName('media')
      ->setSetting('target_type', 'media')
      ->setSetting('handler_settings', ['target_bundles' => ['test_type' => 'test_type']]);
    $this->container->get('state')->set('entity_test.additional_base_field_definitions', [
      'media' => $this->baseField,
    ]);
    $this->container->get('state')->set('entity_test_rev.additional_base_field_definitions', [
      'media' => $this->baseField,
    ]);

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installConfig([
      'system',
      'image',
      'media',
      'media_library',
    ]);

    MediaType::create([
      'id' => 'test_type',
      'label' => 'Test type',
      'source' => 'image',
    ])->save();

    // Create user 1 so the test user doesn't bypass access control.
    $this->createUser();

    $this->adminUser = $this->createUser([
      'administer entity_test content',
      'view media',
    ]);
  }

  /**
   * Test the media library widget access.
   */
  public function testWidgetAccess() {
    $entity = EntityTest::create([
      'name' => 'sample entity',
    ]);
    $entity->save();
    $element = $this->buildWidgetForm($entity);
    $this->assertMediaLibraryStateAccess(TRUE, $this->adminUser, $element['open_button']['#media_library_state']);
  }

  /**
   * Test the media library widget access with a revisionable entity type.
   */
  public function testRevisionableWidgetAccess() {
    $allowed_revision = EntityTestRev::create([
      'name' => 'allowed_access',
    ]);
    $allowed_revision->save();

    $denied_revision = clone $allowed_revision;
    $denied_revision->setNewRevision();
    $denied_revision->name = 'forbid_access';
    $denied_revision->save();

    $element = $this->buildWidgetForm($allowed_revision);
    $this->assertMediaLibraryStateAccess(TRUE, $this->adminUser, $element['open_button']['#media_library_state']);

    $element = $this->buildWidgetForm($denied_revision);
    $this->assertMediaLibraryStateAccess(FALSE, $this->adminUser, $element['open_button']['#media_library_state']);
  }

  /**
   * Assert if the given user has access to the given state.
   *
   * @param bool $access
   *   The access result to assert.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user account.
   * @param \Drupal\media_library\MediaLibraryState $state
   *   The media library state.
   *
   * @throws \Exception
   *
   * @internal
   */
  protected function assertMediaLibraryStateAccess(bool $access, AccountInterface $user, MediaLibraryState $state): void {
    $ui_builder = $this->container->get('media_library.ui_builder');
    $access_result = $ui_builder->checkAccess($user, $state);
    $this->assertEquals($access, $access_result->isAllowed());
  }

  /**
   * Build the media library widget form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to build the form for.
   *
   * @return array
   *   A built form array of the media library widget.
   */
  protected function buildWidgetForm($entity) {
    $form = [
      '#parents' => [],
    ];
    return $this->container->get('plugin.manager.field.widget')->createInstance('media_library_widget', [
      'field_definition' => $this->baseField,
      'settings' => [],
      'third_party_settings' => [],
    ])->formElement($entity->media, 0, ['#description' => ''], $form, new FormState());
  }

}
