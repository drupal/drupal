<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\Link.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to present a link to the user.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("user_link")
 */
class Link extends FieldPluginBase {

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['uid'] = 'uid';
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = array('default' => '', 'translatable' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  // An example of field level access control.
  public function access() {
    return user_access('administer users') || user_access('access user profiles');
  }

  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $this->renderLink($this->getEntity($values), $values);
  }

  /**
   * Alters the field to render a link.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\views\ResultRow $values
   *   The current row of the views result.
   *
   * @return string
   *   The acutal rendered text (without the link) of this field.
   */
  protected function renderLink(EntityInterface $entity, ResultRow $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('View');

    $this->options['alter']['make_link'] = TRUE;
    $uri = $entity->uri();
    $this->options['alter']['path'] = $uri['path'];

    return $text;
  }

}
