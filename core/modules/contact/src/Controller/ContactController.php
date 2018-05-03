<?php

namespace Drupal\contact\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\contact\ContactFormInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for contact routes.
 */
class ContactController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a ContactController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Presents the site-wide contact form.
   *
   * @param \Drupal\contact\ContactFormInterface $contact_form
   *   The contact form to use.
   *
   * @return array
   *   The form as render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Exception is thrown when user tries to access non existing default
   *   contact form.
   */
  public function contactSitePage(ContactFormInterface $contact_form = NULL) {
    $config = $this->config('contact.settings');

    // Use the default form if no form has been passed.
    if (empty($contact_form)) {
      $contact_form = $this->entityManager()
        ->getStorage('contact_form')
        ->load($config->get('default_form'));
      // If there are no forms, do not display the form.
      if (empty($contact_form)) {
        if ($this->currentUser()->hasPermission('administer contact forms')) {
          $this->messenger()->addError($this->t('The contact form has not been configured. <a href=":add">Add one or more forms</a> .', [
            ':add' => $this->url('contact.form_add'),
          ]));
          return [];
        }
        else {
          throw new NotFoundHttpException();
        }
      }
    }

    $message = $this->entityManager()
      ->getStorage('contact_message')
      ->create([
        'contact_form' => $contact_form->id(),
      ]);

    $form = $this->entityFormBuilder()->getForm($message);
    $form['#title'] = $contact_form->label();
    $form['#cache']['contexts'][] = 'user.permissions';
    $this->renderer->addCacheableDependency($form, $config);
    return $form;
  }

  /**
   * Form constructor for the personal contact form.
   *
   * @param \Drupal\user\UserInterface $user
   *   The account for which a personal contact form should be generated.
   *
   * @return array
   *   The personal contact form as render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Exception is thrown when user tries to access a contact form for a
   *   user who does not have an email address configured.
   */
  public function contactPersonalPage(UserInterface $user) {
    // Do not continue if the user does not have an email address configured.
    if (!$user->getEmail()) {
      throw new NotFoundHttpException();
    }

    $message = $this->entityManager()->getStorage('contact_message')->create([
      'contact_form' => 'personal',
      'recipient' => $user->id(),
    ]);

    $form = $this->entityFormBuilder()->getForm($message);
    $form['#title'] = $this->t('Contact @username', ['@username' => $user->getDisplayName()]);
    $form['#cache']['contexts'][] = 'user.permissions';
    return $form;
  }

}
