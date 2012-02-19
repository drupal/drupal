<?php

/**
 * @file
 * Initializes the list of date formats and their locales.
 */

/**
 * Provides a default system list of date formats for system_date_formats().
 */
function system_default_date_formats() {
  $formats = array();

  // Short date formats.
  $formats[] = array(
    'type' => 'short',
    'format' => 'Y-m-d H:i',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'short',
    'format' => 'm/d/Y - H:i',
    'locales' => array('en-us'),
  );
  $formats[] = array(
    'type' => 'short',
    'format' => 'd/m/Y - H:i',
    'locales' => array('en-gb', 'en-hk', 'en-ie', 'el-gr', 'es-es', 'fr-be', 'fr-fr', 'fr-lu', 'it-it', 'nl-be', 'pt-pt'),
  );
  $formats[] = array(
    'type' => 'short',
    'format' => 'Y/m/d - H:i',
    'locales' => array('en-ca', 'fr-ca', 'no-no', 'sv-se'),
  );
  $formats[] = array(
    'type' => 'short',
    'format' => 'd.m.Y - H:i',
    'locales' => array('de-ch', 'de-de', 'de-lu', 'fi-fi', 'fr-ch', 'is-is', 'pl-pl', 'ro-ro', 'ru-ru'),
  );
  $formats[] = array(
    'type' => 'short',
    'format' => 'm/d/Y - g:ia',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'short',
    'format' => 'd/m/Y - g:ia',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'short',
    'format' => 'Y/m/d - g:ia',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'short',
    'format' => 'M j Y - H:i',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'short',
    'format' => 'j M Y - H:i',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'short',
    'format' => 'Y M j - H:i',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'short',
    'format' => 'M j Y - g:ia',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'short',
    'format' => 'j M Y - g:ia',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'short',
    'format' => 'Y M j - g:ia',
    'locales' => array(),
  );

  // Medium date formats.
  $formats[] = array(
    'type' => 'medium',
    'format' => 'D, Y-m-d H:i',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'medium',
    'format' => 'D, m/d/Y - H:i',
    'locales' => array('en-us'),
  );
  $formats[] = array(
    'type' => 'medium',
    'format' => 'D, d/m/Y - H:i',
    'locales' => array('en-gb', 'en-hk', 'en-ie', 'el-gr', 'es-es', 'fr-be', 'fr-fr', 'fr-lu', 'it-it', 'nl-be', 'pt-pt'),
  );
  $formats[] = array(
    'type' => 'medium',
    'format' => 'D, Y/m/d - H:i',
    'locales' => array('en-ca', 'fr-ca', 'no-no', 'sv-se'),
  );
  $formats[] = array(
    'type' => 'medium',
    'format' => 'F j, Y - H:i',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'medium',
    'format' => 'j F, Y - H:i',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'medium',
    'format' => 'Y, F j - H:i',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'medium',
    'format' => 'D, m/d/Y - g:ia',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'medium',
    'format' => 'D, d/m/Y - g:ia',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'medium',
    'format' => 'D, Y/m/d - g:ia',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'medium',
    'format' => 'F j, Y - g:ia',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'medium',
    'format' => 'j F Y - g:ia',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'medium',
    'format' => 'Y, F j - g:ia',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'medium',
    'format' => 'j. F Y - G:i',
    'locales' => array(),
  );

  // Long date formats.
  $formats[] = array(
    'type' => 'long',
    'format' => 'l, F j, Y - H:i',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'long',
    'format' => 'l, j F, Y - H:i',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'long',
    'format' => 'l, Y,  F j - H:i',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'long',
    'format' => 'l, F j, Y - g:ia',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'long',
    'format' => 'l, j F Y - g:ia',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'long',
    'format' => 'l, Y,  F j - g:ia',
    'locales' => array(),
  );
  $formats[] = array(
    'type' => 'long',
    'format' => 'l, j. F Y - G:i',
    'locales' => array(),
  );

  return $formats;
}
