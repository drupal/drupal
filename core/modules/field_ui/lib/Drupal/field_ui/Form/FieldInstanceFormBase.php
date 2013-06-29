<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\FieldInstanceFormBase.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\field\Plugin\Type\Widget\WidgetPluginManager;
use Drupal\field\FieldInstanceInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class FieldInstanceFormBase implements FormInterface, ControllerInterface {

  /**
   * The field instance being edited.
   *
   * @var \Drupal\field\FieldInstanceInterface
   */
  protected $instance;

  /**
   * The field widget plugin manager.
   *
   * @var \Drupal\field\Plugin\Type\Widget\WidgetPluginManager
   */
  protected $widgetManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a new field instance form.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param \Drupal\field\Plugin\Type\Widget\WidgetPluginManager $widget_manager
   *   The field widget plugin manager.
   */
  public function __construct(EntityManager $entity_manager, WidgetPluginManager $widget_manager) {
    $this->entityManager = $entity_manager;
    $this->widgetManager = $widget_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity'),
      $container->get('plugin.manager.field.widget')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, FieldInstanceInterface $field_instance = NULL) {
    $this->instance = $form_state['instance'] = $field_instance;
  }

  /**
   * Returns the next redirect path in a multipage sequence.
   *
   * @return string|array
   *   Either the next path, or an array of redirect paths.
   */
  protected function getNextDestination() {
    $next_destination = FieldUI::getNextDestination();
    if (empty($next_destination)) {
      $next_destination = $this->entityManager->getAdminPath($this->instance->entity_type, $this->instance->bundle) . '/fields';
    }
    return $next_destination;
  }

}
