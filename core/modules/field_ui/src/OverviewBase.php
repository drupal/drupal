<?php

/**
 * @file
 * Contains \Drupal\field_ui\OverviewBase.
 */

namespace Drupal\field_ui;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract base class for Field UI overview forms.
 */
abstract class OverviewBase extends FormBase {

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
   * The entity type of the entity bundle.
   *
   * @var string
   */
  protected $bundleEntityType;

  /**
   * The entity view or form mode.
   *
   * @var string
   */
  protected $mode = '';

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new OverviewBase.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    $entity_type = $this->entityManager->getDefinition($entity_type_id);
    $this->bundleEntityType = $entity_type->getBundleEntityType();
    if (!isset($form_state['bundle'])) {
      if (!$bundle) {
        $bundle = $this->getRequest()->attributes->get('_raw_variables')->get($this->bundleEntityType);
      }
      $form_state['bundle'] = $bundle;
    }

    $this->entity_type = $entity_type_id;
    $this->bundle = $form_state['bundle'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
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
   *         'title' => $this->t('Content'),
   *         // Indicates if the region is visible in the UI.
   *         'invisible' => TRUE,
   *         // A message to indicate that there is nothing to be displayed in
   *         // the region.
   *         'message' => $this->t('No field is displayed.'),
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
   * @param array $element
   *   A structured array containing two sub-levels of elements. Properties
   *   used:
   *   - #tabledrag: The value is a list of $options arrays that are passed to
   *     drupal_attach_tabledrag(). The HTML ID of the table is added to each
   *     $options array.
   *
   * @see drupal_render()
   * @see \Drupal\Core\Render\Element\Table::preRenderTable()
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
    $children = Element::children($elements);
    $list = array_combine($children, $children);

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
            $children = Element::children($row);
            $cell = current($children);
            $indentation = array(
              '#theme' => 'indentation',
              '#size' => $depth,
            );
            $row[$cell]['#prefix'] = drupal_render($indentation) . (isset($row[$cell]['#prefix']) ? $row[$cell]['#prefix'] : '');
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

    // If the custom #tabledrag is set and there is a HTML ID, add the table's
    // HTML ID to the options and attach the behavior.
    // @see \Drupal\Core\Render\Element\Table::preRenderTable()
    if (!empty($elements['#tabledrag']) && isset($elements['#attributes']['id'])) {
      foreach ($elements['#tabledrag'] as $options) {
        $options['table_id'] = $elements['#attributes']['id'];
        drupal_attach_tabledrag($elements, $options);
      }
    }

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
      uasort($a['children'], array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));
      $array = array_merge($array, array_reduce($a['children'], array($this, 'reduceOrder')));
    }
    return $array;
  }

}
