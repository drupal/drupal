<?php

/**
 * @file
 * Contains \Drupal\custom_block\CustomBlockFormController.
 */

namespace Drupal\custom_block;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormControllerNG;
use Drupal\Core\Language\Language;

/**
 * Form controller for the custom block edit forms.
 */
class CustomBlockFormController extends EntityFormControllerNG {

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::prepareEntity().
   *
   * Prepares the custom block object.
   *
   * Fills in a few default values, and then invokes hook_custom_block_prepare()
   * on all modules.
   */
  protected function prepareEntity() {
    $block = $this->entity;
    // Set up default values, if required.
    $block_type = entity_load('custom_block_type', $block->type->value);
    // If this is a new custom block, fill in the default values.
    if (isset($block->id->value)) {
      $block->set('log', NULL);
    }
    // Always use the default revision setting.
    $block->setNewRevision($block_type->revision);
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $block = $this->entity;

    if ($this->operation == 'edit') {
      // @todo Remove this once https://drupal.org/node/1981644 is in.
      drupal_set_title(t('Edit custom block %label', array('%label' => $block->label())), PASS_THROUGH);
    }
    // Override the default CSS class name, since the user-defined custom block
    // type name in 'TYPE-block-form' potentially clashes with third-party class
    // names.
    $form['#attributes']['class'][0] = drupal_html_class('block-' . $block->type->value . '-form');

    // Basic block information.
    // These elements are just values so they are not even sent to the client.
    foreach (array('revision_id', 'id') as $key) {
      $form[$key] = array(
        '#type' => 'value',
        '#value' => $block->$key->value,
      );
    }

    $form['info'] = array(
      '#type' => 'textfield',
      '#title' => t('Block description'),
      '#required' => TRUE,
      '#default_value' => $block->info->value,
      '#weight' => -5,
      '#description' => t('A brief description of your block. Used on the <a href="@overview">Blocks administration page</a>.', array('@overview' => url('admin/structure/block'))),
    );

    $language_configuration = module_invoke('language', 'get_default_configuration', 'custom_block', $block->type->value);

    // Set the correct default language.
    if ($block->isNew() && !empty($language_configuration['langcode'])) {
      $language_default = language($language_configuration['langcode']);
      $block->langcode->value = $language_default->id;
    }

    $form['langcode'] = array(
      '#title' => t('Language'),
      '#type' => 'language_select',
      '#default_value' => $block->langcode->value,
      '#languages' => Language::STATE_ALL,
      '#access' => isset($language_configuration['language_show']) && $language_configuration['language_show'],
    );

    $form['advanced'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    );

    // Add a log field if the "Create new revision" option is checked, or if the
    // current user has the ability to check that option.
    $form['revision_information'] = array(
      '#type' => 'details',
      '#title' => t('Revision information'),
      '#collapsible' => TRUE,
      // Collapsed by default when "Create new revision" is unchecked.
      '#collapsed' => !$block->isNewRevision(),
      '#group' => 'advanced',
      '#attributes' => array(
        'class' => array('custom-block-form-revision-information'),
      ),
      '#attached' => array(
        'js' => array(drupal_get_path('module', 'custom_block') . '/custom_block.js'),
      ),
      '#weight' => 20,
      '#access' => $block->isNewRevision() || user_access('administer blocks'),
    );

    $form['revision_information']['revision'] = array(
      '#type' => 'checkbox',
      '#title' => t('Create new revision'),
      '#default_value' => $block->isNewRevision(),
      '#access' => user_access('administer blocks'),
    );

    // Check the revision log checkbox when the log textarea is filled in.
    // This must not happen if "Create new revision" is enabled by default,
    // since the state would auto-disable the checkbox otherwise.
    if (!$block->isNewRevision()) {
      $form['revision_information']['revision']['#states'] = array(
        'checked' => array(
          'textarea[name="log"]' => array('empty' => FALSE),
        ),
      );
    }

    $form['revision_information']['log'] = array(
      '#type' => 'textarea',
      '#title' => t('Revision log message'),
      '#rows' => 4,
      '#default_value' => $block->log->value,
      '#description' => t('Briefly describe the changes you have made.'),
    );

    return parent::form($form, $form_state, $block);
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::submit().
   *
   * Updates the custom block object by processing the submitted values.
   *
   * This function can be called by a "Next" button of a wizard to update the
   * form state's entity with the current step's values before proceeding to the
   * next step.
   */
  public function submit(array $form, array &$form_state) {
    // Build the block object from the submitted values.
    $block = parent::submit($form, $form_state);

    // Save as a new revision if requested to do so.
    if (!empty($form_state['values']['revision'])) {
      $block->setNewRevision();
    }

    return $block;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $block = $this->entity;
    $insert = empty($block->id->value);
    $block->save();
    $watchdog_args = array('@type' => $block->bundle(), '%info' => $block->label());
    $block_type = entity_load('custom_block_type', $block->type->value);
    $t_args = array('@type' => $block_type->label(), '%info' => $block->label());

    if ($insert) {
      watchdog('content', '@type: added %info.', $watchdog_args, WATCHDOG_NOTICE);
      drupal_set_message(t('@type %info has been created.', $t_args));
    }
    else {
      watchdog('content', '@type: updated %info.', $watchdog_args, WATCHDOG_NOTICE);
      drupal_set_message(t('@type %info has been updated.', $t_args));
    }

    if ($block->id->value) {
      $form_state['values']['id'] = $block->id->value;
      $form_state['id'] = $block->id->value;
      if ($insert) {
        if ($theme = $block->getTheme()) {
          $form_state['redirect'] = 'admin/structure/block/add/custom_block:' . $block->uuid->value . '/' . $theme;
        }
        else {
          $form_state['redirect'] = 'admin/structure/block/add/custom_block:' . $block->uuid->value . '/' . \Drupal::config('system.theme')->get('default');
        }
      }
      else {
        $form_state['redirect'] = 'admin/structure/custom-blocks';
      }
    }
    else {
      // In the unlikely case something went wrong on save, the block will be
      // rebuilt and block form redisplayed.
      drupal_set_message(t('The block could not be saved.'), 'error');
      $form_state['rebuild'] = TRUE;
    }

    // Clear the page and block caches.
    cache_invalidate_tags(array('content' => TRUE));
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::delete().
   */
  public function delete(array $form, array &$form_state) {
    $destination = array();
    $query = \Drupal::request()->query;
    if (!is_null($query->get('destination'))) {
      $destination = drupal_get_destination();
      $query->remove('destination');
    }
    $block = $this->buildEntity($form, $form_state);
    $form_state['redirect'] = array('block/' . $block->id() . '/delete', array('query' => $destination));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    if ($this->entity->isNew()) {
      // @todo Inject this once https://drupal.org/node/2060865 is in.
      $exists = \Drupal::entityManager()->getStorageController('custom_block')->loadByProperties(array('info' => $form_state['values']['info']));
      if (!empty($exists)) {
        form_set_error('info', t('A block with description %name already exists.', array(
        '%name' => $form_state['values']['info']
      )));
      }
    }
  }

}
