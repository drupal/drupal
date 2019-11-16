<?php

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * @coversDefaultClass \Drupal\workspaces\Plugin\Validation\Constraint\EntityReferenceSupportedNewEntitiesConstraintValidator
 * @group workspaces
 */
class EntityReferenceSupportedNewEntitiesConstraintValidatorTest extends KernelTestBase {

  use UserCreationTrait;
  use WorkspaceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'workspaces',
    'entity_test',
    'path_alias',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->createUser();

    $fields['supported_reference'] = BaseFieldDefinition::create('entity_reference')->setSetting('target_type', 'entity_test_mulrevpub');
    $fields['unsupported_reference'] = BaseFieldDefinition::create('entity_reference')->setSetting('target_type', 'entity_test');
    $this->container->get('state')->set('entity_test_mulrevpub.additional_base_field_definitions', $fields);

    $this->installEntitySchema('entity_test_mulrevpub');
    $this->initializeWorkspacesModule();
  }

  /**
   * @covers ::validate
   */
  public function testNewEntitiesAllowedInDefaultWorkspace() {
    $entity = EntityTestMulRevPub::create([
      'unsupported_reference' => [
        'entity' => EntityTest::create([]),
      ],
      'supported_reference' => [
        'entity' => EntityTest::create([]),
      ],
    ]);
    $this->assertCount(0, $entity->validate());
  }

  /**
   * @covers ::validate
   */
  public function testNewEntitiesForbiddenInNonDefaultWorkspace() {
    $this->switchToWorkspace('stage');
    $entity = EntityTestMulRevPub::create([
      'unsupported_reference' => [
        'entity' => EntityTest::create([]),
      ],
      'supported_reference' => [
        'entity' => EntityTestMulRevPub::create([]),
      ],
    ]);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('<em class="placeholder">Test entity entities</em> can only be created in the default workspace.', $violations[0]->getMessage());
  }

}
