<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;

/**
 * @group Recipe
 */
class ConfigActionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'layout_builder',
    'layout_discovery',
  ];

  /**
   * Tests config actions exposed by Layout Builder.
   */
  public function testLayoutBuilderActions(): void {
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');

    $this->installEntitySchema('entity_test_with_bundle');
    EntityTestBundle::create(['id' => 'test'])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = $this->container->get(EntityDisplayRepositoryInterface::class);

    /** @var \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay $display */
    $display = $display_repository->getViewDisplay('entity_test_with_bundle', 'test');
    $this->assertInstanceOf(LayoutBuilderEntityViewDisplay::class, $display);
    $display->save();

    $this->assertFalse($display->isLayoutBuilderEnabled());
    $manager->applyAction('enableLayoutBuilder', $display->getConfigDependencyName(), []);
    $this->assertTrue($display_repository->getViewDisplay('entity_test_with_bundle', 'test')->isLayoutBuilderEnabled());

    $this->assertFalse($display->isOverridable());
    $manager->applyAction('allowLayoutOverrides', $display->getConfigDependencyName(), TRUE);
    $this->assertTrue($display_repository->getViewDisplay('entity_test_with_bundle', 'test')->isOverridable());
    $manager->applyAction('allowLayoutOverrides', $display->getConfigDependencyName(), FALSE);
    $this->assertFalse($display_repository->getViewDisplay('entity_test_with_bundle', 'test')->isOverridable());

    $manager->applyAction('disableLayoutBuilder', $display->getConfigDependencyName(), []);
    $this->assertFalse($display_repository->getViewDisplay('entity_test_with_bundle', 'test')->isLayoutBuilderEnabled());
  }

}
