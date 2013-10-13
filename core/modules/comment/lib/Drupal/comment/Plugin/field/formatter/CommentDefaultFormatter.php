<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\field\formatter\CommentDefaultFormatter.
 */

namespace Drupal\comment\Plugin\field\formatter;

use Drupal\comment\CommentStorageControllerInterface;
use Drupal\Core\Entity\EntityRenderControllerInterface;
use Drupal\Core\Entity\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\Field\FieldDefinitionInterface;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a default comment formatter.
 *
 * @FieldFormatter(
 *   id = "comment_default",
 *   module = "comment",
 *   label = @Translation("Comment list"),
 *   field_types = {
 *     "comment"
 *   }
 * )
 */
class CommentDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The comment storage controller.
   *
   * @var \Drupal\comment\CommentStorageControllerInterface
   */
  protected $storageController;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The comment render controller.
   *
   * @var \Drupal\Core\Entity\EntityRenderControllerInterface
   */
  protected $renderController;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $container->get('current_user'),
      $container->get('entity.manager')->getStorageController('comment'),
      $container->get('entity.manager')->getRenderController('comment')
    );
  }

  /**
   * Constructs a new CommentDefaultFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\comment\CommentStorageControllerInterface
   *   The comment storage controller.
   * @param \Drupal\Core\Entity\EntityRenderControllerInterface
   *   The comment render controller.
   */
  public function __construct($plugin_id, array $plugin_definition,  FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, AccountInterface $current_user, CommentStorageControllerInterface $comment_storage_controller, EntityRenderControllerInterface $comment_render_controller) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode);
    $this->renderController = $comment_render_controller;
    $this->storageController = $comment_storage_controller;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    $output = array();

    $field_name = $this->fieldDefinition->getFieldName();
    $entity = $items->getEntity();

    $status = $items->status;

    if ($status != COMMENT_HIDDEN && empty($entity->in_preview) &&
      // Comments are added to the search results and search index by
      // comment_node_update_index() instead of by this formatter, so don't
      // return anything if the view mode is search_index or search_result.
      !in_array($this->viewMode, array('search_result', 'search_index'))) {
      $comment_settings = $this->getFieldSettings();

      // Only attempt to render comments if the entity has visible comments.
      // Unpublished comments are not included in
      // $entity->get($field_name)->comment_count, but unpublished comments
      // should display if the user is an administrator.
      if ((($entity->get($field_name)->comment_count && $this->currentUser->hasPermission('access comments')) ||
        $this->currentUser->hasPermission('administer comments'))) {
        $mode = $comment_settings['default_mode'];
        $comments_per_page = $comment_settings['per_page'];
        if ($cids = comment_get_thread($entity, $field_name, $mode, $comments_per_page)) {
          $comments = $this->storageController->loadMultiple($cids);
          comment_prepare_thread($comments);
          $build = $this->renderController->viewMultiple($comments);
          $build['pager']['#theme'] = 'pager';
          $output['comments'] = $build;
        }
      }

      // Append comment form if the comments are open and the form is set to
      // display below the entity.
      if ($status == COMMENT_OPEN && $comment_settings['form_location'] == COMMENT_FORM_BELOW) {
        // Only show the add comment form if the user has permission.
        if ($this->currentUser->hasPermission('post comments')) {
          $output['comment_form'] = comment_add($entity, $field_name);
        }
      }

      $elements[] = $output + array(
        '#theme' => 'comment_wrapper__' . $entity->entityType() . '__' . $entity->bundle() . '__' . $field_name,
        '#entity' => $entity,
        '#display_mode' => $this->getFieldSetting('default_mode'),
        'comments' => array(),
        'comment_form' => array(),
      );
    }

    return $elements;
  }

}
