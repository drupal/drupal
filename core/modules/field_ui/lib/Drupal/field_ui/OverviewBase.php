<?php

/**
 * @file
 * Definition of Drupal\field_ui\OverviewBase.
 */

namespace Drupal\field_ui;

use Drupal\Core\Form\FormInterface;

/**
 * Abstract base class for Field UI overview forms.
 */
abstract class OverviewBase implements FormInterface {

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
   * The entity view mode.
   *
   * @var string
   */
  protected $view_mode = '';

  /**
   * The admin path of the overview page.
   *
   * @var string
   */
  protected $adminPath = NULL;

  /**
   * Constructs the overview object for a entity type, bundle and view mode.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle for the entity of entity_type.
   * @param string $view_mode
   *   (optional) The view mode for the entity which takes a string or
   *   "default".
   */
  public function __construct($entity_type, $bundle, $view_mode = NULL) {
    $this->entity_type = $entity_type;
    $this->bundle = $bundle;
    $this->view_mode = (isset($view_mode) ? $view_mode : 'default');
    $this->adminPath = field_ui_bundle_admin_path($this->entity_type, $this->bundle);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validate().
   */
  public function validate(array &$form, array &$form_state) {
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submit().
   */
  public function submit(array &$form, array &$form_state) {
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

}
