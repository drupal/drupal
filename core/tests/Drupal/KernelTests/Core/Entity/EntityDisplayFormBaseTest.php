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
   * @covers ::copyFormValuesToEntity
   */
  public function testCopyFormValuesToEntity() {
    $field_values = [];
    $entity = $this->prophesize(EntityDisplayInterface::class);
    $entity->getPluginCollections()->willReturn([]);

    // A field with no initial values, with mismatched submitted values, type is
    // hidden.
    $entity->getComponent('new_field_mismatch_type_hidden')->willReturn([]);
    $field_values['new_field_mismatch_type_hidden'] = [
      'weight' => 0,
      'type' => 'hidden',
      'region' => 'content',
    ];
    $entity
      ->setComponent('new_field_mismatch_type_hidden', [
        'weight' => 0,
        'region' => 'content',
      ])
      ->will(function($args) {
        // On subsequent calls, getComponent() will return the newly set values,
        // plus the updated type value.
        $args[1] += ['type' => 'textfield'];
        $this->getComponent($args[0])->willReturn($args[1]);
        $this->setComponent($args[0], $args[1])->shouldBeCalled();
      })
      ->shouldBeCalled();

    // A field with no initial values, with mismatched submitted values, type is
    // visible.
    $entity->getComponent('new_field_mismatch_type_visible')->willReturn([]);
    $field_values['new_field_mismatch_type_visible'] = [
      'weight' => 0,
      'type' => 'textfield',
      'region' => 'hidden',
    ];
    $entity
      ->setComponent('new_field_mismatch_type_visible', [
        'weight' => 0,
        'type' => 'textfield',
      ])
      ->will(function($args) {
        // On subsequent calls, getComponent() will return the newly set values,
        // plus the updated region value.
        $args[1] += ['region' => 'content'];
        $this->getComponent($args[0])->willReturn($args[1]);
        $this->setComponent($args[0], $args[1])->shouldBeCalled();
      })
      ->shouldBeCalled();

    // An initially hidden field, with identical submitted values.
    $entity->getComponent('field_hidden_no_changes')
      ->willReturn([
        'weight' => 0,
        'type' => 'hidden',
        'region' => 'hidden',
      ]);
    $field_values['field_hidden_no_changes'] = [
      'weight' => 0,
      'type' => 'hidden',
      'region' => 'hidden',
    ];
    $entity->removeComponent('field_hidden_no_changes')
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

    // An initially hidden field, with a submitted type change.
    $entity->getComponent('field_start_hidden_change_type')
      ->willReturn([
        'weight' => 0,
        'type' => 'hidden',
        'region' => 'hidden',
      ]);
    $field_values['field_start_hidden_change_type'] = [
      'weight' => 0,
      'type' => 'textfield',
      'region' => 'hidden',
    ];
    $entity
      ->setComponent('field_start_hidden_change_type', [
        'weight' => 0,
        'type' => 'textfield',
      ])
      ->will(function($args) {
        // On subsequent calls, getComponent() will return the newly set values,
        // plus the updated region value.
        $args[1] += ['region' => 'content'];
        $this->getComponent($args[0])->willReturn($args[1]);
        $this->setComponent($args[0], $args[1])->shouldBeCalled();
      })
      ->shouldBeCalled();

    // An initially hidden field, with a submitted region change.
    $entity->getComponent('field_start_hidden_change_region')
      ->willReturn([
        'weight' => 0,
        'type' => 'hidden',
        'region' => 'hidden',
      ]);
    $field_values['field_start_hidden_change_region'] = [
      'weight' => 0,
      'type' => 'hidden',
      'region' => 'content',
    ];
    $entity
      ->setComponent('field_start_hidden_change_region', [
        'weight' => 0,
        'region' => 'content',
      ])
      ->will(function($args) {
        // On subsequent calls, getComponent() will return the newly set values,
        // plus the updated type value.
        $args[1] += ['type' => 'textfield'];
        $this->getComponent($args[0])->willReturn($args[1]);
        $this->setComponent($args[0], $args[1])->shouldBeCalled();
      })
      ->shouldBeCalled();

    // An initially hidden field, with a submitted region and type change.
    $entity->getComponent('field_start_hidden_change_both')
      ->willReturn([
        'weight' => 0,
        'type' => 'hidden',
        'region' => 'hidden',
      ]);
    $field_values['field_start_hidden_change_both'] = [
      'weight' => 0,
      'type' => 'textfield',
      'region' => 'content',
    ];
    $entity
      ->setComponent('field_start_hidden_change_both', [
        'weight' => 0,
        'type' => 'textfield',
        'region' => 'content',
      ])
      ->will(function($args) {
        // On subsequent calls, getComponent() will return the newly set values.
        $this->getComponent($args[0])->willReturn($args[1]);
      })
      ->shouldBeCalled();

    // An initially visible field, with a submitted type change.
    $entity->getComponent('field_start_visible_change_type')
      ->willReturn([
        'weight' => 0,
        'type' => 'textfield',
        'region' => 'content',
      ]);
    $field_values['field_start_visible_change_type'] = [
      'weight' => 0,
      'type' => 'hidden',
      'region' => 'content',
    ];
    $entity->removeComponent('field_start_visible_change_type')
      ->will(function ($args) {
        // On subsequent calls, getComponent() will return an empty array.
        $this->getComponent($args[0])->willReturn([]);
      })
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

    // An initially visible field, with a submitted region and type change.
    $entity->getComponent('field_start_visible_change_both')
      ->willReturn([
        'weight' => 0,
        'type' => 'textfield',
        'region' => 'content',
      ]);
    $field_values['field_start_visible_change_both'] = [
      'weight' => 0,
      'type' => 'hidden',
      'region' => 'hidden',
    ];
    $entity->removeComponent('field_start_visible_change_both')
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
    $form_object->setEntity($entity->reveal());

    $form = [
      '#fields' => array_keys($field_values),
      '#extra' => [],
    ];
    $form_state = new FormState();
    $form_state->setValues(['fields' => $field_values]);

    $form_object->buildEntity($form, $form_state);

    // Flag one field for updating plugin settings.
    $form_state->set('plugin_settings_update', 'field_plugin_settings_update');
    // During form submission, buildEntity() will be called twice. Simulate that
    // here to prove copyFormValuesToEntity() is idempotent.
    $form_object->buildEntity($form, $form_state);
  }

}
