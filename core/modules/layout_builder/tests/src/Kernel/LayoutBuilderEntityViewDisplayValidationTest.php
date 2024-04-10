<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests validation of Layout Builder's entity_view_display entities.
 *
 * @group layout_builder
 * @group #slow
 */
class LayoutBuilderEntityViewDisplayValidationTest extends ConfigEntityValidationTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'layout_builder',
    'node',
    'text',
    'user',
  ];

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
      'id' => 'node.layout',
      'label' => 'Layout',
      'targetEntityType' => 'node',
    ])->save();

    $this->entity = $this->container->get(EntityDisplayRepositoryInterface::class)
      ->getViewDisplay('node', 'one', 'layout');
    $this->assertInstanceOf(LayoutBuilderEntityViewDisplay::class, $this->entity);
    $this->entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testLabelValidation(): void {
    // @todo Remove this override in https://www.drupal.org/i/2939931. The label of Layout Builder's EntityViewDisplay override is computed dynamically, that issue will change this.
    // @see \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay::label()
    $this->markTestSkipped();
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
