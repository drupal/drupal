<?php
// $Id$

/**
 * @file
 * User page callbacks for the drupal module.
 */


/**
 * Menu callback; print Drupal-authentication-specific information.
 */
function drupal_page_help() {
  return t('<p><a href="@Drupal">Drupal</a> is the name of the software that powers %this-site. There are Drupal websites all over the world, and many of them share their registration databases so that users may freely log in to any Drupal site using a single <strong>Drupal ID</strong>.</p>
<p>So please feel free to log in to your account here at %this-site with a username from another Drupal site. The format of a Drupal ID is similar to an e-mail address: <strong>username</strong>@<em>server</em>. An example of a valid Drupal ID is <strong>mwlily</strong>@<em>drupal.org</em>.</p>', array('@Drupal' => 'http://drupal.org', '%this-site' => variable_get('site_name', 'Drupal')));
}
