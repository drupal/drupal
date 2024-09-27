<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Kernel\ConfigAction;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * @covers \Drupal\content_moderation\Plugin\ConfigAction\AddModeration
 * @covers \Drupal\content_moderation\Plugin\ConfigAction\AddModerationDeriver
 * @group content_moderation
 * @group Recipe
 */
class AddModerationConfigActionTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use RecipeTestTrait {
    createRecipe as traitCreateRecipe;
  }
  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  public function testAddEntityTypeAndBundle(): void {
    $this->installConfig('node');

    $this->createContentType(['type' => 'a']);
    $this->createContentType(['type' => 'b']);
    $this->createContentType(['type' => 'c']);
    $this->createVocabulary(['vid' => 'tags']);

    $recipe = $this->createRecipe('workflows.workflow.editorial');
    RecipeRunner::processRecipe($recipe);

    /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface $plugin */
    $plugin = Workflow::load('editorial')?->getTypePlugin();
    $this->assertSame(['a', 'b'], $plugin->getBundlesForEntityType('node'));
    $this->assertSame(['tags'], $plugin->getBundlesForEntityType('taxonomy_term'));
  }

  public function testWorkflowMustBeContentModeration(): void {
    $this->enableModules(['workflows', 'workflow_type_test']);

    $workflow = Workflow::create([
      'id' => 'test',
      'label' => 'Test workflow',
      'type' => 'workflow_type_test',
    ]);
    $workflow->save();

    $recipe = $this->createRecipe($workflow->getConfigDependencyName());
    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage("The add_moderation:addNodeTypes config action only works with Content Moderation workflows.");
    RecipeRunner::processRecipe($recipe);
  }

  public function testActionOnlyTargetsWorkflows(): void {
    $recipe = $this->createRecipe('user.role.anonymous');
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage('The "user_role" entity does not support the "addNodeTypes" config action.');
    RecipeRunner::processRecipe($recipe);
  }

  public function testDeriverAdminLabel(): void {
    $this->enableModules(['workflows', 'content_moderation']);

    /** @var array<string, array{admin_label: \Stringable}> $definitions */
    $definitions = $this->container->get('plugin.manager.config_action')
      ->getDefinitions();

    $this->assertSame('Add moderation to all content types', (string) $definitions['add_moderation:addNodeTypes']['admin_label']);
    $this->assertSame('Add moderation to all vocabularies', (string) $definitions['add_moderation:addTaxonomyVocabularies']['admin_label']);
  }

  private function createRecipe(string $config_name): Recipe {
    $recipe = <<<YAML
name: 'Add entity types and bundles to workflow'
recipes:
  - core/recipes/editorial_workflow
config:
  actions:
    $config_name:
      addNodeTypes:
        - a
        - b
      addTaxonomyVocabularies: '*'
YAML;
    return $this->traitCreateRecipe($recipe);
  }

}
