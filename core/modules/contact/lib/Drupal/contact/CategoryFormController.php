<?php

/**
 * @file
 * Definition of Drupal\contact\CategoryFormController.
 */

namespace Drupal\contact;

use Drupal\Core\Entity\EntityFormController;

/**
 * Base form controller for category edit forms.
 */
class CategoryFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);

    $category = $this->entity;
    $default_category = \Drupal::config('contact.settings')->get('default_category');

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#maxlength' => 255,
      '#default_value' => $category->label(),
      '#description' => t("Example: 'website feedback' or 'product information'."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $category->id(),
      '#machine_name' => array(
        'exists' => 'contact_category_load',
      ),
      '#disabled' => !$category->isNew(),
    );
    $form['recipients'] = array(
      '#type' => 'textarea',
      '#title' => t('Recipients'),
      '#default_value' => implode(', ', $category->recipients),
      '#description' => t("Example: 'webmaster@example.com' or 'sales@example.com,support@example.com' . To specify multiple recipients, separate each e-mail address with a comma."),
      '#required' => TRUE,
    );
    $form['reply'] = array(
      '#type' => 'textarea',
      '#title' => t('Auto-reply'),
      '#default_value' => $category->reply,
      '#description' => t('Optional auto-reply. Leave empty if you do not want to send the user an auto-reply message.'),
    );
    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight'),
      '#default_value' => $category->weight,
      '#description' => t('When listing categories, those with lighter (smaller) weights get listed before categories with heavier (larger) weights. Categories with equal weights are sorted alphabetically.'),
    );
    $form['selected'] = array(
      '#type' => 'checkbox',
      '#title' => t('Make this the default category.'),
      '#default_value' => $default_category === $category->id(),
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);

    // Validate and each e-mail recipient.
    $recipients = explode(',', $form_state['values']['recipients']);

    foreach ($recipients as &$recipient) {
      $recipient = trim($recipient);
      if (!valid_email_address($recipient)) {
        $this->setFormError('recipients', $form_state, $this->t('%recipient is an invalid e-mail address.', array('%recipient' => $recipient)));
      }
    }
    $form_state['values']['recipients'] = $recipients;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $category = $this->entity;
    $status = $category->save();

    $uri = $category->urlInfo();
    $edit_link = \Drupal::l($this->t('Edit'), $uri['route_name'], $uri['route_parameters'], $uri['options']);

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('Category %label has been updated.', array('%label' => $category->label())));
      watchdog('contact', 'Category %label has been updated.', array('%label' => $category->label()), WATCHDOG_NOTICE, $edit_link);
    }
    else {
      drupal_set_message(t('Category %label has been added.', array('%label' => $category->label())));
      watchdog('contact', 'Category %label has been added.', array('%label' => $category->label()), WATCHDOG_NOTICE, $edit_link);
    }

    // Update the default category.
    $contact_config = \Drupal::config('contact.settings');
    if ($form_state['values']['selected']) {
      $contact_config
        ->set('default_category', $category->id())
        ->save();
    }
    // If it was the default category, empty out the setting.
    elseif ($contact_config->get('default_category') == $category->id()) {
      $contact_config
        ->set('default_category', NULL)
        ->save();
    }

    $form_state['redirect_route']['route_name'] = 'contact.category_list';
  }

}
