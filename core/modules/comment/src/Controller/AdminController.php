<?php

/**
 * @file
 * Contains \Drupal\comment\Controller\AdminController.
 */

namespace Drupal\comment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for comment module administrative routes.
 */
class AdminController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Constructs an AdminController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * Presents an administrative comment listing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param string $type
   *   The type of the overview form ('approval' or 'new') default to 'new'.
   *
   * @return array
   *   Then comment multiple delete confirmation form or the comments overview
   *   administration form.
   */
  public function adminPage(Request $request, $type = 'new') {
    if ($request->request->get('operation') == 'delete' && $request->request->get('comments')) {
      return $this->formBuilder->getForm('\Drupal\comment\Form\ConfirmDeleteMultiple', $request);
    }
    else {
      return $this->formBuilder->getForm('\Drupal\comment\Form\CommentAdminOverview', $type);
    }
  }

}
