<?php

/**
 * @file
 * Definition of Drupal\contact\CategoryFormController.
 */

namespace Drupal\contact;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFormController;

/**
 * Base form controller for category edit forms.
 */
class CategoryFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $category) {
    $form = parent::form($form, $form_state, $category);

    $default_category = config('contact.settings')->get('default_category');

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
        'source' => array('label'),
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
        form_set_error('recipients', t('%recipient is an invalid e-mail address.', array('%recipient' => $recipient)));
      }
    }
    $form_state['values']['recipients'] = $recipients;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    // @todo We should not be calling contact_category_delete_form() from
    // within the form builder.
    if ($form_state['triggering_element']['#value'] == t('Delete')) {
      // Rebuild the form to confirm category deletion.
      $form_state['redirect'] = 'admin/structure/contact/manage/' . $form_state['values']['id'] . '/delete';
      return NULL;
    }

    return parent::submit($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $category = $this->getEntity($form_state);
    // Property enforceIsNew is not supported by config entity. So this is only
    // way to make sure that entity is not saved.
    $is_new = !$category->getOriginalID();
    $category->save();
    $id = $category->id();

    if ($is_new) {
      drupal_set_message(t('Category %label has been added.', array('%label' => $category->label())));
      watchdog('contact', 'Category %label has been added.', array('%label' => $category->label()), WATCHDOG_NOTICE, l(t('Edit'), 'admin/structure/contact/manage/' . $id . '/edit'));
    }
    else {
      drupal_set_message(t('Category %label has been updated.', array('%label' => $category->label())));
      watchdog('contact', 'Category %label has been updated.', array('%label' => $category->label()), WATCHDOG_NOTICE, l(t('Edit'), 'admin/structure/contact/manage/' . $id . '/edit'));
    }

    // Update the default category.
    $contact_config = config('contact.settings');
    if ($form_state['values']['selected']) {
      $contact_config
        ->set('default_category', $id)
        ->save();
    }
    // If it was the default category, empty out the setting.
    elseif ($contact_config->get('default_category') == $id) {
      $contact_config
        ->clear('default_category')
        ->save();
    }

    // Remove the 'selected' value, which is not part of the Category.
    unset($form_state['values']['selected']);

    $form_state['redirect'] = 'admin/structure/contact';
  }
}
