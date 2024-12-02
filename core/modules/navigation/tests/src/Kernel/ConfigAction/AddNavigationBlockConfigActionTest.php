<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Kernel\ConfigAction;

use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * @covers \Drupal\navigation\Plugin\ConfigAction\AddNavigationBlock
 * @group navigation
 * @group Recipe
 */
class AddNavigationBlockConfigActionTest extends KernelTestBase {

  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'navigation',
    'layout_builder',
    'layout_discovery',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('navigation');
  }

  /**
   * Tests add item logic.
   *
   * @testWith [0, 0]
   * [1, 1]
   * [3, 3]
   * [7, 3]
   */
  public function testAddBlockToNavigation($delta, $computed_delta): void {
    // Load the navigation section storage.
    $navigation_storage = \Drupal::service('plugin.manager.layout_builder.section_storage')->load('navigation', [
      'navigation' => new Context(new ContextDefinition('string'), 'navigation'),
    ]);
    $section = $navigation_storage->getSection(0);
    $components = $section->getComponentsByRegion('content');
    $this->assertCount(3, $components);
    $data = [
      'delta' => $delta,
      'configuration' => [
        'id' => 'navigation_menu:content',
        'label' => 'Content From Recipe',
        'label_display' => 1,
        'provider' => 'navigation',
        'level' => 1,
        'depth' => 2,
      ],
    ];

    // Use the action to add a new block to Navigation.
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    $manager->applyAction('addNavigationBlock', 'navigation.block_layout', $data);

    // Load the config after the execution.
    $navigation_storage = \Drupal::service('plugin.manager.layout_builder.section_storage')->load('navigation', [
      'navigation' => new Context(new ContextDefinition('string'), 'navigation'),
    ]);
    $section = $navigation_storage->getSection(0);
    $components = $section->getComponentsByRegion('content');
    $this->assertCount(4, $components);
    $component = array_values($components)[$computed_delta];
    $this->assertSame('content', $component->getRegion());
    $this->assertEquals($data['configuration'], $component->get('configuration'));
  }

  /**
   * Checks invalid config exception.
   */
  public function testActionOnlySupportsNavigationConfig(): void {
    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage('addNavigationBlock can only be executed for the navigation.block_layout config.');
    // Try to apply the Config Action against an unexpected config entity.
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    $manager->applyAction('addNavigationBlock', 'navigation.settings', []);
  }

}
