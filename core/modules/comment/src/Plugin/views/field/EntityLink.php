<?php

namespace Drupal\comment\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Handler for showing comment module's entity links.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("comment_entity_link")]
class EntityLink extends FieldPluginBase {

  /**
   * Stores the result of parent entities build for all rows to reuse it later.
   *
   * @var array
   */
  protected $build;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['teaser'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['teaser'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show teaser-style link'),
      '#default_value' => $this->options['teaser'],
      '#description' => $this->t('Show the comment link in the form used on standard entity teasers, rather than the full entity form.'),
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    // Render all nodes, so you can grep the comment links.
    $entities = [];
    foreach ($values as $row) {
      $entity = $row->_entity;
      $entities[$entity->id()] = $entity;
    }
    if ($entities) {
      $entityTypeId = reset($entities)->getEntityTypeId();
      $viewMode = $this->options['teaser'] ? 'teaser' : 'full';
      $this->build = \Drupal::entityTypeManager()
        ->getViewBuilder($entityTypeId)
        ->viewMultiple($entities, $viewMode);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);

    // Only render the links, if they are defined.
    if (!$entity || empty($this->build[$entity->id()]['links']['comment__comment'])) {
      return '';
    }
    return \Drupal::service('renderer')->render($this->build[$entity->id()]['links']['comment__comment']);
  }

}
