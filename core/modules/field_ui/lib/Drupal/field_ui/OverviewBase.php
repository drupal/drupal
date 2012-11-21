<?php

/**
 * @file
 * Definition of Drupal\field_ui\OverviewBase.
 */

namespace Drupal\field_ui;

/**
 * Abstract base class for Field UI overview forms.
 */
abstract class OverviewBase {

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
   * Creates a field UI overview form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A reference to a keyed array containing the current state of the form.
   *
   * @return array
   *   The array containing the complete form.
   */
  public function form(array $form, array &$form_state) {
    // Add the validate and submit behavior.
    $form['#validate'] = array(array($this, 'validate'));
    $form['#submit'] = array(array($this, 'submit'));
    return $form;
  }

  /**
   * Validate handler for the field UI overview form.
   *
   * @param array $form
   *   The root element or form.
   * @param array $form_state
   *   The state of the form.
   */
  public function validate(array $form, array &$form_state) {
  }

  /**
   * Submit handler for the field UI overview form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function submit(array $form, array &$form_state) {
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
