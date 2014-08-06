<?php

/**
 * @file
 * Contains \Drupal\language\Form\NegotiationConfigureBrowserDeleteForm.
 */

namespace Drupal\language\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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
  public function getCancelUrl() {
    return new Url('language.negotiation_browser');
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
  public function buildForm(array $form, FormStateInterface $form_state, $browser_langcode = NULL) {
    $this->browserLangcode = $browser_langcode;

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mappings = language_get_browser_drupal_langcode_mappings();

    if (array_key_exists($this->browserLangcode, $mappings)) {
      unset($mappings[$this->browserLangcode]);
      language_set_browser_drupal_langcode_mappings($mappings);
    }

    $form_state->setRedirect('language.negotiation_browser');
  }

}
