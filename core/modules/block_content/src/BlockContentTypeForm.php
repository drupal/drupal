<?php

/**
 * @file
 * Contains \Drupal\block_content\BlockContentTypeForm.
 */

namespace Drupal\block_content;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form for category edit forms.
 */
class BlockContentTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $block_type = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $block_type->label(),
      '#description' => t("Provide a label for this block type to help identify it in the administration pages."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $block_type->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\block_content\Entity\BlockContentType::load',
      ),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$block_type->isNew(),
    );

    $form['description'] = array(
      '#type' => 'textarea',
      '#default_value' => $block_type->description,
      '#description' => t('Enter a description for this block type.'),
      '#title' => t('Description'),
    );

    $form['revision'] = array(
      '#type' => 'checkbox',
      '#title' => t('Create new revision'),
      '#default_value' => $block_type->revision,
      '#description' => t('Create a new revision by default for this block type.')
    );

    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = array(
        '#type' => 'details',
        '#title' => t('Language settings'),
        '#group' => 'additional_settings',
      );

      $language_configuration = language_get_default_configuration('block_content', $block_type->id());
      $form['language']['language_configuration'] = array(
        '#type' => 'language_configuration',
        '#entity_information' => array(
          'entity_type' => 'block_content',
          'bundle' => $block_type->id(),
        ),
        '#default_value' => $language_configuration,
      );

      $form['#submit'][] = 'language_configuration_element_submit';
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $block_type = $this->entity;
    $status = $block_type->save();

    $edit_link = \Drupal::linkGenerator()->generateFromUrl($this->t('Edit'), $this->entity->urlInfo());
    $logger = $this->logger('block_content');
    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('Custom block type %label has been updated.', array('%label' => $block_type->label())));
      $logger->notice('Custom block type %label has been updated.', array('%label' => $block_type->label(), 'link' => $edit_link));
    }
    else {
      drupal_set_message(t('Custom block type %label has been added.', array('%label' => $block_type->label())));
      $logger->notice('Custom block type %label has been added.', array('%label' => $block_type->label(), 'link' => $edit_link));
    }

    $form_state->setRedirect('block_content.type_list');
  }

}
