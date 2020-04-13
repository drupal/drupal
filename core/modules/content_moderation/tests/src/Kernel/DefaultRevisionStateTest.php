<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Tests the correct default revision is set.
 *
 * @group content_moderation
 */
class DefaultRevisionStateTest extends KernelTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
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
  protected function setUp(): void {
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

    $workflow = $this->createEditorialWorkflow();
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
    $this->assertModerationState($english_node->getRevisionId(), $english_node->language()->getId(), 'draft');

    // Revision 2 (fr)
    $french_node = $english_node->addTranslation('fr', ['title' => 'French title']);
    $french_node->moderation_state->value = 'published';
    $french_node->save();
    $this->assertTrue($french_node->isPublished());
    $this->assertTrue($french_node->isDefaultRevision());
    $this->assertModerationState($french_node->getRevisionId(), $french_node->language()->getId(), 'published');

    // Revision 3 (fr)
    $node = Node::load($english_node->id())->getTranslation('fr');
    $node->moderation_state->value = 'draft';
    $node->save();
    $this->assertFalse($node->isPublished());
    $this->assertFalse($node->isDefaultRevision());
    $this->assertModerationState($node->getRevisionId(), $node->language()->getId(), 'draft');

    // Revision 4 (en)
    $latest_revision = $this->entityTypeManager->getStorage('node')->loadRevision(3);
    $latest_revision->moderation_state->value = 'draft';
    $latest_revision->save();
    $this->assertFalse($latest_revision->isPublished());
    $this->assertFalse($latest_revision->isDefaultRevision());
    $this->assertModerationState($latest_revision->getRevisionId(), $latest_revision->language()->getId(), 'draft');
  }

  /**
   * Verifies the expected moderation state revision exists.
   *
   * @param int $revision_id
   *   The revision ID of the host entity.
   * @param string $langcode
   *   The language code of the host entity to check.
   * @param string $expected_state
   *   The state the content moderation state revision should be in.
   * @param string $expected_workflow
   *   The workflow the content moderation state revision should be using.
   */
  protected function assertModerationState($revision_id, $langcode, $expected_state, $expected_workflow = 'editorial') {
    $moderation_state_storage = $this->entityTypeManager->getStorage('content_moderation_state');

    $query = $moderation_state_storage->getQuery();
    $results = $query->allRevisions()
      ->condition('content_entity_revision_id', $revision_id)
      ->condition('langcode', $langcode)
      ->execute();
    $this->assertCount(1, $results);

    $moderation_state = $moderation_state_storage
      ->loadRevision(key($results))
      ->getTranslation($langcode);
    $this->assertEquals($expected_state, $moderation_state->get('moderation_state')->value);
    $this->assertEquals($expected_workflow, $moderation_state->get('workflow')->target_id);
  }

}
