<?php

/**
 * @file
 * Contains \Drupal\contact\Controller\ContactController.
 */

namespace Drupal\contact\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Flood\FloodInterface;
use Drupal\contact\CategoryInterface;
use Drupal\user\UserInterface;
use Drupal\Component\Utility\String;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for contact routes.
 */
class ContactController extends ControllerBase {

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * Constructs a ContactController object.
   *
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   */
  public function __construct(FloodInterface $flood) {
    $this->flood = $flood;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flood')
    );
  }

  /**
   * Presents the site-wide contact form.
   *
   * @param \Drupal\contact\CategoryInterface $contact_category
   *   The contact category to use.
   *
   * @return array
   *   The form as render array as expected by drupal_render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Exception is thrown when user tries to access non existing default
   *   contact category form.
   */
  public function contactSitePage(CategoryInterface $contact_category = NULL) {
    // Check if flood control has been activated for sending e-mails.
    if (!$this->currentUser()->hasPermission('administer contact forms')) {
      $this->contactFloodControl();
    }

    // Use the default category if no category has been passed.
    if (empty($contact_category)) {
      $contact_category = $this->entityManager()
        ->getStorage('contact_category')
        ->load($this->config('contact.settings')->get('default_category'));
      // If there are no categories, do not display the form.
      if (empty($contact_category)) {
        if ($this->currentUser()->hasPermission('administer contact forms')) {
          drupal_set_message($this->t('The contact form has not been configured. <a href="@add">Add one or more categories</a> to the form.', array(
            '@add' => $this->urlGenerator()->generateFromRoute('contact.category_add'))), 'error');
          return array();
        }
        else {
          throw new NotFoundHttpException();
        }
      }
    }

    $message = $this->entityManager()
      ->getStorage('contact_message')
      ->create(array(
        'category' => $contact_category->id(),
      ));

    $form = $this->entityFormBuilder()->getForm($message);
    $form['#title'] = String::checkPlain($contact_category->label());
    return $form;
  }

  /**
   * Form constructor for the personal contact form.
   *
   * @param \Drupal\user\UserInterface $user
   *   The account for which a personal contact form should be generated.
   *
   * @return array
   *   The personal contact form as render array as expected by drupal_render().
   */
  public function contactPersonalPage(UserInterface $user) {
    // Check if flood control has been activated for sending e-mails.
    if (!$this->currentUser()->hasPermission('administer contact forms') && !$this->currentUser()->hasPermission('administer users')) {
      $this->contactFloodControl();
    }

    $message = $this->entityManager()->getStorage('contact_message')->create(array(
      'category' => 'personal',
      'recipient' => $user->id(),
    ));

    $form = $this->entityFormBuilder()->getForm($message);
    $form['#title'] = $this->t('Contact @username', array('@username' => $user->getUsername()));
    return $form;
  }

  /**
   * Throws an exception if the current user triggers flood control.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  protected function contactFloodControl() {
    $limit = $this->config('contact.settings')->get('flood.limit');
    $interval = $this->config('contact.settings')->get('flood.interval');
    if (!$this->flood->isAllowed('contact', $limit, $interval)) {
      drupal_set_message($this->t('You cannot send more than %limit messages in @interval. Try again later.', array(
        '%limit' => $limit,
        '@interval' => format_interval($interval),
      )), 'error');
      throw new AccessDeniedHttpException();
    }
  }

}
