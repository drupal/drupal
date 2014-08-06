<?php

/**
 * @file
 * Contains \Drupal\contact\CategoryForm.
 */

namespace Drupal\contact;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form for category edit forms.
 */
class CategoryForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $category = $this->entity;
    $default_category = $this->config('contact.settings')->get('default_category');

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $category->label(),
      '#description' => $this->t("Example: 'website feedback' or 'product information'."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $category->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => array(
        'exists' => '\Drupal\contact\Entity\Category::load',
      ),
      '#disabled' => !$category->isNew(),
    );
    $form['recipients'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Recipients'),
      '#default_value' => implode(', ', $category->recipients),
      '#description' => $this->t("Example: 'webmaster@example.com' or 'sales@example.com,support@example.com' . To specify multiple recipients, separate each email address with a comma."),
      '#required' => TRUE,
    );
    $form['reply'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Auto-reply'),
      '#default_value' => $category->reply,
      '#description' => $this->t('Optional auto-reply. Leave empty if you do not want to send the user an auto-reply message.'),
    );
    $form['weight'] = array(
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $category->weight,
      '#description' => $this->t('When listing categories, those with lighter (smaller) weights get listed before categories with heavier (larger) weights. Categories with equal weights are sorted alphabetically.'),
    );
    $form['selected'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Make this the default category.'),
      '#default_value' => $default_category === $category->id(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

    // Validate and each email recipient.
    $recipients = explode(',', $form_state['values']['recipients']);

    foreach ($recipients as &$recipient) {
      $recipient = trim($recipient);
      if (!valid_email_address($recipient)) {
        $form_state->setErrorByName('recipients', $this->t('%recipient is an invalid email address.', array('%recipient' => $recipient)));
      }
    }
    $form_state['values']['recipients'] = $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $category = $this->entity;
    $status = $category->save();
    $contact_settings = $this->config('contact.settings');

    $edit_link = \Drupal::linkGenerator()->generateFromUrl($this->t('Edit'), $this->entity->urlInfo());

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('Category %label has been updated.', array('%label' => $category->label())));
      $this->logger('contact')->notice('Category %label has been updated.', array('%label' => $category->label(), 'link' => $edit_link));
    }
    else {
      drupal_set_message($this->t('Category %label has been added.', array('%label' => $category->label())));
      $this->logger('contact')->notice('Category %label has been added.', array('%label' => $category->label(), 'link' => $edit_link));
    }

    // Update the default category.
    if ($form_state['values']['selected']) {
      $contact_settings
        ->set('default_category', $category->id())
        ->save();
    }
    // If it was the default category, empty out the setting.
    elseif ($contact_settings->get('default_category') == $category->id()) {
      $contact_settings
        ->set('default_category', NULL)
        ->save();
    }

    $form_state->setRedirect('contact.category_list');
  }

}
