<?php

namespace Drupal\user\Plugin\views\argument_validator;

use Drupal\Core\Form\FormStateInterface;

/**
 * Validates whether a user name is valid.
 *
 * @ViewsArgumentValidator(
 *   id = "user_name",
 *   title = @Translation("User name"),
 *   entity_type = "user"
 * )
 */
class UserName extends User {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $entity_type = $this->entityTypeManager->getDefinition('user');

    $form['multiple']['#options'] = [
      0 => $this->t('Single name', ['%type' => $entity_type->getLabel()]),
      1 => $this->t('One or more names separated by , or +', ['%type' => $entity_type->getLabel()]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateArgument($argument) {
    if ($this->multipleCapable && $this->options['multiple']) {
      // At this point only interested in individual IDs no matter what type,
      // just splitting by the allowed delimiters.
      $names = array_filter(preg_split('/[,+ ]/', $argument));
    }
    elseif ($argument) {
      $names = [$argument];
    }
    // No specified argument should be invalid.
    else {
      return FALSE;
    }

    $accounts = $this->userStorage->loadByProperties(['name' => $names]);

    // If there are no accounts, return FALSE now. As we will not enter the
    // loop below otherwise.
    if (empty($accounts)) {
      return FALSE;
    }

    // Validate each account. If any fails break out and return false.
    foreach ($accounts as $account) {
      if (!in_array($account->getAccountName(), $names) || !$this->validateEntity($account)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function processSummaryArguments(&$args) {
    // If the validation says the input is a username, we should reverse the
    // argument so it works for example for generation summary URLs.
    $uids_arg_keys = array_flip($args);

    foreach ($this->userStorage->loadMultiple($args) as $uid => $account) {
      $args[$uids_arg_keys[$uid]] = $account->label();
    }
  }

}
