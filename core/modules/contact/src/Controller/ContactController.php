<?php

/**
 * @file
 * Contains \Drupal\contact\Controller\ContactController.
 */

namespace Drupal\contact\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Flood\FloodInterface;
use Drupal\contact\ContactFormInterface;
use Drupal\user\UserInterface;
use Drupal\Component\Utility\SafeMarkup;
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
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a ContactController object.
   *
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date service.
   */
  public function __construct(FloodInterface $flood, DateFormatter $date_formatter) {
    $this->flood = $flood;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flood'),
      $container->get('date.formatter')
    );
  }

  /**
   * Presents the site-wide contact form.
   *
   * @param \Drupal\contact\ContactFormInterface $contact_form
   *   The contact form to use.
   *
   * @return array
   *   The form as render array as expected by drupal_render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Exception is thrown when user tries to access non existing default
   *   contact form.
   */
  public function contactSitePage(ContactFormInterface $contact_form = NULL) {
    // Check if flood control has been activated for sending emails.
    if (!$this->currentUser()->hasPermission('administer contact forms')) {
      $this->contactFloodControl();
    }
    $config = $this->config('contact.settings');

    // Use the default form if no form has been passed.
    if (empty($contact_form)) {
      $contact_form = $this->entityManager()
        ->getStorage('contact_form')
        ->load($config->get('default_form'));
      // If there are no forms, do not display the form.
      if (empty($contact_form)) {
        if ($this->currentUser()->hasPermission('administer contact forms')) {
          drupal_set_message($this->t('The contact form has not been configured. <a href="@add">Add one or more forms</a> .', array(
            '@add' => $this->url('contact.form_add'))), 'error');
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
        'contact_form' => $contact_form->id(),
      ));

    $form = $this->entityFormBuilder()->getForm($message);
    $form['#title'] = SafeMarkup::checkPlain($contact_form->label());
    $form['#cache']['tags'] = Cache::mergeTags(isset($form['#cache']['tags']) ? $form['#cache']['tags'] : [],  $config->getCacheTags());
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
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Exception is thrown when user tries to access a contact form for a
   *   user who does not have an e-mail address configured.
   */
  public function contactPersonalPage(UserInterface $user) {
    // Do not continue if the user does not have an e-mail address configured.
    if (!$user->getEmail()) {
      throw new NotFoundHttpException();
    }

    // Check if flood control has been activated for sending emails.
    if (!$this->currentUser()->hasPermission('administer contact forms') && !$this->currentUser()->hasPermission('administer users')) {
      $this->contactFloodControl();
    }

    $message = $this->entityManager()->getStorage('contact_message')->create(array(
      'contact_form' => 'personal',
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
        '@interval' => $this->dateFormatter->formatInterval($interval),
      )), 'error');
      throw new AccessDeniedHttpException();
    }
  }

}
