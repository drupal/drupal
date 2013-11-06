<?php

/**
 * @file
 * Contains \Drupal\node\Form\NodeDeleteForm.
 */

namespace Drupal\node\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a node.
 */
class NodeDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The node type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $nodeTypeStorage;

  /**
   * Constructs a NodeDeleteForm object.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $node_type_storage
   *   The node type storage.
   */
  public function __construct(UrlGeneratorInterface $url_generator, EntityStorageControllerInterface $node_type_storage) {
    $this->urlGenerator = $url_generator;
    $this->nodeTypeStorage = $node_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('url_generator'),
      $container->get('entity.manager')->getStorageController('node_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete %title?', array('%title' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);

    // @todo Convert to getCancelRoute() after http://drupal.org/node/1987778.
    $uri = $this->entity->uri();
    $actions['cancel']['#href'] = $this->urlGenerator->generateFromPath($uri['path'], $uri['options']);

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    watchdog('content', '@type: deleted %title.', array('@type' => $this->entity->bundle(), '%title' => $this->entity->label()));
    $node_type = $this->nodeTypeStorage->load($this->entity->bundle())->label();
    drupal_set_message(t('@type %title has been deleted.', array('@type' => $node_type, '%title' => $this->entity->label())));
    Cache::invalidateTags(array('content' => TRUE));
    $form_state['redirect'] = '<front>';
  }

}
