<?php

/**
 * @file
 * Contains \Drupal\views\Form\ViewsForm.
 */

namespace Drupal\views\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a base class for single- or multistep view forms.
 *
 * This class only dispatches logic to the form for the current step. The form
 * is always assumed to be multistep, even if it has only one step (which by
 * default is \Drupal\views\Form\ViewsFormMainForm). That way it is actually
 * possible for modules to have a multistep form if they need to.
 */
class ViewsForm implements FormInterface, ContainerInjectionInterface {
  use DependencySerializationTrait;

  /**
   * The class resolver to get the subform form objects.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

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
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver to get the subform form objects.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator to generate the form action.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param string $view_id
   *   The ID of the view.
   * @param string $view_display_id
   *   The ID of the active view's display.
   */
  public function __construct(ClassResolverInterface $class_resolver, UrlGeneratorInterface $url_generator, RequestStack $requestStack, $view_id, $view_display_id) {
    $this->classResolver = $class_resolver;
    $this->urlGenerator = $url_generator;
    $this->requestStack = $requestStack;
    $this->viewId = $view_id;
    $this->viewDisplayId = $view_display_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $view_id = NULL, $view_display_id = NULL) {
    return new static(
      $container->get('class_resolver'),
      $container->get('url_generator'),
      $container->get('request_stack'),
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
  public function buildForm(array $form, FormStateInterface $form_state, ViewExecutable $view = NULL, $output = []) {
    if (!$step = $form_state->get('step')) {
      $step = 'views_form_views_form';
      $form_state->set('step', $step);
    }
    $form_state->set(['step_controller', 'views_form_views_form'], 'Drupal\views\Form\ViewsFormMainForm');

    // Cache the built form to prevent it from being rebuilt prior to validation
    // and submission, which could lead to data being processed incorrectly,
    // because the views rows (and thus, the form elements as well) have changed
    // in the meantime.
    $form_state->setCached();

    $form = array();

    $query = $this->requestStack->getCurrentRequest()->query->all();
    $query = UrlHelper::filterQueryParameters($query, array(), '');

    $form['#action'] = $this->urlGenerator->generateFromPath($view->getUrl(), array('query' => $query));
    // Tell the preprocessor whether it should hide the header, footer, pager...
    $form['show_view_elements'] = array(
      '#type' => 'value',
      '#value' => ($step == 'views_form_views_form') ? TRUE : FALSE,
    );

    $form_object = $this->getFormObject($form_state);
    $form += $form_object->buildForm($form, $form_state, $view, $output);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_object = $this->getFormObject($form_state);
    $form_object->validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_object = $this->getFormObject($form_state);
    $form_object->submitForm($form, $form_state);
  }

  /**
   * Returns the object used to build the step form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state of the current form.
   *
   * @return \Drupal\Core\Form\FormInterface
   *   The form object to use.
   */
  protected function getFormObject(FormStateInterface $form_state) {
    // If this is a class, instantiate it.
    $form_step_class = $form_state->get(['step_controller', $form_state->get('step')]) ?: 'Drupal\views\Form\ViewsFormMainForm';
    return $this->classResolver->getInstanceFromDefinition($form_step_class);
  }

}
