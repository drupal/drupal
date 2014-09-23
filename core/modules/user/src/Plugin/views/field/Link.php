<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\Link.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\Core\Entity\EntityInterface;

/**
 * Field handler to present a link to the user.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_link")
 */
class Link extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

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

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('administer users') || $account->hasPermission('access user profiles');
  }

  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if ($entity = $this->getEntity($values)) {
      return $this->renderLink($entity, $values);
    }
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
    $text = !empty($this->options['text']) ? $this->options['text'] : $this->t('View');

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = $entity->getSystemPath();

    return $text;
  }

}
