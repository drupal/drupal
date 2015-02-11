<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\Link.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base field handler to present a link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("comment_link")
 */
class Link extends FieldPluginBase {

  /**
   * Entity Manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * Constructs a Link field plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = array('default' => '');
    $options['link_to_entity'] = array('default' => FALSE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    $form['link_to_entity'] = array(
      '#title' => $this->t('Link field to the entity if there is no comment'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_entity'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $comment = $this->getEntity($values);
    return $this->renderLink($comment, $values);
  }

  /**
   * Prepares the link pointing to the comment or its node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $data
   *   The comment entity.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : $this->t('View');
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $data;
    $cid = $comment->id();

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['html'] = TRUE;

    if (!empty($cid)) {
      $this->options['alter']['url'] = Url::fromRoute('entity.comment.canonical', ['comment' => $cid]);
      $this->options['alter']['fragment'] = "comment-" . $cid;
    }
    // If there is no comment link to the node.
    elseif ($this->options['link_to_node']) {
      $entity = $comment->getCommentedEntity();
      $this->options['alter']['url'] = $entity->urlInfo();
    }

    return $text;
  }

}
