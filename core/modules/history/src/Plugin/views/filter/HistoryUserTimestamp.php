<?php

namespace Drupal\history\Plugin\views\filter;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter for new content.
 *
 * The handler is named history_user, because of compatibility reasons, the
 * table is history.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("history_user_timestamp")]
class HistoryUserTimestamp extends FilterPluginBase {

  use UncacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $no_operator = TRUE;

  /**
   * Constructs a HistoryUserTimestamp object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Component\Datetime\TimeInterface|null $time
   *   The time service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected ?TimeInterface $time = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!$time) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $time argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3395991', E_USER_DEPRECATED);
      $this->time = \Drupal::service('datetime.time');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    // @todo There are better ways of excluding required and multiple (object flags)
    unset($form['expose']['required']);
    unset($form['expose']['multiple']);
    unset($form['expose']['remember']);
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    // Only present a checkbox for the exposed filter itself. There's no way
    // to tell the difference between not checked and the default value, so
    // specifying the default value via the views UI is meaningless.
    if ($form_state->get('exposed')) {
      if (isset($this->options['expose']['label'])) {
        $label = $this->options['expose']['label'];
      }
      else {
        $label = $this->t('Has new content');
      }
      $form['value'] = [
        '#type' => 'checkbox',
        '#title' => $label,
        '#default_value' => $this->value,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // This can only work if we're authenticated in.
    if (!\Drupal::currentUser()->isAuthenticated()) {
      return;
    }

    // Don't filter if we're exposed and the checkbox isn't selected.
    if ((!empty($this->options['exposed'])) && empty($this->value)) {
      return;
    }

    // Hey, Drupal kills old history, so nodes that haven't been updated
    // since HISTORY_READ_LIMIT are outta here!
    $limit = $this->time->getRequestTime() - HISTORY_READ_LIMIT;

    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";
    $node = $this->query->ensureTable('node_field_data', $this->relationship);

    $clause = '';
    $clause2 = '';
    if ($alias = $this->query->ensureTable('comment_entity_statistics', $this->relationship)) {
      $clause = "OR $alias.last_comment_timestamp > (***CURRENT_TIME*** - $limit)";
      $clause2 = "OR $field < $alias.last_comment_timestamp";
    }

    // NULL means a history record doesn't exist. That's clearly new content.
    // Unless it's very very old content. Everything in the query is already
    // type safe cause none of it is coming from outside here.
    $this->query->addWhereExpression($this->options['group'], "($field IS NULL AND ($node.changed > (***CURRENT_TIME*** - $limit) $clause)) OR $field < $node.changed $clause2");
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }
  }

}
