<?php

/**
 * @file
 * Contains \Drupal\comment\Controller\AdminController.
 */

namespace Drupal\comment\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\comment\CommentManager;
use Drupal\field\FieldInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for comment module administrative routes.
 */
class AdminController implements ContainerInjectionInterface {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The field info service.
   *
   * @var \Drupal\field\FieldInfo
   */
  protected $fieldInfo;

  /**
   * The comment manager service.
   *
   * @var \Drupal\comment\CommentManager
   */
  protected $commentManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity'),
      $container->get('module_handler'),
      $container->get('field.info'),
      $container->get('comment.manager')
    );
  }

  /**
   * Constructs an AdminController object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler service.
   * @param \Drupal\field\FieldInfo $field_info
   *   The field info service.
   */
  public function __construct(EntityManager $entity_manager, ModuleHandler $module_handler, FieldInfo $field_info, CommentManager $comment_manager) {
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->fieldInfo = $field_info;
    $this->commentManager = $comment_manager;
  }

  /**
   * Returns an overview of comment fields in use on the site.
   *
   * @return array
   *   A renderable array containing a list of comment fields, the entity
   *   type and bundle combinations on which they are in use and various
   *   operation links for configuring each field.
   */
  public function overviewBundles() {
    $header = array(
      'field_name' => t('Field name'),
      'usage' => array(
        'data' => t('Used in'),
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
    );

    // Add a column for field UI operations if the Field UI module is enabled.
    $field_ui_enabled = $this->moduleHandler->moduleExists('field_ui');
    if ($field_ui_enabled) {
      $header['operations'] = t('Operations');
    }

    // @todo Remove when entity_get_bundles() is a method on the entity manager.
    $entity_bundles = entity_get_bundles();
    $entity_types = $this->entityManager->getDefinitions();
    $rows = array();

    // Fetch a list of all comment fields.
    $fields = $this->commentManager->getAllFields();

    foreach ($fields as $entity_type => $data) {
      foreach ($data as $field_name => $field_info_map) {
        $field_info = $this->fieldInfo->getField($entity_type, $field_name);
        // Initialize the row.
        $row = array(
          'class' => $field_info->get('locked') ? array('field-disabled') : array(''),
        );
        $row['data']['field_name']['data'] = $field_info->get('locked') ? t('@field_name (Locked)', array('@field_name' => $field_name)) : check_plain($field_name);

        $row['data']['usage']['data'] = array(
          '#theme' => 'item_list',
          '#title' => check_plain($entity_types[$entity_type]['label']),
          '#items' => array(),
        );
        foreach ($field_info_map['bundles'] as $bundle) {
          if (isset($entity_bundles[$entity_type][$bundle])) {
            // Add the current instance.
            if ($field_ui_enabled && ($path = $this->entityManager->getAdminPath($entity_type, $bundle))) {
              $row['data']['usage']['data']['#items'][] = l($entity_bundles[$entity_type][$bundle]['label'], $path . '/fields');
            }
            else {
              $row['data']['usage']['data']['#items'][] = $entity_bundles[$entity_type][$bundle]['label'];
            }
          }
        }

        if ($field_ui_enabled) {
          // @todo Check proper permissions for operations.
          $links['fields'] = array(
            'title' => t('Manage fields'),
            'href' => 'admin/structure/comments/manage/' . $entity_type . '_' . $field_name . '/fields',
            'weight' => 5,
          );
          $links['display'] = array(
            'title' => t('Manage display'),
            'href' => 'admin/structure/comments/manage/' . $entity_type . '_' . $field_name . '/display',
            'weight' => 10,
          );

          $row['data']['operations']['data'] = array(
            '#type' => 'operations',
            '#links' => $links,
          );
        }
        $rows[$entity_type . '_' . $field_name] = $row;
      }
    }

    $build['overview'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No comment forms available.'),
    );
    $build['#title'] = t('Comment forms');

    return $build;
  }

  /**
   * Returns an overview of the entity types a comment field is attached to.
   *
   * @param string $field_name
   *   The comment field for which the overview is to be displayed.
   *
   * @return array
   *   A renderable array containing the list of entity types and bundle
   *   combinations on which the comment field is in use.
   */
  public function bundleInfo($field_name) {
    // @todo Remove when entity_get_bundles() is a method on the entity manager.
    $entity_bundles = entity_get_bundles();
    $entity_types = $this->entityManager->getDefinitions();
    // Add a link to manage entity fields if the Field UI module is enabled.
    $field_ui_enabled = $this->moduleHandler->moduleExists('field_ui');

    // @todo Provide dynamic routing to get entity type and field name.
    list($entity_type, $field) = explode('_', $field_name);
    $field_info = $this->fieldInfo->getField($entity_type, $field);
    // @todo Decide on better UX http://drupal.org/node/1901110
    $build['usage'] = array(
      '#theme' => 'item_list',
      '#title' => check_plain($entity_types[$entity_type]['label']),
      '#items' => array(),
    );
    // Loop over all of the entity types to which this comment field is
    // attached.
    foreach ($field_info->getBundles() as $bundle) {
      if (isset($entity_bundles[$entity_type][$bundle])) {
        // Add the current instance to the list of bundles.
        if ($field_ui_enabled && ($path = $this->entityManager->getAdminPath($entity_type, $bundle))) {
          // Add a link to configure the fields on the given bundle and entity
          // type combination.
          $build['usage']['#items'][] = l($entity_bundles[$entity_type][$bundle]['label'], $path . '/fields');
        }
        else {
          // Field UI is disabled so fallback to a list of bundle labels
          // instead of links to configure fields.
          $build['usage']['#items'][] = check_plain($entity_bundles[$entity_type][$bundle]['label']);
        }
      }
    }

    return $build;
  }

}
