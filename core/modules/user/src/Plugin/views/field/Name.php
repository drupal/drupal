<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\Name.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Plugin\views\field\User;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to provide simple renderer that allows using a themed user link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_name")
 */
class Name extends User {

  /**
   * Overrides \Drupal\user\Plugin\views\field\User::init().
   *
   * Add uid in the query so we can test for anonymous if needed.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->options['overwrite_anonymous']) || !empty($this->options['format_username'])) {
      $this->additional_fields['uid'] = 'uid';
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['overwrite_anonymous'] = array('default' => FALSE);
    $options['anonymous_text'] = array('default' => '');
    $options['format_username'] = array('default' => TRUE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['format_username'] = array(
      '#title' => $this->t('Use formatted username'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['format_username']),
      '#description' => $this->t('If checked, the username will be formatted by the system. If unchecked, it will be displayed raw.'),
    );
    $form['overwrite_anonymous'] = array(
      '#title' => $this->t('Overwrite the value to display for anonymous users'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['overwrite_anonymous']),
      '#description' => $this->t('Enable to display different text for anonymous users.'),
    );
    $form['anonymous_text'] = array(
      '#title' => $this->t('Text to display for anonymous users'),
      '#type' => 'textfield',
      '#default_value' => $this->options['anonymous_text'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[overwrite_anonymous]"]' => array('checked' => TRUE),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink($data, ResultRow $values) {
    if (!empty($this->options['link_to_user']) || !empty($this->options['overwrite_anonymous']) || !empty($this->options['format_username'])) {
      $account = entity_create('user');
      $account->uid = $this->getValue($values, 'uid');
      $account->name = $this->getValue($values);
      if (!empty($this->options['overwrite_anonymous']) && !$account->id()) {
        // This is an anonymous user, and we're overwriting the text.
        return SafeMarkup::checkPlain($this->options['anonymous_text']);
      }
      elseif (!empty($this->options['link_to_user'])) {
        $account->name = $this->getValue($values);
        $username = array(
          '#theme' => 'username',
          '#account' => $account,
        );
        return drupal_render($username);
      }
      // If we want a formatted username, do that.
      if (!empty($this->options['format_username'])) {
        return user_format_name($account);
      }
    }

    // Otherwise, there's no special handling, so return the data directly.
    return $data;
  }

}
