<?php

/**
 * @file
 * Contains \Drupal\language\Form\NegotiationConfigureBrowserDeleteForm.
 */

namespace Drupal\language\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a confirmation form for deleting a browser language negotiation mapping.
 */
class NegotiationBrowserDeleteForm extends ConfirmFormBase {

  /**
   * The browser language code to be deleted.
   *
   * @var string
   */
  protected $browserLangcode;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %browser_langcode?', array('%browser_langcode' => $this->browserLangcode));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/config/regional/language/detection/browser';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'language_negotiation_configure_browser_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $browser_langcode = NULL) {
    $this->browserLangcode = $browser_langcode;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $mappings = language_get_browser_drupal_langcode_mappings();

    if (array_key_exists($this->browserLangcode, $mappings)) {
      unset($mappings[$this->browserLangcode]);
      language_set_browser_drupal_langcode_mappings($mappings);
    }

    $form_state['redirect'] = 'admin/config/regional/language/detection/browser';
  }

}
