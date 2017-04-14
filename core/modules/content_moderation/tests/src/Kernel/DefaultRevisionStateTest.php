<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests the correct default revision is set.
 *
 * @group content_moderation
 */
class DefaultRevisionStateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'node',
    'block_content',
    'content_moderation',
    'user',
    'system',
    'language',
    'content_translation',
    'text',
    'workflows',
  ];

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_with_bundle');
    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('entity_test_mulrevpub');
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig('content_moderation');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests a translatable Node.
   */
  public function testMultilingual() {
    // Enable French.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->save();

    $this->container->get('content_translation.manager')->setEnabled('node', 'example', TRUE);

    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $workflow->save();

    $english_node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    // Revision 1 (en).
    $english_node
      ->setUnpublished()
      ->save();
    $this->assertEquals('draft', $english_node->moderation_state->value);
    $this->assertFalse($english_node->isPublished());
    $this->assertTrue($english_node->isDefaultRevision());

    // Revision 2 (fr)
    $french_node = $english_node->addTranslation('fr', ['title' => 'French title']);
    $french_node->moderation_state->value = 'published';
    $french_node->save();
    $this->assertTrue($french_node->isPublished());
    $this->assertTrue($french_node->isDefaultRevision());

    // Revision 3 (fr)
    $node = Node::load($english_node->id())->getTranslation('fr');
    $node->moderation_state->value = 'draft';
    $node->save();
    $this->assertFalse($node->isPublished());
    $this->assertFalse($node->isDefaultRevision());

    // Revision 4 (en)
    $latest_revision = $this->entityTypeManager->getStorage('node')->loadRevision(3);
    $latest_revision->moderation_state->value = 'draft';
    $latest_revision->save();
    $this->assertFalse($latest_revision->isPublished());
    $this->assertFalse($latest_revision->isDefaultRevision());
  }

}
