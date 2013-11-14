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
  public function getCancelRoute() {
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_negotiation_configure_browser_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $browser_langcode = NULL) {
    $this->browserLangcode = $browser_langcode;

    $form = parent::buildForm($form, $form_state);

    // @todo Convert to getCancelRoute() after http://drupal.org/node/2082071.
    $form['actions']['cancel']['#href'] = 'admin/config/regional/language/detection/browser';
    return $form;
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

    $form_state['redirect_route']['route_name'] = 'language.negotiation_browser';
  }

}
