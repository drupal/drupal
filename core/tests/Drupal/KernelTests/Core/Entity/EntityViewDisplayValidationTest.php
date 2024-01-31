<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests validation of entity_view_display entities.
 *
 * @group Entity
 * @group Validation
 */
class EntityViewDisplayValidationTest extends ConfigEntityValidationTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected bool $hasLabel = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'field', 'node', 'text', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('node');
    $this->createContentType(['type' => 'one']);
    $this->createContentType(['type' => 'two']);

    EntityTestBundle::create(['id' => 'one'])->save();
    EntityTestBundle::create(['id' => 'two'])->save();

    EntityViewMode::create([
      'id' => 'node.test',
      'label' => 'Test',
      'targetEntityType' => 'node',
    ])->save();

    $this->entity = $this->container->get(EntityDisplayRepositoryInterface::class)
      ->getViewDisplay('node', 'one', 'test');
    $this->entity->save();
  }

  /**
   * Tests that the target bundle of the entity view display is checked.
   */
  public function testTargetBundleMustExist(): void {
    $this->entity->set('bundle', 'superhero');
    $this->assertValidationErrors([
      '' => "The 'bundle' property cannot be changed.",
      'bundle' => "The 'superhero' bundle does not exist on the 'node' entity type.",
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testImmutableProperties(array $valid_values = []): void {
    parent::testImmutableProperties([
      'targetEntityType' => 'entity_test_with_bundle',
      'bundle' => 'two',
    ]);
  }

}
