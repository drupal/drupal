<?php

/**
 * @file
 * Builds placeholder replacement tokens system-wide data.
 *
 * This file handles tokens for the global 'site' and 'date' tokens.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Render\BubbleableMetadata;

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
    'description' => t('A date in a custom format. See <a href="http://php.net/manual/function.date.php">the PHP documentation</a> for details.'),
  );
  $date['since'] = array(
    'name' => t("Time-since"),
    'description' => t("A date in 'time-since' format. (%date)", array('%date' => \Drupal::service('date.formatter')->formatTimeDiffSince(REQUEST_TIME - 360))),
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
function system_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $replacements = array();

  if ($type == 'site') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'name':
          $config = \Drupal::config('system.site');
          $bubbleable_metadata->addCacheableDependency($config);
          $site_name = $config->get('name');
          $replacements[$original] = $site_name;
          break;

        case 'slogan':
          $config = \Drupal::config('system.site');
          $bubbleable_metadata->addCacheableDependency($config);
          $slogan = $config->get('slogan');
          $build = [
            '#markup' => $slogan,
          ];
          // @todo Fix in https://www.drupal.org/node/2577827
          $replacements[$original] = \Drupal::service('renderer')->renderPlain($build);
          break;

        case 'mail':
          $config = \Drupal::config('system.site');
          $bubbleable_metadata->addCacheableDependency($config);
          $replacements[$original] = $config->get('mail');
          break;

        case 'url':
          /** @var \Drupal\Core\GeneratedUrl $result */
          $result = \Drupal::url('<front>', array(), $url_options, TRUE);
          $bubbleable_metadata->addCacheableDependency($result);
          $replacements[$original] = $result->getGeneratedUrl();
          break;

        case 'url-brief':
          /** @var \Drupal\Core\GeneratedUrl $result */
          $result = \Drupal::url('<front>', array(), $url_options, TRUE);
          $bubbleable_metadata->addCacheableDependency($result);
          $replacements[$original] = preg_replace(array('!^https?://!', '!/$!'), '', $result->getGeneratedUrl());
          break;

        case 'login-url':
          /** @var \Drupal\Core\GeneratedUrl $result */
          $result = \Drupal::url('user.page', [], $url_options, TRUE);
          $bubbleable_metadata->addCacheableDependency($result);
          $replacements[$original] = $result->getGeneratedUrl();
          break;
      }
    }
  }

  elseif ($type == 'date') {
    if (empty($data['date'])) {
      $date = REQUEST_TIME;
      // We depend on the current request time, so the tokens are not cacheable
      // at all.
      $bubbleable_metadata->setCacheMaxAge(0);
    }
    else {
      $date = $data['date'];
    }

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'short':
        case 'medium':
        case 'long':
          $date_format = DateFormat::load($name);
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($date, $name, '', NULL, $langcode);
          break;

        case 'since':
          $replacements[$original] = \Drupal::service('date.formatter')->formatTimeDiffSince($date, array('langcode' => $langcode));
          $bubbleable_metadata->setCacheMaxAge(0);
          break;

        case 'raw':
          $replacements[$original] = $date;
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
