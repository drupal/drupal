<?php

/**
 * @file
 * Builds placeholder replacement tokens system-wide data.
 *
 * This file handles tokens for the global 'site' and 'date' tokens.
 */

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;

/**
 * Implements hook_token_info().
 */
function system_token_info() {
  $types['site'] = array(
    'name' => t("Site information"),
    'description' => t("Tokens for site-wide settings and other global information."),
  );
  $types['date'] = array(
    'name' => t("Dates"),
    'description' => t("Tokens related to times and dates."),
  );

  // Site-wide global tokens.
  $site['name'] = array(
    'name' => t("Name"),
    'description' => t("The name of the site."),
  );
  $site['slogan'] = array(
    'name' => t("Slogan"),
    'description' => t("The slogan of the site."),
  );
  $site['mail'] = array(
    'name' => t("Email"),
    'description' => t("The administrative email address for the site."),
  );
  $site['url'] = array(
    'name' => t("URL"),
    'description' => t("The URL of the site's front page."),
  );
  $site['url-brief'] = array(
    'name' => t("URL (brief)"),
    'description' => t("The URL of the site's front page without the protocol."),
  );
  $site['login-url'] = array(
    'name' => t("Login page"),
    'description' => t("The URL of the site's login page."),
  );

  // Date related tokens.
  $date['short'] = array(
    'name' => t("Short format"),
    'description' => t("A date in 'short' format. (%date)", array('%date' => format_date(REQUEST_TIME, 'short'))),
  );
  $date['medium'] = array(
    'name' => t("Medium format"),
    'description' => t("A date in 'medium' format. (%date)", array('%date' => format_date(REQUEST_TIME, 'medium'))),
  );
  $date['long'] = array(
    'name' => t("Long format"),
    'description' => t("A date in 'long' format. (%date)", array('%date' => format_date(REQUEST_TIME, 'long'))),
  );
  $date['custom'] = array(
    'name' => t("Custom format"),
    'description' => t("A date in a custom format. See !php-date for details.", array('!php-date' => l(t('the PHP documentation'), 'http://php.net/manual/function.date.php'))),
  );
  $date['since'] = array(
    'name' => t("Time-since"),
    'description' => t("A date in 'time-since' format. (%date)", array('%date' => \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - 360, 2))),
  );
  $date['raw'] = array(
    'name' => t("Raw timestamp"),
    'description' => t("A date in UNIX timestamp format (%date)", array('%date' => REQUEST_TIME)),
  );

  return array(
    'types' => $types,
    'tokens' => array(
      'site' => $site,
      'date' => $date,
    ),
  );
}

/**
 * Implements hook_tokens().
 */
function system_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $token_service = \Drupal::token();

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = language_load($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $sanitize = !empty($options['sanitize']);

  $replacements = array();

  if ($type == 'site') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'name':
          $site_name = \Drupal::config('system.site')->get('name');
          $replacements[$original] = $sanitize ? String::checkPlain($site_name) : $site_name;
          break;

        case 'slogan':
          $slogan = \Drupal::config('system.site')->get('slogan');
          $replacements[$original] = $sanitize ? Xss::filterAdmin($slogan) : $slogan;
          break;

        case 'mail':
          $replacements[$original] = \Drupal::config('system.site')->get('mail');
          break;

        case 'url':
          $replacements[$original] = url('<front>', $url_options);
          break;

        case 'url-brief':
          $replacements[$original] = preg_replace(array('!^https?://!', '!/$!'), '', url('<front>', $url_options));
          break;

        case 'login-url':
          $replacements[$original] = url('user', $url_options);
          break;
      }
    }
  }

  elseif ($type == 'date') {
    if (empty($data['date'])) {
      $date = REQUEST_TIME;
    }
    else {
      $date = $data['date'];
    }

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'short':
          $replacements[$original] = format_date($date, 'short', '', NULL, $langcode);
          break;

        case 'medium':
          $replacements[$original] = format_date($date, 'medium', '', NULL, $langcode);
          break;

        case 'long':
          $replacements[$original] = format_date($date, 'long', '', NULL, $langcode);
          break;

        case 'since':
          $replacements[$original] = \Drupal::service('date.formatter')->formatInterval((REQUEST_TIME - $date), 2, $langcode);
          break;

        case 'raw':
          $replacements[$original] = $sanitize ? String::checkPlain($date) : $date;
          break;
      }
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'custom')) {
      foreach ($created_tokens as $name => $original) {
        $replacements[$original] = format_date($date, 'custom', $name, NULL, $langcode);
      }
    }
  }

  return $replacements;
}
