<?php

/**
 * @file
 * Contains \Drupal\comment\Controller\AdminController.
 */

namespace Drupal\comment\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\comment\CommentManager;
use Drupal\field\FieldInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for comment module administrative routes.
 */
class AdminController extends ControllerBase implements ContainerInjectionInterface {

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
      $container->get('field.info'),
      $container->get('comment.manager')
    );
  }

  /**
   * Constructs an AdminController object.
   *
   * @param \Drupal\field\FieldInfo $field_info
   *   The field info service.
   * @param \Drupal\comment\CommentManager $comment_manager
   *   The comment manager service.
   */
  public function __construct(FieldInfo $field_info, CommentManager $comment_manager) {
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
      'field_name' => $this->t('Field name'),
      'usage' => array(
        'data' => $this->t('Used in'),
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'type' => $this->t('Type'),
    );

    // Add a column for field UI operations if the Field UI module is enabled.
    $field_ui_enabled = $this->moduleHandler()->moduleExists('field_ui');
    if ($field_ui_enabled) {
      $header['operations'] = $this->t('Operations');
    }

    $entity_bundles = $this->entityManager()->getAllBundleInfo();
    $entity_types = $this->entityManager()->getDefinitions();
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

        $bundles = $field_info->getBundles();
        $sample_bundle = reset($bundles);
        $sample_instance = $this->fieldInfo->getInstance($entity_type, $sample_bundle, $field_name);

        $tokens = array(
          '@label' => $sample_instance->label,
          '@field_name' => $field_name,
        );
        $row['data']['field_name']['data'] = $field_info->get('locked') ? $this->t('@label (@field_name) (Locked)', $tokens) : $this->t('@label (@field_name)', $tokens);

        $row['data']['usage']['data'] = array(
          '#theme' => 'item_list',
          '#items' => array(),
        );
        foreach ($field_info_map['bundles'] as $bundle) {
          if (isset($entity_bundles[$entity_type][$bundle])) {
            // Add the current instance.
            if ($field_ui_enabled && ($route_info = $this->entityManager()->getAdminRouteInfo($entity_type, $bundle))) {
              $row['data']['usage']['data']['#items'][] = $this->l($entity_bundles[$entity_type][$bundle]['label'], $route_info['route_name'], $route_info['route_parameters']);
            }
            else {
              $row['data']['usage']['data']['#items'][] = $entity_bundles[$entity_type][$bundle]['label'];
            }
          }
        }

        $row['data']['type']['data'] = String::checkPlain($entity_types[$entity_type]['label']);

        if ($field_ui_enabled) {
          if ($this->currentUser()->hasPermission('administer comment fields')) {
            $links['fields'] = array(
              'title' => $this->t('Manage fields'),
              'href' => 'admin/structure/comments/manage/' . $entity_type . '__' . $field_name . '/fields',
              'weight' => 5,
            );
          }
          if ($this->currentUser()->hasPermission('administer comment display')) {
            $links['display'] = array(
              'title' => $this->t('Manage display'),
              'href' => 'admin/structure/comments/manage/' . $entity_type . '__' . $field_name . '/display',
              'weight' => 10,
            );
          }
          if ($this->currentUser()->hasPermission('administer comment form display')) {
            $links['form_display'] = array(
              'title' => $this->t('Manage form display'),
              'href' => 'admin/structure/comments/manage/' . $entity_type . '__' . $field_name . '/form-display',
              'weight' => 10,
            );
          }

          $row['data']['operations']['data'] = array(
            '#type' => 'operations',
            '#links' => $links,
          );
        }
        $rows[$entity_type . '__' . $field_name] = $row;
      }
    }

    $build['overview'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No comment forms available.'),
    );
    $build['#title'] = $this->t('Comment forms');

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
    // Add a link to manage entity fields if the Field UI module is enabled.
    $field_ui_enabled = $this->moduleHandler()->moduleExists('field_ui');

    // @todo Provide dynamic routing to get entity type and field name.
    //   https://drupal.org/node/2098011.
    list($entity_type, $field) = explode('__', $field_name, 2);
    $field_info = $this->fieldInfo->getField($entity_type, $field);

    $entity_type_info = $this->entityManager()->getDefinition($entity_type);
    $entity_bundle_info = $this->entityManager()->getBundleInfo($entity_type);

    $build['usage'] = array(
      '#theme' => 'item_list',
      '#title' => String::checkPlain($entity_type_info['label']),
      '#items' => array(),
    );
    // Loop over all of bundles to which this comment field is attached.
    foreach ($field_info->getBundles() as $bundle) {
      // Add the current instance to the list of bundles.
      if ($field_ui_enabled && ($route_info = $this->entityManager()->getAdminRouteInfo($entity_type, $bundle))) {
        // Add a link to configure the fields on the given bundle and entity
        // type combination.
        $build['usage']['#items'][] = $this->l($entity_bundle_info[$bundle]['label'], $route_info['route_name'], $route_info['route_parameters']);
      }
      else {
        // Field UI is disabled so fallback to a list of bundle labels
        // instead of links to configure fields.
        $build['usage']['#items'][] = String::checkPlain($entity_bundle_info[$bundle]['label']);
      }
    }

    return $build;
  }

  /**
   * Route title callback.
   *
   * @param string $field_name
   *   The comment field for which the overview is to be displayed.
   *
   * @return string
   *   The human readable field name.
   */
  public function bundleTitle($field_name) {
    return $this->commentManager->getFieldUIPageTitle($field_name);
  }

}
