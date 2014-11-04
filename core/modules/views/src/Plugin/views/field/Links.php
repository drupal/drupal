<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\field\Links.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url as UrlObject;

/**
 * A abstract handler which provides a collection of links.
 *
 * @ingroup views_field_handlers
 */
abstract class Links extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::defineOptions().
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['fields'] = array('default' => array());
    $options['destination'] = array('default' => TRUE);

    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::defineOptions().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    // Only show fields that precede this one.
    $field_options = $this->getPreviousFieldLabels();
    $form['fields'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Fields'),
      '#description' => $this->t('Fields to be included as links.'),
      '#options' => $field_options,
      '#default_value' => $this->options['fields'],
    );
    $form['destination'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Include destination'),
      '#description' => $this->t('Include a "destination" parameter in the link to return the user to the original view upon completing the link action.'),
      '#default_value' => $this->options['destination'],
    );
  }

  /**
   * Gets the list of links used by this field.
   *
   * @return array
   *   The links which are used by the render function.
   */
  protected function getLinks() {
    $links = array();
    foreach ($this->options['fields'] as $field) {
      if (empty($this->view->field[$field]->last_render_text)) {
        continue;
      }
      $title = $this->view->field[$field]->last_render_text;
      $path = '';
      $url = NULL;
      if (!empty($this->view->field[$field]->options['alter']['path'])) {
        $path = $this->view->field[$field]->options['alter']['path'];
      }
      elseif (!empty($this->view->field[$field]->options['alter']['url']) && $this->view->field[$field]->options['alter']['url'] instanceof UrlObject) {
        $url = $this->view->field[$field]->options['alter']['url'];
      }
      // Make sure that tokens are replaced for this paths as well.
      $tokens = $this->getRenderTokens(array());
      $path = strip_tags(String::decodeEntities(strtr($path, $tokens)));

      $links[$field] = array(
        'url' => $path ? UrlObject::fromUri('base://' . $path) : $url,
        'title' => $title,
      );
      if (!empty($this->options['destination'])) {
        $links[$field]['query'] = drupal_get_destination();
      }
    }

    return $links;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::query().
   */
  public function query() {
  }

}
