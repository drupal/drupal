<?php

namespace Drupal\system\Form;

/**
 * Builds a confirmation form for enabling experimental modules.
 *
 * @internal
 */
class ModulesListExperimentalConfirmForm extends ModulesListConfirmForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you wish to enable experimental modules?');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_modules_experimental_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildMessageList() {
    $this->messenger()->addWarning($this->t('<a href=":url">Experimental modules</a> are provided for testing purposes only. Use at your own risk.', [':url' => 'https://www.drupal.org/core/experimental']));

    $items = parent::buildMessageList();
    // Add the list of experimental modules after any other messages.
    $items[] = $this->t('The following modules are experimental: @modules', ['@modules' => implode(', ', array_values($this->modules['experimental']))]);

    return $items;
  }

}
