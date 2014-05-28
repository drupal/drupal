<?php

/**
 * @file
 * Contains \Drupal\comment\Controller\AdminController.
 */

namespace Drupal\comment\Controller;

use Drupal\comment\CommentManagerInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for comment module administrative routes.
 */
class AdminController extends ControllerBase {

  /**
   * The comment manager service.
   *
   * @var \Drupal\comment\CommentManagerInterface
   */
  protected $commentManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('comment.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * Constructs an AdminController object.
   *
   * @param \Drupal\comment\CommentManagerInterface $comment_manager
   *   The comment manager service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(CommentManagerInterface $comment_manager, FormBuilderInterface $form_builder) {
    $this->commentManager = $comment_manager;
    $this->formBuilder = $form_builder;
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
      'description' => array(
        'data' => $this->t('Description'),
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
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
      $field_storage_definitions = $this->entityManager()->getFieldStorageDefinitions($entity_type);
      foreach ($data as $field_name => $field_info_map) {
        $storage_definition = $field_storage_definitions[$field_name];
        // Initialize the row.
        $row = array(
          'class' => $storage_definition->get('locked') ? array('field-disabled') : array(''),
        );

        $label = $storage_definition->getLabel();
        if ($storage_definition instanceof FieldConfigInterface) {
          $bundles = $storage_definition->getBundles();
          $sample_bundle = reset($bundles);
          $field_definitions = $this->entityManager()->getFieldDefinitions($entity_type, $sample_bundle);
          $label = $field_definitions[$field_name]->getLabel();
        }

        $tokens = array(
          '@label' => $label,
          '@field_name' => $field_name,
        );
        $row['data']['field_name']['data'] = $storage_definition->get('locked') ? $this->t('@label (@field_name) (Locked)', $tokens) : $this->t('@label (@field_name)', $tokens);

        $row['data']['description']['data'] = $storage_definition->getSetting('description');
        $row['data']['usage']['data'] = array(
          '#theme' => 'item_list',
          '#items' => array(),
        );
        foreach ($field_info_map['bundles'] as $bundle) {
          if (isset($entity_bundles[$entity_type][$bundle])) {
            // Add the current instance.
            if ($field_ui_enabled && $route_info = FieldUI::getOverviewRouteInfo($entity_type, $bundle)) {
              $row['data']['usage']['data']['#items'][] = \Drupal::linkGenerator()->generateFromUrl($entity_bundles[$entity_type][$bundle]['label'], $route_info);
            }
            else {
              $row['data']['usage']['data']['#items'][] = $entity_bundles[$entity_type][$bundle]['label'];
            }
          }
        }

        $row['data']['type']['data'] = String::checkPlain($entity_types[$entity_type]->getLabel());

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
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No comment forms available.'),
    );
    $build['#title'] = $this->t('Comment forms');

    return $build;
  }

  /**
   * Presents an administrative comment listing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param string $type
   *   The type of the overview form ('approval' or 'new') default to 'new'.
   *
   * @return array
   *   Then comment multiple delete confirmation form or the comments overview
   *   administration form.
   */
  public function adminPage(Request $request, $type = 'new') {
    if ($request->request->get('operation') == 'delete' && $request->request->get('comments')) {
      return $this->formBuilder->getForm('\Drupal\comment\Form\ConfirmDeleteMultiple', $request);
    }
    else {
      return $this->formBuilder->getForm('\Drupal\comment\Form\CommentAdminOverview', $type);
    }
  }

}
