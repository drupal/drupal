<?php

/**
 * @file
 * Contains \Drupal\field_ui\Plugin\Derivative\FieldUiLocalTask.
 */

namespace Drupal\field_ui\Plugin\Derivative;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Menu\LocalTaskDerivativeBase;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all entity bundles.
 */
class FieldUiLocalTask extends LocalTaskDerivativeBase implements ContainerDerivativeInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The entity manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * Creates an FieldUiLocalTask object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   */
  public function __construct(RouteProviderInterface $route_provider, EntityManagerInterface $entity_manager, TranslationInterface $translation_manager) {
    $this->routeProvider = $route_provider;
    $this->entityManager = $entity_manager;
    $this->translationManager = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('entity.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    $this->derivatives = array();

    foreach ($this->entityManager->getDefinitions() as $entity_type => $entity_info) {
      if ($entity_info['fieldable'] && isset($entity_info['links']['admin-form'])) {
        $this->derivatives["overview_$entity_type"] = array(
          'route_name' => "field_ui.overview_$entity_type",
          'weight' => 1,
          'title' => $this->t('Manage fields'),
          'tab_root_id' => "field_ui.fields:overview_$entity_type",
        );

        // 'Manage form display' tab.
        $this->derivatives["form_display_overview_$entity_type"] = array(
          'route_name' => "field_ui.form_display_overview_$entity_type",
          'weight' => 2,
          'title' => $this->t('Manage form display'),
          'tab_root_id' => "field_ui.fields:overview_$entity_type",
        );

        // 'Manage display' tab.
        $this->derivatives["display_overview_$entity_type"] = array(
          'route_name' => "field_ui.display_overview_$entity_type",
          'weight' => 3,
          'title' => $this->t('Manage display'),
          'tab_root_id' => "field_ui.fields:overview_$entity_type",
        );

        // Field instance edit tab.
        $this->derivatives["instance_edit_$entity_type"] = array(
          'route_name' => "field_ui.instance_edit_$entity_type",
          'title' => $this->t('Edit'),
          'tab_root_id' => "field_ui.fields:instance_edit_$entity_type",
        );

        // Field settings tab.
        $this->derivatives["field_edit_$entity_type"] = array(
          'route_name' => "field_ui.field_edit_$entity_type",
          'title' => $this->t('Field settings'),
          'tab_root_id' => "field_ui.fields:instance_edit_$entity_type",
        );

        // View and form modes secondary tabs.
        // The same base $path for the menu item (with a placeholder) can be
        // used for all bundles of a given entity type; but depending on
        // administrator settings, each bundle has a different set of view
        // modes available for customisation. So we define menu items for all
        // view modes, and use a route requirement to determine which ones are
        // actually visible for a given bundle.
        $this->derivatives['field_form_display_default_' . $entity_type] = array(
          'title' => 'Default',
          'route_name' => "field_ui.form_display_overview_$entity_type",
          'tab_root_id' => "field_ui.fields:overview_$entity_type",
          'tab_parent_id' => "field_ui.fields:form_display_overview_$entity_type",
        );
        $this->derivatives['field_display_default_' . $entity_type] = array(
          'title' => 'Default',
          'route_name' => "field_ui.display_overview_$entity_type",
          'tab_root_id' => "field_ui.fields:overview_$entity_type",
          'tab_parent_id' => "field_ui.fields:display_overview_$entity_type",
        );

        // One local task for each form mode.
        $weight = 0;
        foreach (entity_get_form_modes($entity_type) as $form_mode => $form_mode_info) {
          $this->derivatives['field_form_display_' . $form_mode . '_' . $entity_type] = array(
            'title' => $form_mode_info['label'],
            'route_name' => "field_ui.form_display_overview_form_mode_$entity_type",
            'route_parameters' => array(
              'form_mode_name' => $form_mode,
            ),
            'tab_root_id' => "field_ui.fields:overview_$entity_type",
            'tab_parent_id' => "field_ui.fields:form_display_overview_$entity_type",
            'weight' => $weight++,
          );
        }

        // One local task for each view mode.
        $weight = 0;
        foreach (entity_get_view_modes($entity_type) as $view_mode => $form_mode_info) {
          $this->derivatives['field_display_' . $view_mode . '_' . $entity_type] = array(
            'title' => $form_mode_info['label'],
            'route_name' => "field_ui.display_overview_view_mode_$entity_type",
            'route_parameters' => array(
              'view_mode_name' => $view_mode,
            ),
            'tab_root_id' => "field_ui.fields:overview_$entity_type",
            'tab_parent_id' => "field_ui.fields:display_overview_$entity_type",
            'weight' => $weight++,
          );
        }
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

  /**
   * Alters the tab_root_id definition for field_ui local tasks.
   *
   * @param array $local_tasks
   *   An array of local tasks plugin definitions, keyed by plugin ID.
   */
  public function alterLocalTasks(&$local_tasks) {
    foreach ($this->entityManager->getDefinitions() as $entity_type => $entity_info) {
      if ($entity_info['fieldable'] && isset($entity_info['links']['admin-form'])) {
        if ($parent_task = $this->getPluginIdFromRoute($entity_info['links']['admin-form'], $local_tasks)) {
          $local_tasks["field_ui.fields:overview_$entity_type"]['tab_root_id'] = $parent_task;
          $local_tasks["field_ui.fields:form_display_overview_$entity_type"]['tab_root_id'] = $parent_task;
          $local_tasks["field_ui.fields:display_overview_$entity_type"]['tab_root_id'] = $parent_task;
          $local_tasks["field_ui.fields:field_form_display_default_$entity_type"]['tab_root_id'] = $parent_task;
          $local_tasks["field_ui.fields:field_display_default_$entity_type"]['tab_root_id'] = $parent_task;

          foreach (entity_get_form_modes($entity_type) as $form_mode => $form_mode_info) {
            $local_tasks['field_ui.fields:field_form_display_' . $form_mode . '_' . $entity_type]['tab_root_id'] = $parent_task;
          }

          foreach (entity_get_view_modes($entity_type) as $view_mode => $form_mode_info) {
            $local_tasks['field_ui.fields:field_display_' . $view_mode . '_' . $entity_type]['tab_root_id'] = $parent_task;
          }
        }
      }
    }
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translationManager->translate($string, $args, $options);
  }

}
