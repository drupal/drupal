<?php

/**
 * @file
 * Definition of Views\contact\Plugin\views\field\ContactLink.
 */

namespace Views\contact\Plugin\views\field;

use Views\user\Plugin\views\field\Link;
use Drupal\Core\Annotation\Plugin;

/**
 * A field that links to the user contact page, if access is permitted.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "contact_link",
 *   module = "contact"
 * )
 */
class ContactLink extends Link {

  public function buildOptionsForm(&$form, &$form_state) {
    $form['text']['#title'] = t('Link label');
    $form['text']['#required'] = TRUE;
    $form['text']['#default_value'] = empty($this->options['text']) ? t('contact') : $this->options['text'];
    parent::buildOptionsForm($form, $form_state);
  }

  // An example of field level access control.
  // We must override the access method in the parent class, as that requires
  // the 'access user profiles' permission, which the contact form does not.
  public function access() {
    return user_access('access user contact forms');
  }

  function render_link($data, $values) {
    global $user;
    $uid = $this->get_value($values, 'uid');

    if (empty($uid)) {
      return;
    }

    $account = user_load($uid);
    if (empty($account)) {
      return;
    }

    // Check access when we pull up the user account so we know
    // if the user has made the contact page available.
    $menu_item = menu_get_item("user/$uid/contact");
    if (!$menu_item['access'] || empty($account->data['contact'])) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = 'user/' . $account->uid . '/contact';
    $this->options['alter']['attributes'] = array('title' => t('Contact %user', array('%user' => $account->name)));

    $text = $this->options['text'];

    return $text;
  }

}
