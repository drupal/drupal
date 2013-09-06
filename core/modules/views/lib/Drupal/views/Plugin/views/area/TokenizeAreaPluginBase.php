<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\area\TokenizeAreaPluginBase.
 */

namespace Drupal\views\Plugin\views\area;

/**
 * Tokenized base class for area handlers.
 *
 * This class provides a method tokenizeValue() to tokenize a given value with
 * the tokens of the first view result and additionally apllies global token
 * replacement to the passed value. The form elements to enable the replacement
 * functionality is automatically added to the buildOptionsForm().
 *
 * @ingroup views_area_handlers
 */
abstract class TokenizeAreaPluginBase extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['tokenize'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Add tokenization form elements.
    $this->tokenForm($form, $form_state);
  }

  /**
   * Adds tokenization form elements.
   */
  public function tokenForm(&$form, &$form_state) {
    $form['tokenize'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use replacement tokens from the first row'),
      '#default_value' => $this->options['tokenize'],
    );

    // Get a list of the available fields and arguments for token replacement.
    $options = array();
    foreach ($this->view->display_handler->getHandlers('field') as $field => $handler) {
      $options[t('Fields')]["[$field]"] = $handler->adminLabel();
    }

    $count = 0; // This lets us prepare the key as we want it printed.
    foreach ($this->view->display_handler->getHandlers('argument') as $handler) {
      $options[t('Arguments')]['%' . ++$count] = t('@argument title', array('@argument' => $handler->adminLabel()));
      $options[t('Arguments')]['!' . $count] = t('@argument input', array('@argument' => $handler->adminLabel()));
    }

    if (!empty($options)) {
      $form['tokens'] = array(
        '#type' => 'details',
        '#title' => t('Replacement patterns'),
        '#open' => TRUE,
        '#id' => 'edit-options-token-help',
        '#states' => array(
          'visible' => array(
            ':input[name="options[tokenize]"]' => array('checked' => TRUE),
          ),
        ),
      );
      $form['tokens']['help'] = array(
        '#markup' => '<p>' . t('The following tokens are available. If you would like to have the characters \'[\' and \']\' use the html entity codes \'%5B\' or  \'%5D\' or they will get replaced with empty space.') . '</p>',
      );
      foreach (array_keys($options) as $type) {
        if (!empty($options[$type])) {
          $items = array();
          foreach ($options[$type] as $key => $value) {
            $items[] = $key . ' == ' . $value;
          }
          $form['tokens']['tokens'] = array(
            '#theme' => 'item_list',
            '#items' => $items,
          );
        }
      }
    }

    $this->globalTokenForm($form, $form_state);
  }

  /**
   * Replaces value with special views tokens and global tokens.
   *
   * @param string $value
   *   The value to eventually tokenize.
   *
   * @return string
   *   Tokenized value if tokenize option is enabled. In any case global tokens
   *   will be replaced.
   */
  public function tokenizeValue($value) {
    if ($this->options['tokenize']) {
      $value = $this->view->style_plugin->tokenizeValue($value, 0);
    }
    // As we add the globalTokenForm() we also should replace the token here.
    return $this->globalTokenReplace($value);
  }

}
