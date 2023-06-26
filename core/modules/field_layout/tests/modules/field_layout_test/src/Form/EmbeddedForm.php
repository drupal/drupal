<?php

namespace Drupal\field_layout_test\Form;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field_layout\FieldLayoutBuilder;

/**
 * Provides the EmbeddedForm class.
 *
 * @package Drupal\field_layout_test\Form
 */
class EmbeddedForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_layout_test_embedded_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['foo'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Wrapper'),
      '#tree' => TRUE,
      '#parents' => ['foo'],
    ];
    $entity = EntityTest::load(1);
    if ($entity) {
      if ($entity) {
        $display = EntityFormDisplay::collectRenderDisplay($entity, 'default');
        $subform_state = SubformState::createForSubform($form['foo'], $form, $form_state);
        $display->buildForm($entity, $form['foo'], $subform_state);
        \Drupal::classResolver(FieldLayoutBuilder::class)->buildForm($form['foo'], $display, $subform_state);
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

}
