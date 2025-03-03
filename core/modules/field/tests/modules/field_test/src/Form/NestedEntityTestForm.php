<?php

declare(strict_types=1);

namespace Drupal\field_test\Form;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Provides a form for field_test routes.
 *
 * @internal
 */
class NestedEntityTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_test_entity_nested_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?EntityInterface $entity_1 = NULL, ?EntityInterface $entity_2 = NULL) {
    // First entity.
    $form_state->set('entity_1', $entity_1);
    $form_display_1 = EntityFormDisplay::collectRenderDisplay($entity_1, 'default');
    $form_state->set('form_display_1', $form_display_1);
    $form_display_1->buildForm($entity_1, $form, $form_state);

    // Second entity.
    $form_state->set('entity_2', $entity_2);
    $form_display_2 = EntityFormDisplay::collectRenderDisplay($entity_2, 'default');
    $form_state->set('form_display_2', $form_display_2);
    $form['entity_2'] = [
      '#type' => 'details',
      '#title' => $this->t('Second entity'),
      '#tree' => TRUE,
      '#parents' => ['entity_2'],
      '#weight' => 50,
      '#attributes' => ['class' => ['entity-2']],
    ];

    $form_display_2->buildForm($entity_2, $form['entity_2'], $form_state);

    if ($entity_2 instanceof EntityChangedInterface) {
      // Changed must be sent to the client, for later overwrite error checking.
      // @see \Drupal\Tests\field\Functional\NestedFormTest::testNestedEntityFormEntityLevelValidation()
      $form['entity_2']['changed'] = [
        '#type' => 'hidden',
        '#default_value' => $entity_1->getChangedTime(),
      ];
    }

    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#weight' => 100,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity_1 = $form_state->get('entity_1');
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display_1 */
    $form_display_1 = $form_state->get('form_display_1');
    $form_display_1->extractFormValues($entity_1, $form, $form_state);
    $form_display_1->validateFormValues($entity_1, $form, $form_state);

    $entity_2 = $form_state->get('entity_2');
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display_2 */
    $form_display_2 = $form_state->get('form_display_2');
    $extracted = $form_display_2->extractFormValues($entity_2, $form['entity_2'], $form_state);
    // Extract the values of fields that are not rendered through widgets, by
    // simply copying from top-level form values. This leaves the fields that
    // are not being edited within this form untouched.
    // @see \Drupal\Tests\field\Functional\NestedFormTest::testNestedEntityFormEntityLevelValidation()
    foreach ($form_state->getValues()['entity_2'] as $name => $values) {
      if ($entity_2->hasField($name) && !isset($extracted[$name])) {
        $entity_2->set($name, $values);
      }
    }
    $form_display_2->validateFormValues($entity_2, $form['entity_2'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity_1 */
    $entity_1 = $form_state->get('entity_1');
    $entity_1->save();

    /** @var \Drupal\Core\Entity\EntityInterface $entity_2 */
    $entity_2 = $form_state->get('entity_2');
    $entity_2->save();

    $this->messenger()
      ->addStatus($this->t('test_entities @id_1 and @id_2 have been updated.', [
        '@id_1' => $entity_1->id(),
        '@id_2' => $entity_2->id(),
      ]));
  }

}
