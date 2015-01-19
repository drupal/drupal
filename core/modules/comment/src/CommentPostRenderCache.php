<?php

/**
 * @file
 * Contains \Drupal\comment\CommentPostRenderCache.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Defines a service for comment post render cache callbacks.
 */
class CommentPostRenderCache {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * Constructs a new CommentPostRenderCache object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder service.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityFormBuilderInterface $entity_form_builder) {
    $this->entityManager = $entity_manager;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * #post_render_cache callback; replaces placeholder with comment form.
   *
   * @param array $element
   *   The renderable array that contains the to be replaced placeholder.
   * @param array $context
   *   An array with the following keys:
   *   - entity_type: an entity type
   *   - entity_id: an entity ID
   *   - field_name: a comment field name
   *
   * @return array
   *   A renderable array containing the comment form.
   */
  public function renderForm(array $element, array $context) {
    $field_name = $context['field_name'];
    $entity = $this->entityManager->getStorage($context['entity_type'])->load($context['entity_id']);
    $field_storage = FieldStorageConfig::loadByName($entity->getEntityTypeId(), $field_name);
    $values = array(
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'field_name' => $field_name,
      'comment_type' => $field_storage->getSetting('bundle'),
      'pid' => NULL,
    );
    $comment = $this->entityManager->getStorage('comment')->create($values);
    $form = $this->entityFormBuilder->getForm($comment);
    $markup = drupal_render($form);

    $callback = 'comment.post_render_cache:renderForm';
    $placeholder = drupal_render_cache_generate_placeholder($callback, $context);
    $element['#markup'] = str_replace($placeholder, $markup, $element['#markup']);
    $element = Renderer::mergeBubbleableMetadata($element, $form);

    return $element;
  }

}
