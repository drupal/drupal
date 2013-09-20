<?php

/**
 * @file
 * Page callbacks for the Contact module.
 */

use Drupal\contact\Entity\Category;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Component\Utility\String;

/**
 * Page callback: Presents the site-wide contact form.
 *
 * @param Drupal\contact\Entity\Category $category
 *   (optional) The contact category to use.
 *
 * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
 *
 * @see contact_menu()
 * @see contact_site_form_submit()
 * @ingroup forms
 *
 * @deprecated Use \Drupal\contact\Controller\ContactController::contactSitePage()
 */
function contact_site_page(Category $category = NULL) {
  // Check if flood control has been activated for sending e-mails.
  if (!user_access('administer contact forms')) {
    contact_flood_control();
  }

  if (!isset($category)) {
    $categories = entity_load_multiple('contact_category');
    $default_category = \Drupal::config('contact.settings')->get('default_category');
    if (isset($categories[$default_category])) {
      $category = $categories[$default_category];
    }
    // If there are no categories, do not display the form.
    else {
      if (user_access('administer contact forms')) {
        drupal_set_message(t('The contact form has not been configured. <a href="@add">Add one or more categories</a> to the form.', array('@add' => url('admin/structure/contact/add'))), 'error');
        return array();
      }
      else {
        throw new NotFoundHttpException();
      }
    }
  }
  else if ($category->id() == 'personal') {
    throw new NotFoundHttpException();
  }
  $message = entity_create('contact_message', array(
    'category' => $category->id(),
  ));
  $form = \Drupal::entityManager()->getForm($message);
  $form['#title'] = String::checkPlain($category->label());
  return $form;
}

/**
 * Page callback: Form constructor for the personal contact form.
 *
 * @param $recipient
 *   The account for which a personal contact form should be generated.
 *
 * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
 *
 * @see contact_menu()
 * @see contact_personal_form_submit()
 *
 * @ingroup forms
 *
 * @deprecated Use \Drupal\contact\Controller\ContactController::contactPersonalPage()
 */
function contact_personal_page($recipient) {
  global $user;

  // Check if flood control has been activated for sending e-mails.
  if (!user_access('administer contact forms') && !user_access('administer users')) {
    contact_flood_control();
  }

  $message = entity_create('contact_message', array(
    'recipient' => $recipient,
    'category' => 'personal',
  ));
  $form = \Drupal::entityManager()->getForm($message);
  $form['#title'] = t('Contact @username', array('@username' => $recipient->getUsername()));
  return $form;
}

/**
 * Throws an exception if the current user is not allowed to submit a contact form.
 *
 * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
 *
 * @see contact_site_page()
 * @see contact_personal_page()
 */
function contact_flood_control() {
  $config = \Drupal::config('contact.settings');
  $limit = $config->get('flood.limit');
  $interval = $config->get('flood.interval');
  if (!\Drupal::service('flood')->isAllowed('contact', $limit, $interval)) {
    drupal_set_message(t("You cannot send more than %limit messages in @interval. Try again later.", array(
      '%limit' => $limit,
      '@interval' => format_interval($interval),
    )), 'error');
    throw new AccessDeniedHttpException();
  }
}
