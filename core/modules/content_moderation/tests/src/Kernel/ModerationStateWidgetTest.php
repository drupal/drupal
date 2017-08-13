<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\workflows\Entity\Workflow;

/**
 * @coversDefaultClass \Drupal\content_moderation\Plugin\Field\FieldWidget\ModerationStateWidget
 * @group content_moderation
 */
class ModerationStateWidgetTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'workflows',
    'content_moderation',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('user');
    $this->installConfig(['content_moderation', 'system']);

    NodeType::create([
      'type' => 'moderated',
    ])->save();
    NodeType::create([
      'type' => 'unmoderated',
    ])->save();

    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'moderated');
    $workflow->save();
  }

  /**
   * Test the widget does not impact a non-moderated entity.
   */
  public function testWidgetNonModeratedEntity() {
    // Create an unmoderated entity and build a form display which will include
    // the ModerationStateWidget plugin, in a hidden state.
    $entity = Node::create([
      'type' => 'unmoderated',
    ]);
    $entity_form_display = EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'unmoderated',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $form = [];
    $form_state = new FormState();
    $entity_form_display->buildForm($entity, $form, $form_state);

    // The moderation_state field should have no values for an entity that isn't
    // being moderated.
    $entity_form_display->extractFormValues($entity, $form, $form_state);
    $this->assertEquals(0, $entity->moderation_state->count());
  }

}
