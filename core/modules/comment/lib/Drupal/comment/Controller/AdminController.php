<?php

/**
 * @file
 * Contains \Drupal\comment\Controller\AdminController
 */

namespace Drupal\comment\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\field\FieldInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AdminController implements ControllerInterface {

  /**
   * Entity Manager service.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Field info service.
   *
   * @var \Drupal\field\FieldInfo
   */
  protected $fieldInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity'),
      $container->get('module_handler'),
      $container->get('field.info')
    );
  }

  /**
   * Constructs an AdminController object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   Entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   Module Handler service.
   * @param \Drupal\field\FieldInfo $field_info
   *   Field Info service.
   */
  public function __construct(EntityManager $entity_manager, ModuleHandler $module_handler, FieldInfo $field_info) {
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->fieldInfo = $field_info;
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
    // @todo Remove when http://drupal.org/node/1981644 is in.
    drupal_set_title(t('Comment forms'));
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
    $fields = array_filter($this->fieldInfo->getFieldMap(), function ($value) {
      if ($value['type'] == 'comment') {
        return TRUE;
      }
    });

    foreach ($fields as $field_name => $field_info_map) {
      $field_info = $this->fieldInfo->getField($field_name);
      // Initialize the row.
      $rows[$field_name]['class'] = $field_info['locked'] ? array('field-disabled') : array('');
      $rows[$field_name]['data']['field_name']['data'] = $field_info['locked'] ? t('@field_name (Locked)', array('@field_name' => $field_name)) : $field_name;

      $rows[$field_name]['data']['usage']['data'] = array(
        '#theme' => 'item_list',
        '#items' => array(),
      );
      foreach ($field_info['bundles'] as $entity_type => $field_bundles) {
        $bundles = array();
        foreach ($field_bundles as $bundle) {
          if (isset($entity_bundles[$entity_type][$bundle])) {
            // Add the current instance.
            if ($field_ui_enabled && ($path = $this->entityManager->getAdminPath($entity_type, $bundle))) {
              $bundles[] = l($entity_bundles[$entity_type][$bundle]['label'], $path . '/fields');
            }
            else {
              $bundles[] = $entity_bundles[$entity_type][$bundle]['label'];
            }
          }
        }
        // Format used entity bundles.
        $rows[$field_name]['data']['usage']['data']['#items'][] = t('@entity_type: !bundles', array(
          '@entity_type' => $entity_types[$entity_type]['label'],
          '!bundles' => implode(', ', $bundles),
        ));
      }

      if ($field_ui_enabled) {
        // @todo Check proper permissions for operations.
        $links['fields'] = array(
          'title' => t('manage fields'),
          'href' => 'admin/structure/comments/manage/' . $field_name . '/fields',
          'weight' => 5,
        );
        $links['display'] = array(
          'title' => t('manage display'),
          'href' => 'admin/structure/comments/manage/' . $field_name . '/display',
          'weight' => 10,
        );

        $rows[$field_name]['data']['operations']['data'] = array(
          '#type' => 'operations',
          '#links' => $links,
        );
      }
    }

    $build['overview'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No comment forms available.'),
    );

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
    // @todo Decide on better UX http://drupal.org/node/1901110
    $build['usage'] = array(
      '#theme' => 'item_list',
      '#items' => array(),
    );
    // @todo Remove when entity_get_bundles() is a method on the entity manager.
    $entity_bundles = entity_get_bundles();
    $entity_types = $this->entityManager->getDefinitions();
    // Add a link to manage entity fields if the Field UI module is enabled.
    $field_ui_enabled = $this->moduleHandler->moduleExists('field_ui');

    $field_info = $this->fieldInfo->getField($field_name);
    // Loop over all of the entity types to which this comment field is
    // attached.
    foreach ($field_info['bundles'] as $entity_type => $field_bundles) {
      $bundles = array();
      // Loop over all of the bundles of this entity type to which this comment
      // field is attached.
      foreach ($field_bundles as $bundle) {
        if (isset($entity_bundles[$entity_type][$bundle])) {
          // Add the current instance to the list of bundles.
          if ($field_ui_enabled && ($path = $this->entityManager->getAdminPath($entity_type, $bundle))) {
            // Add a link to configure the fields on the given bundle and entity
            // type combination.
            $bundles[] = l($entity_bundles[$entity_type][$bundle]['label'], $path . '/fields');
          }
          else {
            // Field UI is disabled so fallback to a list of bundle labels
            // instead of links to configure fields.
            $bundles[] = $entity_bundles[$entity_type][$bundle]['label'];
          }
        }
      }
      // Format used entity bundles for this comment field.
      $build['usage']['#items'][] = t('@entity_type: !bundles', array(
        '@entity_type' => $entity_types[$entity_type]['label'],
        '!bundles' => implode(', ', $bundles),
      ));
    }

    return $build;
  }

}
