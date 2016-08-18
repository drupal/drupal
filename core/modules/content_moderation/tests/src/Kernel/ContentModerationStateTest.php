<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\content_moderation\Entity\ModerationState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;

/**
 * Tests links between a content entity and a content_moderation_state entity.
 *
 * @group content_moderation
 */
class ContentModerationStateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'content_moderation',
    'user',
    'system',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig('content_moderation');
  }

  /**
   * Tests basic monolingual content moderation through the API.
   */
  public function testBasicModeration() {
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->setThirdPartySetting('content_moderation', 'enabled', TRUE);
    $node_type->setThirdPartySetting('content_moderation', 'allowed_moderation_states', ['draft', 'published']);
    $node_type->setThirdPartySetting('content_moderation', 'default_moderation_state', 'draft');
    $node_type->save();
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    $node->save();
    $node = $this->reloadNode($node);
    $this->assertEquals('draft', $node->moderation_state->entity->id());

    $published = ModerationState::load('published');
    $node->moderation_state->entity = $published;
    $node->save();

    $node = $this->reloadNode($node);
    $this->assertEquals('published', $node->moderation_state->entity->id());

    // Change the state without saving the node.
    $content_moderation_state = ContentModerationState::load(1);
    $content_moderation_state->set('moderation_state', 'draft');
    $content_moderation_state->setNewRevision(TRUE);
    $content_moderation_state->save();

    $node = $this->reloadNode($node, 3);
    $this->assertEquals('draft', $node->moderation_state->entity->id());
    $this->assertFalse($node->isPublished());

    // Get the default revision.
    $node = $this->reloadNode($node);
    $this->assertTrue($node->isPublished());
    $this->assertEquals(2, $node->getRevisionId());

    $node->moderation_state->target_id = 'published';
    $node->save();

    $node = $this->reloadNode($node, 4);
    $this->assertEquals('published', $node->moderation_state->entity->id());

    // Get the default revision.
    $node = $this->reloadNode($node);
    $this->assertTrue($node->isPublished());
    $this->assertEquals(4, $node->getRevisionId());

  }

  /**
   * Tests basic multilingual content moderation through the API.
   */
  public function testMultilingualModeration() {
    // Enable French.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $node_type = NodeType::create([
      'type' => 'example',
    ]);
    $node_type->setThirdPartySetting('content_moderation', 'enabled', TRUE);
    $node_type->setThirdPartySetting('content_moderation', 'allowed_moderation_states', ['draft', 'published']);
    $node_type->setThirdPartySetting('content_moderation', 'default_moderation_state', 'draft');
    $node_type->save();
    $english_node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
    ]);
    // Revision 1 (en).
    $english_node
      ->setPublished(FALSE)
      ->save();
    $this->assertEquals('draft', $english_node->moderation_state->entity->id());
    $this->assertFalse($english_node->isPublished());

    // Create a French translation.
    $french_node = $english_node->addTranslation('fr', ['title' => 'French title']);
    $french_node->setPublished(FALSE);
    // Revision 1 (fr).
    $french_node->save();
    $french_node = $this->reloadNode($english_node)->getTranslation('fr');
    $this->assertEquals('draft', $french_node->moderation_state->entity->id());
    $this->assertFalse($french_node->isPublished());

    // Move English node to create another draft.
    $english_node = $this->reloadNode($english_node);
    $english_node->moderation_state->target_id = 'draft';
    // Revision 2 (en, fr).
    $english_node->save();
    $english_node = $this->reloadNode($english_node);
    $this->assertEquals('draft', $english_node->moderation_state->entity->id());

    // French node should still be in draft.
    $french_node = $this->reloadNode($english_node)->getTranslation('fr');
    $this->assertEquals('draft', $french_node->moderation_state->entity->id());

    // Publish the French node.
    $french_node->moderation_state->target_id = 'published';
    // Revision 3 (en, fr).
    $french_node->save();
    $french_node = $this->reloadNode($french_node)->getTranslation('fr');
    $this->assertTrue($french_node->isPublished());
    $this->assertEquals('published', $french_node->moderation_state->entity->id());
    $this->assertTrue($french_node->isPublished());
    $english_node = $french_node->getTranslation('en');
    $this->assertEquals('draft', $english_node->moderation_state->entity->id());

    // Publish the English node.
    $english_node->moderation_state->target_id = 'published';
    // Revision 4 (en, fr).
    $english_node->save();
    $english_node = $this->reloadNode($english_node);
    $this->assertTrue($english_node->isPublished());

    // Move the French node back to draft.
    $french_node = $this->reloadNode($english_node)->getTranslation('fr');
    $this->assertTrue($french_node->isPublished());
    $french_node->moderation_state->target_id = 'draft';
    // Revision 5 (en, fr).
    $french_node->save();
    $french_node = $this->reloadNode($english_node, 5)->getTranslation('fr');
    $this->assertFalse($french_node->isPublished());
    $this->assertTrue($french_node->getTranslation('en')->isPublished());

    // Republish the French node.
    $french_node->moderation_state->target_id = 'published';
    // Revision 6 (en, fr).
    $french_node->save();
    $french_node = $this->reloadNode($english_node)->getTranslation('fr');
    $this->assertTrue($french_node->isPublished());

    // Change the EN state without saving the node.
    $content_moderation_state = ContentModerationState::load(1);
    $content_moderation_state->set('moderation_state', 'draft');
    $content_moderation_state->setNewRevision(TRUE);
    // Revision 7 (en, fr).
    $content_moderation_state->save();
    $english_node = $this->reloadNode($french_node, $french_node->getRevisionId() + 1);

    $this->assertEquals('draft', $english_node->moderation_state->entity->id());
    $french_node = $this->reloadNode($english_node)->getTranslation('fr');
    $this->assertEquals('published', $french_node->moderation_state->entity->id());

    // This should unpublish the French node.
    $content_moderation_state = ContentModerationState::load(1);
    $content_moderation_state = $content_moderation_state->getTranslation('fr');
    $content_moderation_state->set('moderation_state', 'draft');
    $content_moderation_state->setNewRevision(TRUE);
    // Revision 8 (en, fr).
    $content_moderation_state->save();

    $english_node = $this->reloadNode($english_node, $english_node->getRevisionId());
    $this->assertEquals('draft', $english_node->moderation_state->entity->id());
    $french_node = $this->reloadNode($english_node, '8')->getTranslation('fr');
    $this->assertEquals('draft', $french_node->moderation_state->entity->id());
    // Switching the moderation state to an unpublished state should update the
    // entity.
    $this->assertFalse($french_node->isPublished());

    // Get the default english node.
    $english_node = $this->reloadNode($english_node);
    $this->assertTrue($english_node->isPublished());
    $this->assertEquals(6, $english_node->getRevisionId());
  }

  /**
   * Reloads the node after clearing the static cache.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to reload.
   * @param int|false $revision_id
   *   The specific revision ID to load. Defaults FALSE and just loads the
   *   default revision.
   *
   * @return \Drupal\node\NodeInterface
   *   The reloaded node.
   */
  protected function reloadNode(NodeInterface $node, $revision_id = FALSE) {
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $storage->resetCache([$node->id()]);
    if ($revision_id) {
      return $storage->loadRevision($revision_id);
    }
    return $storage->load($node->id());
  }

}
