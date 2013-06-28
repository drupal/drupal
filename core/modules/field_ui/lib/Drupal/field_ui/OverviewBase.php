<?php

/**
 * @file
 * Contains \Drupal\field_ui\OverviewBase.
 */

namespace Drupal\field_ui;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormInterface;

/**
 * Abstract base class for Field UI overview forms.
 */
abstract class OverviewBase implements FormInterface, ControllerInterface {

  /**
   * The name of the entity type.
   *
   * @var string
   */
  protected $entity_type = '';

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle = '';

  /**
   * The entity view or form mode.
   *
   * @var string
   */
  protected $mode = '';

  /**
   * The admin path of the overview page.
   *
   * @var string
   */
  protected $adminPath = NULL;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a new OverviewBase.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManager $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $entity_type = NULL, $bundle = NULL) {
    $entity_info = $this->entityManager->getDefinition($entity_type);
    if (!empty($entity_info['bundle_prefix'])) {
      $bundle = $entity_info['bundle_prefix'] . $bundle;
    }

    $this->entity_type = $entity_type;
    $this->bundle = $bundle;
    $this->adminPath = $this->entityManager->getAdminPath($this->entity_type, $this->bundle);

    // When displaying the form, make sure the list of fields is up-to-date.
    if (empty($form_state['post'])) {
      field_info_cache_clear();
    }
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
  }

  /**
   * Get the regions needed to create the overview form.
   *
   * @return array
   *   Example usage:
   *   @code
   *     return array(
   *       'content' => array(
   *         // label for the region.
   *         'title' => t('Content'),
   *         // Indicates if the region is visible in the UI.
   *         'invisible' => TRUE,
   *         // A mesage to indicate that there is nothing to be displayed in
   *         // the region.
   *         'message' => t('No field is displayed.'),
   *       ),
   *     );
   *   @endcode
   */
  abstract public function getRegions();

  /**
   * Returns an associative array of all regions.
   */
  public function getRegionOptions() {
    $options = array();
    foreach ($this->getRegions() as $region => $data) {
      $options[$region] = $data['title'];
    }
    return $options;
  }

  /**
   * Performs pre-render tasks on field_ui_table elements.
   *
   * This function is assigned as a #pre_render callback in
   * field_ui_element_info().
   *
   * @see drupal_render().
   */
  public function tablePreRender($elements) {
    $js_settings = array();

    // For each region, build the tree structure from the weight and parenting
    // data contained in the flat form structure, to determine row order and
    // indentation.
    $regions = $elements['#regions'];
    $tree = array('' => array('name' => '', 'children' => array()));
    $trees = array_fill_keys(array_keys($regions), $tree);

    $parents = array();
    $list = drupal_map_assoc(element_children($elements));

    // Iterate on rows until we can build a known tree path for all of them.
    while ($list) {
      foreach ($list as $name) {
        $row = &$elements[$name];
        $parent = $row['parent_wrapper']['parent']['#value'];
        // Proceed if parent is known.
        if (empty($parent) || isset($parents[$parent])) {
          // Grab parent, and remove the row from the next iteration.
          $parents[$name] = $parent ? array_merge($parents[$parent], array($parent)) : array();
          unset($list[$name]);

          // Determine the region for the row.
          $region_name = call_user_func($row['#region_callback'], $row);

          // Add the element in the tree.
          $target = &$trees[$region_name][''];
          foreach ($parents[$name] as $key) {
            $target = &$target['children'][$key];
          }
          $target['children'][$name] = array('name' => $name, 'weight' => $row['weight']['#value']);

          // Add tabledrag indentation to the first row cell.
          if ($depth = count($parents[$name])) {
            $children = element_children($row);
            $cell = current($children);
            $row[$cell]['#prefix'] = theme('indentation', array('size' => $depth)) . (isset($row[$cell]['#prefix']) ? $row[$cell]['#prefix'] : '');
          }

          // Add row id and associate JS settings.
          $id = drupal_html_class($name);
          $row['#attributes']['id'] = $id;
          if (isset($row['#js_settings'])) {
            $row['#js_settings'] += array(
              'rowHandler' => $row['#row_type'],
              'name' => $name,
              'region' => $region_name,
            );
            $js_settings[$id] = $row['#js_settings'];
          }
        }
      }
    }
    // Determine rendering order from the tree structure.
    foreach ($regions as $region_name => $region) {
      $elements['#regions'][$region_name]['rows_order'] = array_reduce($trees[$region_name], array($this, 'reduceOrder'));
    }

    $elements['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('fieldUIRowsData' => $js_settings),
    );

    return $elements;
  }

  /**
   * Determines the rendering order of an array representing a tree.
   *
   * Callback for array_reduce() within
   * \Drupal\field_ui\OverviewBase::tablePreRender().
   */
  public function reduceOrder($array, $a) {
    $array = !isset($array) ? array() : $array;
    if ($a['name']) {
      $array[] = $a['name'];
    }
    if (!empty($a['children'])) {
      uasort($a['children'], 'drupal_sort_weight');
      $array = array_merge($array, array_reduce($a['children'], array($this, 'reduceOrder')));
    }
    return $array;
  }

}
