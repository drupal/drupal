<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * @group Recipe
 */
class EntityMethodConfigActionsTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'layout_builder',
    'layout_discovery',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('node');
    $this->createContentType(['type' => 'test']);

    $this->container->get(EntityDisplayRepositoryInterface::class)
      ->getViewDisplay('node', 'test', 'full')
      ->save();
  }

  public function testSetSingleThirdPartySetting(): void {
    $recipe = <<<YAML
name: Third-party setting
config:
  actions:
    core.entity_view_display.node.test.full:
      setThirdPartySetting:
        module: layout_builder
        key: enabled
        value: true
YAML;
    $recipe = $this->createRecipe($recipe);
    RecipeRunner::processRecipe($recipe);

    /** @var \Drupal\Core\Config\Entity\ThirdPartySettingsInterface $display */
    $display = $this->container->get(EntityDisplayRepositoryInterface::class)
      ->getViewDisplay('node', 'test', 'full');
    $this->assertTrue($display->getThirdPartySetting('layout_builder', 'enabled'));
  }

  public function testSetMultipleThirdPartySettings(): void {
    $recipe = <<<YAML
name: Third-party setting
config:
  actions:
    core.entity_view_display.node.test.full:
      setThirdPartySettings:
        -
          module: layout_builder
          key: enabled
          value: true
        -
          module: layout_builder
          key: allow_custom
          value: true
YAML;
    $recipe = $this->createRecipe($recipe);
    RecipeRunner::processRecipe($recipe);

    /** @var \Drupal\Core\Config\Entity\ThirdPartySettingsInterface $display */
    $display = $this->container->get(EntityDisplayRepositoryInterface::class)
      ->getViewDisplay('node', 'test', 'full');
    $this->assertTrue($display->getThirdPartySetting('layout_builder', 'enabled'));
    $this->assertTrue($display->getThirdPartySetting('layout_builder', 'allow_custom'));
  }

}
