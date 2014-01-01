<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Enhancer\EntityRouteEnhancer.
 */

namespace Drupal\Core\Entity\Enhancer;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\HtmlEntityFormController;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Enhances an entity form route with the appropriate controller.
 */
class EntityRouteEnhancer implements RouteEnhancerInterface {

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $resolver;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $manager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new EntityRouteEnhancer object.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $resolver
   *   The controller resolver.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(ControllerResolverInterface $resolver, EntityManagerInterface $manager, FormBuilderInterface $form_builder) {
    $this->resolver = $resolver;
    $this->manager = $manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (empty($defaults['_content'])) {
      if (!empty($defaults['_entity_form'])) {
        $wrapper = new HtmlEntityFormController($this->resolver, $this->manager, $this->formBuilder, $defaults['_entity_form']);
        $defaults['_content'] = array($wrapper, 'getContentResult');
      }
      elseif (!empty($defaults['_entity_list'])) {
        $defaults['_content'] = '\Drupal\Core\Entity\Controller\EntityListController::listing';
        $defaults['entity_type'] = $defaults['_entity_list'];
        unset($defaults['_entity_list']);
      }
      elseif (!empty($defaults['_entity_view'])) {
        $defaults['_content'] = '\Drupal\Core\Entity\Controller\EntityViewController::view';
        if (strpos($defaults['_entity_view'], '.') !== FALSE) {
          // The _entity_view entry is of the form entity_type.view_mode.
          list($entity_type, $view_mode) = explode('.', $defaults['_entity_view']);
          $defaults['view_mode'] = $view_mode;
        }
        else {
          // Only the entity type is nominated, the view mode will use the
          // default.
          $entity_type = $defaults['_entity_view'];
        }
        // Set by reference so that we get the upcast value.
        if (!empty($defaults[$entity_type])) {
          $defaults['_entity'] = &$defaults[$entity_type];
        }
        else {
          // The entity is not keyed by its entity_type. Attempt to find it
          // using a converter.
          $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
          if ($route && is_object($route)) {
            $options = $route->getOptions();
            if (isset($options['parameters'])) {
              foreach ($options['parameters'] as $name => $details) {
                if (!empty($details['type'])) {
                  $type = $details['type'];
                  // Type is of the form entity:{entity_type}.
                  $parameter_entity_type = substr($type, strlen('entity:'));
                  if ($entity_type == $parameter_entity_type) {
                    // We have the matching entity type. Set the '_entity' key
                    // to point to this named placeholder. The entity in this
                    // position is the one being rendered.
                    $defaults['_entity'] = &$defaults[$name];
                  }
                }
              }
            }
            else {
              throw new \RuntimeException(sprintf('Failed to find entity of type %s in route named %s', $entity_type, $defaults[RouteObjectInterface::ROUTE_NAME]));
            }
          }
          else {
            throw new \RuntimeException(sprintf('Failed to find entity of type %s in route named %s', $entity_type, $defaults[RouteObjectInterface::ROUTE_NAME]));
          }
        }
        unset($defaults['_entity_view']);
      }
    }
    return $defaults;
  }

}
