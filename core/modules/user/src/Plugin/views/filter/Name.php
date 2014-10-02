<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\filter\Name.
 */

namespace Drupal\user\Plugin\views\filter;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter handler for usernames.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("user_name")
 */
class Name extends InOperator {

  protected $alwaysMultiple = TRUE;

  protected function valueForm(&$form, FormStateInterface $form_state) {
    $values = array();
    if ($this->value) {
      $result = entity_load_multiple_by_properties('user', array('uid' => $this->value));
      foreach ($result as $account) {
        if ($account->id()) {
          $values[] = $account->getUsername();
        }
        else {
          $values[] = 'Anonymous'; // Intentionally NOT translated.
        }
      }
    }

    sort($values);
    $default_value = implode(', ', $values);
    $form['value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Usernames'),
      '#description' => $this->t('Enter a comma separated list of user names.'),
      '#default_value' => $default_value,
      '#autocomplete_route_name' => 'user.autocomplete_anonymous',
    );

    $user_input = $form_state->getUserInput();
    if ($form_state->get('exposed') && !isset($user_input[$this->options['expose']['identifier']])) {
      $user_input[$this->options['expose']['identifier']] = $default_value;
      $form_state->setUserInput($user_input);
    }
  }

  protected function valueValidate($form, FormStateInterface $form_state) {
    $values = Tags::explode($form_state->getValue(array('options', 'value')));
    if ($uids = $this->validate_user_strings($form['value'], $form_state, $values)) {
      $form_state->setValue(array('options', 'value'), $uids);
    }
  }

  public function acceptExposedInput($input) {
    $rc = parent::acceptExposedInput($input);

    if ($rc) {
      // If we have previously validated input, override.
      if (isset($this->validated_exposed_input)) {
        $this->value = $this->validated_exposed_input;
      }
    }

    return $rc;
  }

  public function validateExposed(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    if (empty($this->options['expose']['identifier'])) {
      return;
    }

    $identifier = $this->options['expose']['identifier'];
    $input = $form_state->getValue($identifier);

    if ($this->options['is_grouped'] && isset($this->options['group_info']['group_items'][$input])) {
      $this->operator = $this->options['group_info']['group_items'][$input]['operator'];
      $input = $this->options['group_info']['group_items'][$input]['value'];
    }

    $values = Tags::explode($input);

    if (!$this->options['is_grouped'] || ($this->options['is_grouped'] && ($input != 'All'))) {
      $uids = $this->validate_user_strings($form[$identifier], $form_state, $values);
    }
    else {
      $uids = FALSE;
    }

    if ($uids) {
      $this->validated_exposed_input = $uids;
    }
  }

  /**
   * Validate the user string. Since this can come from either the form
   * or the exposed filter, this is abstracted out a bit so it can
   * handle the multiple input sources.
   */
  function validate_user_strings(&$form, FormStateInterface $form_state, $values) {
    $uids = array();
    $placeholders = array();
    $args = array();
    foreach ($values as $value) {
      if (strtolower($value) == 'anonymous') {
        $uids[] = 0;
      }
      else {
        $missing[strtolower($value)] = TRUE;
        $args[] = $value;
        $placeholders[] = "'%s'";
      }
    }

    if (!$args) {
      return $uids;
    }

    $result = entity_load_multiple_by_properties('user', array('name' => $args));
    foreach ($result as $account) {
      unset($missing[strtolower($account->getUsername())]);
      $uids[] = $account->id();
    }

    if ($missing) {
      $form_state->setError($form, format_plural(count($missing), 'Unable to find user: @users', 'Unable to find users: @users', array('@users' => implode(', ', array_keys($missing)))));
    }

    return $uids;
  }

  protected function valueSubmit($form, FormStateInterface $form_state) {
    // prevent array filter from removing our anonymous user.
  }

  // Override to do nothing.
  public function getValueOptions() { }

  public function adminSummary() {
    // set up $this->valueOptions for the parent summary
    $this->valueOptions = array();

    if ($this->value) {
      $result = entity_load_multiple_by_properties('user', array('uid' => $this->value));
      foreach ($result as $account) {
        if ($account->id()) {
          $this->valueOptions[$account->id()] = $account->label();
        }
        else {
          $this->valueOptions[$account->id()] = 'Anonymous'; // Intentionally NOT translated.
        }
      }
    }

    return parent::adminSummary();
  }

}
