<?php

/**
 * @file
 * Contains \Drupal\views\Form\ViewsForm.
 */

namespace Drupal\views\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerialization;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a base class for single- or multistep view forms.
 *
 * This class only dispatches logic to the form for the current step. The form
 * is always assumed to be multistep, even if it has only one step (which by
 * default is \Drupal\views\Form\ViewsFormMainForm). That way it is actually
 * possible for modules to have a multistep form if they need to.
 */
class ViewsForm extends DependencySerialization implements FormInterface, ContainerInjectionInterface {

  /**
   * The class resolver to get the subform form objects.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The url generator to generate the form action.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The ID of the view.
   *
   * @var string
   */
  protected $viewId;

  /**
   * The ID of the active view's display.
   *
   * @var string
   */
  protected $viewDisplayId;

  /**
   * Constructs a ViewsForm object.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $controller_resolver
   *   The class resolver to get the subform form objects.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator to generate the form action.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $view_id
   *   The ID of the view.
   * @param string $view_display_id
   *   The ID of the active view's display.
   */
  public function __construct(ClassResolverInterface $controller_resolver, UrlGeneratorInterface $url_generator, Request $request, $view_id, $view_display_id) {
    $this->classResolver = $controller_resolver;
    $this->urlGenerator = $url_generator;
    $this->request = $request;
    $this->viewId = $view_id;
    $this->viewDisplayId = $view_display_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $view_id = NULL, $view_display_id = NULL) {
    return new static(
      $container->get('controller_resolver'),
      $container->get('url_generator'),
      $container->get('request'),
      $view_id,
      $view_display_id
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    $parts = array(
      'views_form',
      $this->viewId,
      $this->viewDisplayId,
    );

    return implode('_', $parts);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, ViewExecutable $view = NULL, $output = NULL) {
    $form_state['step'] = isset($form_state['step']) ? $form_state['step'] : 'views_form_views_form';
    $form_state['step_controller']['views_form_views_form'] = 'Drupal\views\Form\ViewsFormMainForm';

    // Cache the built form to prevent it from being rebuilt prior to validation
    // and submission, which could lead to data being processed incorrectly,
    // because the views rows (and thus, the form elements as well) have changed
    // in the meantime.
    $form_state['cache'] = TRUE;

    $form = array();

    $query = $this->request->query->all();
    $query = UrlHelper::filterQueryParameters($query, array(), '');

    $form['#action'] = $this->urlGenerator->generateFromPath($view->getUrl(), array('query' => $query));
    // Tell the preprocessor whether it should hide the header, footer, pager...
    $form['show_view_elements'] = array(
      '#type' => 'value',
      '#value' => ($form_state['step'] == 'views_form_views_form') ? TRUE : FALSE,
    );

    $form_object = $this->getFormObject($form_state);
    $form += $form_object->buildForm($form, $form_state, $view, $output);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $form_object = $this->getFormObject($form_state);
    $form_object->validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_object = $this->getFormObject($form_state);
    $form_object->submitForm($form, $form_state);
  }

  /**
   * Returns the object used to build the step form.
   *
   * @param array $form_state
   *   The form_state of the current form.
   *
   * @return \Drupal\Core\Form\FormInterface
   *   The form object to use.
   */
  protected function getFormObject(array $form_state) {
    // If this is a class, instantiate it.
    $form_step_class = isset($form_state['step_controller'][$form_state['step']]) ? $form_state['step_controller'][$form_state['step']] : 'Drupal\views\Form\ViewsFormMainForm';
    return $this->classResolver->getInstanceFromDefinition($form_step_class);
  }

}
