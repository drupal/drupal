<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Form\FormState;
use Drupal\field_ui\Form\EntityViewDisplayEditForm;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\field_ui\Form\EntityDisplayFormBase
 *
 * @group Entity
 */
class EntityDisplayFormBaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test'];

  /**
   * @covers ::copyFormValuesToEntity
   */
  public function testCopyFormValuesToEntity() {
    $field_values = [];
    $entity = $this->prophesize(EntityDisplayInterface::class);
    $entity->getPluginCollections()->willReturn([]);
    $entity->getTargetEntityTypeId()->willReturn('entity_test_with_bundle');
    $entity->getTargetBundle()->willReturn('target_bundle');

    // An initially hidden field, with a submitted region change.
    $entity->getComponent('new_field_mismatch_type_visible')->willReturn([]);
    $field_values['new_field_mismatch_type_visible'] = [
      'weight' => 0,
      'type' => 'textfield',
      'region' => 'hidden',
    ];
    $entity->removeComponent('new_field_mismatch_type_visible')
      ->will(function ($args) {
        // On subsequent calls, getComponent() will return an empty array.
        $this->getComponent($args[0])->willReturn([]);
      })
      ->shouldBeCalled();

    // An initially visible field, with identical submitted values.
    $entity->getComponent('field_visible_no_changes')
      ->willReturn([
        'weight' => 0,
        'type' => 'textfield',
        'region' => 'content',
      ]);
    $field_values['field_visible_no_changes'] = [
      'weight' => 0,
      'type' => 'textfield',
      'region' => 'content',
    ];
    $entity
      ->setComponent('field_visible_no_changes', [
        'weight' => 0,
        'type' => 'textfield',
        'region' => 'content',
      ])
      ->shouldBeCalled();

    // An initially visible field, with a submitted region change.
    $entity->getComponent('field_start_visible_change_region')
      ->willReturn([
        'weight' => 0,
        'type' => 'textfield',
        'region' => 'content',
      ]);
    $field_values['field_start_visible_change_region'] = [
      'weight' => 0,
      'type' => 'textfield',
      'region' => 'hidden',
    ];
    $entity->removeComponent('field_start_visible_change_region')
      ->will(function ($args) {
        // On subsequent calls, getComponent() will return an empty array.
        $this->getComponent($args[0])->willReturn([]);
      })
      ->shouldBeCalled();

    // A field that is flagged for plugin settings update on the second build.
    $entity->getComponent('field_plugin_settings_update')
      ->willReturn([
        'weight' => 0,
        'type' => 'textfield',
        'region' => 'content',
      ]);
    $field_values['field_plugin_settings_update'] = [
      'weight' => 0,
      'type' => 'textfield',
      'region' => 'content',
      'settings_edit_form' => [
        'third_party_settings' => [
          'foo' => 'bar',
        ],
      ],
    ];
    $entity
      ->setComponent('field_plugin_settings_update', [
        'weight' => 0,
        'type' => 'textfield',
        'region' => 'content',
      ])
      ->will(function ($args) {
        // On subsequent calls, getComponent() will return the newly set values.
        $this->getComponent($args[0])->willReturn($args[1]);
        $args[1] += [
          'settings' => [],
          'third_party_settings' => [
            'foo' => 'bar',
          ],
        ];
        $this->setComponent($args[0], $args[1])->shouldBeCalled();
      })
      ->shouldBeCalled();

    $form_object = new EntityViewDisplayEditForm($this->container->get('plugin.manager.field.field_type'), $this->container->get('plugin.manager.field.formatter'));
    $form_object->setEntityManager($this->container->get('entity.manager'));
    $form_object->setEntity($entity->reveal());

    $form = [
      '#fields' => array_keys($field_values),
      '#extra' => [],
    ];
    $form_state = new FormState();
    $form_state->setValues(['fields' => $field_values]);
    $form_state->setProcessInput();

    $form_object->buildEntity($form, $form_state);
    $form_state->setSubmitted();

    // Flag one field for updating plugin settings.
    $form_state->set('plugin_settings_update', 'field_plugin_settings_update');
    // During form submission, buildEntity() will be called twice. Simulate that
    // here to prove copyFormValuesToEntity() is idempotent.
    $form_object->buildEntity($form, $form_state);
  }

}
