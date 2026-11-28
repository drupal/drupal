<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\TestTools\Attribute\ShareEnvironment;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests validation of entity_view_display entities.
 */
#[Group('Entity')]
#[Group('Validation')]
#[Group('config')]
#[RunTestsInSeparateProcesses]
#[ShareEnvironment]
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
  protected function setUpEnvironment(): void {
    parent::setUpEnvironment();

    $this->installEntitySchema('node');
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

    EntityViewMode::create([
      'id' => 'user.test',
      'label' => 'Test',
      'targetEntityType' => 'user',
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
      'id' => 'entity_test_with_bundle.two.full',
      'targetEntityType' => 'entity_test_with_bundle',
      'bundle' => 'two',
    ]);
  }

}
