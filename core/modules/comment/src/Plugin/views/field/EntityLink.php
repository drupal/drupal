<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\views\field\EntityLink.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Handler for showing comment module's entity links.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("comment_entity_link")
 */
class EntityLink extends FieldPluginBase {

  /**
   * Stores the result of node_view_multiple for all rows to reuse it later.
   *
   * @var array
   */
  protected $build;

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['teaser'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['teaser'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show teaser-style link'),
      '#default_value' => $this->options['teaser'],
      '#description' => $this->t('Show the comment link in the form used on standard entity teasers, rather than the full entity form.'),
    );

    parent::buildOptionsForm($form, $form_state);
  }

  public function query() {}

  /**
   * Implements \Drupal\views\Plugin\views\field\FieldPluginBase::pre_render().
   */
  public function preRender(&$values) {
    // Render all nodes, so you can grep the comment links.
    $entities = array();
    foreach ($values as $row) {
      $entity = $row->_entity;
      $entities[$entity->id()] = $entity;
    }
    if ($entities) {
      $this->build = entity_view_multiple($entities, $this->options['teaser'] ? 'teaser' : 'full');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);

    // Only render the links, if they are defined.
    return !empty($this->build[$entity->id()]['links']['comment__comment']) ? drupal_render($this->build[$entity->id()]['links']['comment__comment']) : '';
  }

}
