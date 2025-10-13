<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel;

use Drupal\Core\Config\Action\ConfigActionManager;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodePreviewMode;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Config Actions.
 */
#[Group('node')]
#[RunTestsInSeparateProcesses]
class ConfigActionsTest extends KernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'node', 'system', 'text', 'user'];

  /**
   * The configuration action manager.
   */
  private readonly ConfigActionManager $configActionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig('node');
    $this->configActionManager = $this->container->get('plugin.manager.config_action');
  }

  /**
   * Tests the application of configuration actions on a node type.
   */
  public function testConfigActions(): void {
    $node_type = $this->createContentType();

    $this->assertTrue($node_type->shouldCreateNewRevision());
    $this->assertSame(NodePreviewMode::Optional, $node_type->getPreviewMode(FALSE));
    $this->assertTrue($node_type->displaySubmitted());

    $this->configActionManager->applyAction(
      'entity_method:node.type:setNewRevision',
      $node_type->getConfigDependencyName(),
      FALSE,
    );
    $this->configActionManager->applyAction(
      'entity_method:node.type:setPreviewMode',
      $node_type->getConfigDependencyName(),
      NodePreviewMode::Required,
    );
    $this->configActionManager->applyAction(
      'entity_method:node.type:setDisplaySubmitted',
      $node_type->getConfigDependencyName(),
      FALSE,
    );

    $node_type = NodeType::load($node_type->id());
    $this->assertFalse($node_type->shouldCreateNewRevision());
    $this->assertSame(NodePreviewMode::Required, $node_type->getPreviewMode(FALSE));
    $this->assertFalse($node_type->displaySubmitted());
  }

}
