<?php

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\content_moderation\Plugin\Field\FieldWidget\ModerationStateWidget;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * @coversDefaultClass \Drupal\content_moderation\Plugin\Field\FieldWidget\ModerationStateWidget
 * @group content_moderation
 */
class ModerationStateWidgetTest extends KernelTestBase {

  use ContentModerationTestTrait;

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

    $workflow = $this->createEditorialWorkflow();
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

  /**
   * @covers ::isApplicable
   */
  public function testIsApplicable() {
    // The moderation_state field definition should be applicable to our widget.
    $fields = $this->container->get('entity_field.manager')->getFieldDefinitions('node', 'test_type');
    $this->assertTrue(ModerationStateWidget::isApplicable($fields['moderation_state']));
    $this->assertFalse(ModerationStateWidget::isApplicable($fields['status']));
    // A config override should still be applicable.
    $field_config = $fields['moderation_state']->getConfig('moderated');
    $this->assertTrue(ModerationStateWidget::isApplicable($field_config));
  }

}
