<?php

/**
 * @file
 * Contains \Drupal\node\Form\NodeDeleteForm.
 */

namespace Drupal\node\Form;

use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityNGConfirmFormBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\PathBasedGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides a form for deleting a node.
 */
class NodeDeleteForm extends EntityNGConfirmFormBase implements EntityControllerInterface {

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\PathBasedGeneratorInterface
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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Routing\PathBasedGeneratorInterface $url_generator
   *   The URL generator.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $node_type_storage
   *   The node type storage.
   */
  public function __construct(ModuleHandlerInterface $module_handler, PathBasedGeneratorInterface $url_generator, EntityStorageControllerInterface $node_type_storage) {
    parent::__construct($module_handler);

    $this->urlGenerator = $url_generator;
    $this->nodeTypeStorage = $node_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $container->get('module_handler'),
      $container->get('url_generator'),
      $container->get('plugin.manager.entity')->getStorageController('node_type')
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
  public function getCancelPath() {
    $uri = $this->entity->uri();
    return $this->urlGenerator->generateFromPath($uri['path'], $uri['options']);
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
  public function form(array $form, array &$form_state) {
    // Do not attach fields to the delete form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    watchdog('content', '@type: deleted %title.', array('@type' => $this->entity->bundle(), '%title' => $this->entity->label()));
    $node_type = $this->nodeTypeStorage->load($this->entity->bundle())->label();
    drupal_set_message(t('@type %title has been deleted.', array('@type' => $node_type, '%title' => $this->entity->label())));
    $form_state['redirect'] = '<front>';
  }

}
