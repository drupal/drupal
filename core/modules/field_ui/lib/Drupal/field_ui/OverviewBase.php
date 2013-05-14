<?php

/**
 * @file
 * Definition of Drupal\field_ui\OverviewBase.
 */

namespace Drupal\field_ui;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\ControllerInterface;
use Drupal\Core\Entity\EntityManager;

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

    form_load_include($form_state, 'inc', 'field_ui', 'field_ui.admin');
    $this->entity_type = $entity_type;
    $this->bundle = $bundle;
    $this->adminPath = $this->entityManager->getAdminPath($this->entity_type, $this->bundle);
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

}
