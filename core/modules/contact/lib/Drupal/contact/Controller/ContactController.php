<?php

/**
 * @file
 * Contains \Drupal\contact\Controller\ContactController.
 */

namespace Drupal\contact\Controller;

use Drupal\contact\CategoryInterface;
use Drupal\user\UserInterface;

/**
 * Controller routines for contact routes.
 */
class ContactController {

  /**
   * @todo Remove contact_site_page().
   */
  public function contactSitePage(CategoryInterface $contact_category = NULL) {
    module_load_include('pages.inc', 'contact');
    return contact_site_page($contact_category);
  }

  /**
   * @todo Remove contact_personal_page().
   */
  public function contactPersonalPage(UserInterface $user) {
    module_load_include('pages.inc', 'contact');
    return contact_personal_page($user);
  }

}
