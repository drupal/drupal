<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\NodeNewComments.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\Core\Database\Connection;
use Drupal\comment\CommentInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\Numeric;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to display the number of new comments.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_new_comments")
 */
class NodeNewComments extends Numeric {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('database'));
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['entity_id'] = 'nid';
    $this->additional_fields['type'] = 'type';
    $this->additional_fields['comment_count'] = array('table' => 'comment_entity_statistics', 'field' => 'comment_count');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['link_to_comment'] = array('default' => TRUE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_comment'] = array(
      '#title' => $this->t('Link this field to new comments'),
      '#description' => $this->t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_comment'],
    );

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
    $this->field_alias = $this->table . '_' . $this->field;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    $user = \Drupal::currentUser();
    if ($user->isAnonymous() || empty($values)) {
      return;
    }

    $nids = array();
    $ids = array();
    foreach ($values as $id => $result) {
      $nids[] = $result->{$this->aliases['nid']};
      $values[$id]->{$this->field_alias} = 0;
      // Create a reference so we can find this record in the values again.
      if (empty($ids[$result->{$this->aliases['nid']}])) {
        $ids[$result->{$this->aliases['nid']}] = array();
      }
      $ids[$result->{$this->aliases['nid']}][] = $id;
    }

    if ($nids) {
      $result = $this->database->query("SELECT n.nid, COUNT(c.cid) as num_comments FROM {node} n INNER JOIN {comment_field_data} c ON n.nid = c.entity_id AND c.entity_type = 'node' AND c.default_langcode = 1
        LEFT JOIN {history} h ON h.nid = n.nid AND h.uid = :h_uid WHERE n.nid IN ( :nids[] )
        AND c.changed > GREATEST(COALESCE(h.timestamp, :timestamp), :timestamp) AND c.status = :status GROUP BY n.nid", array(
        ':status' => CommentInterface::PUBLISHED,
        ':h_uid' => $user->id(),
        ':nids[]' => $nids,
        ':timestamp' => HISTORY_READ_LIMIT,
      ));
      foreach ($result as $node) {
        foreach ($ids[$node->id()] as $id) {
          $values[$id]->{$this->field_alias} = $node->num_comments;
        }
      }
    }
  }

  /**
   * Prepares the link to the first new comment.
   *
   * @param string $data
   *   The XSS safe string for the link text.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    if (!empty($this->options['link_to_comment']) && $data !== NULL && $data !== '') {
      $node = entity_create('node', array(
        'nid' => $this->getValue($values, 'nid'),
        'type' => $this->getValue($values, 'type'),
      ));
      $page_number = \Drupal::entityManager()->getStorage('comment')
        ->getNewCommentPageNumber($this->getValue($values, 'comment_count'), $this->getValue($values), $node);
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = 'node/' . $node->id();
      $this->options['alter']['query'] = $page_number ? array('page' => $page_number) : NULL;
      $this->options['alter']['fragment'] = 'new';
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    if (!empty($value)) {
      return $this->renderLink(parent::render($values), $values);
    }
    else {
      $this->options['alter']['make_link'] = FALSE;
    }
  }

}
