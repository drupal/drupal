<?php

namespace Drupal\Tests\node\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * Tests the node view builder.
 *
 * @group node
 *
 * @coversDefaultClass \Drupal\node\NodeViewBuilder
 */
class NodeViewBuilderTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node'];

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $storage;

  /**
   * The node view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->storage = $this->entityTypeManager->getStorage('node');
    $this->viewBuilder = $this->entityTypeManager->getViewBuilder('node');
    $this->renderer = $this->container->get('renderer');

    $type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $type->save();

    $this->installSchema('node', 'node_access');
    $this->installConfig(['system', 'node']);
  }

  /**
   * Tests that node links are displayed correctly in pending revisions.
   *
   * @covers ::buildComponents
   * @covers ::renderLinks
   * @covers ::buildLinks
   */
  public function testPendingRevisionLinks() {
    $account = User::create([
      'name' => $this->randomString(),
    ]);
    $account->save();

    $title = $this->randomMachineName();
    $node = Node::create([
      'type' => 'article',
      'title' => $title,
      'uid' => $account->id(),
    ]);
    $node->save();

    /** @var \Drupal\node\NodeInterface $pending_revision */
    $pending_revision = $this->storage->createRevision($node, FALSE);
    $draft_title = $title . ' draft';
    $pending_revision->setTitle($draft_title);
    $pending_revision->save();

    $build = $this->viewBuilder->view($node, 'teaser');
    $output = (string) $this->renderer->renderPlain($build);
    $this->assertStringContainsString("title=\"$title\"", $output);

    $build = $this->viewBuilder->view($pending_revision, 'teaser');
    $output = (string) $this->renderer->renderPlain($build);
    $this->assertStringContainsString("title=\"$draft_title\"", $output);
  }

}
