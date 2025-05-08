<?php

namespace Drupal\language\Form;

use Drupal\Core\Form\ConfigFormBaseTrait;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * The confirmation form for deleting a browser language negotiation mapping.
 *
 * @internal
 */
class NegotiationBrowserDeleteForm extends ConfirmFormBase {
  use ConfigFormBaseTrait;

  /**
   * The browser language code to be deleted.
   *
   * @var string
   */
  protected $browserLangcode;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['language.mappings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %browser_langcode?', ['%browser_langcode' => $this->browserLangcode]);
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
    $this->config('language.mappings')
      ->clear('map.' . $this->browserLangcode)
      ->save();

    $args = [
      '%browser' => $this->browserLangcode,
    ];

    $this->logger('language')->notice('The browser language detection mapping for the %browser browser language code has been deleted.', $args);

    $this->messenger()->addStatus($this->t('The mapping for the %browser browser language code has been deleted.', $args));

    $form_state->setRedirect('language.negotiation_browser');
  }

}
