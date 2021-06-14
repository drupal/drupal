<?php

namespace Drupal\Tests\quickedit\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests loading of in-place editing functionality and lazy loading of its
 * in-place editors.
 *
 * @group quickedit
 */
class QuickEditLoadingTest extends KernelTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'field',
    'node',
    'text',
    'filter',
    'contextual',
    'quickedit',
  ];

  /**
   * A user with permissions to access in-place editor.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editorUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['field', 'filter', 'node']);

    // Create a Content type and one test node.
    $this->createContentType(['type' => 'page']);
    $this->createNode();

    $this->editorUser = $this->createUser([
      'access content',
      'create page content',
      'edit any page content',
      'access contextual links',
      'access in-place editing',
    ]);
  }

  /**
   * Tests that Quick Edit doesn't make fields rendered with display options
   * editable.
   */
  public function testDisplayOptions() {
    $node = Node::load(1);
    $renderer = $this->container->get('renderer');
    $this->container->get('current_user')->setAccount($this->editorUser);

    $build = $node->body->view(['label' => 'inline']);
    $this->setRawContent($renderer->renderRoot($build));
    $elements = $this->xpath('//div[@data-quickedit-field-id]');
    $this->assertFalse(!empty($elements), 'data-quickedit-field-id attribute not added when rendering field using dynamic display options.');

    $build = $node->body->view('default');
    $this->setRawContent($renderer->renderRoot($build));
    $elements = $this->xpath('//div[@data-quickedit-field-id="node/1/body/en/default"]');
    $this->assertTrue(!empty($elements), 'Body with data-quickedit-field-id attribute found.');
  }

}
