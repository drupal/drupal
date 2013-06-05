<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\User.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ViewExecutable;

/**
 * Field handler to provide simple renderer that allows linking to a user.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("user")
 */
class User extends FieldPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->options['link_to_user'])) {
      $this->additional_fields['uid'] = 'uid';
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_user'] = array('default' => TRUE, 'bool' => TRUE);
    return $options;
  }

  /**
   * Provide link to node option
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['link_to_user'] = array(
      '#title' => t('Link this field to its user'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_user'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  function render_link($data, $values) {
    if (!empty($this->options['link_to_user']) && user_access('access user profiles') && ($entity = $this->get_entity($values)) && $data !== NULL && $data !== '') {
      $this->options['alter']['make_link'] = TRUE;
      $uri = $entity->uri();
      $this->options['alter']['path'] = $uri['path'];
    }
    return $data;
  }

  function render($values) {
    $value = $this->getValue($values);
    return $this->render_link($this->sanitizeValue($value), $values);
  }

}
