<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\area\View.
 */

namespace Drupal\views\Plugin\views\area;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views area handlers. Insert a view inside of an area.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("view")
 */
class View extends AreaPluginBase {

  /**
   * Stores whether the embedded view is actually empty.
   *
   * @var bool
   */
  protected $isEmpty;

  /**
   * The view storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
   protected $viewStorage;

   /**
    * Constructs a View object.
    *
    * @param array $configuration
    *   A configuration array containing information about the plugin instance.
    * @param string $plugin_id
    *   The plugin_id for the plugin instance.
    * @param mixed $plugin_definition
    *   The plugin implementation definition.
    * @param \Drupal\Core\Entity\EntityStorageInterface $view_storage
    *   The view storage.
    */
   public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $view_storage) {
     parent::__construct($configuration, $plugin_id, $plugin_definition);

     $this->viewStorage = $view_storage;
   }

   /**
    * {@inheritdoc}
    */
   public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
       return new static(
           $configuration,
           $plugin_id,
           $plugin_definition,
       $container->get('entity.manager')->getStorage('view')
       );
   }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_to_insert'] = array('default' => '');
    $options['inherit_arguments'] = array('default' => FALSE);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $view_display = $this->view->storage->id() . ':' . $this->view->current_display;

    $options = array('' => $this->t('-Select-'));
    $options += Views::getViewsAsOptions(FALSE, 'all', $view_display, FALSE, TRUE);
    $form['view_to_insert'] = array(
      '#type' => 'select',
      '#title' => $this->t('View to insert'),
      '#default_value' => $this->options['view_to_insert'],
      '#description' => $this->t('The view to insert into this area.'),
      '#options' => $options,
    );

    $form['inherit_arguments'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Inherit contextual filters'),
      '#default_value' => $this->options['inherit_arguments'],
      '#description' => $this->t('If checked, this view will receive the same contextual filters as its parent.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!empty($this->options['view_to_insert'])) {
      list($view_name, $display_id) = explode(':', $this->options['view_to_insert']);

      $view = $this->viewStorage->load($view_name)->getExecutable();

      if (empty($view) || !$view->access($display_id)) {
        return array();
      }
      $view->setDisplay($display_id);

      // Avoid recursion
      $view->parent_views += $this->view->parent_views;
      $view->parent_views[] = "$view_name:$display_id";

      // Check if the view is part of the parent views of this view
      $search = "$view_name:$display_id";
      if (in_array($search, $this->view->parent_views)) {
        drupal_set_message(t("Recursion detected in view @view display @display.", array('@view' => $view_name, '@display' => $display_id)), 'error');
      }
      else {
        if (!empty($this->options['inherit_arguments']) && !empty($this->view->args)) {
          $output = $view->preview($display_id, $this->view->args);
        }
        else {
          $output = $view->preview($display_id);
        }
        $this->isEmpty = $view->display_handler->outputIsEmpty();
        return $output;
      }
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if (isset($this->isEmpty)) {
      return $this->isEmpty;
    }
    else {
      return parent::isEmpty();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    list($view_id) = explode(':', $this->options['view_to_insert'], 2);
    if ($view_id) {
      $view = $this->viewStorage->load($view_id);
      $dependencies[$view->getConfigDependencyKey()][] = $view->getConfigDependencyName();
    }

    return $dependencies;
  }

}
