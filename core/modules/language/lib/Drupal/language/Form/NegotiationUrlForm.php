<?php

/**
 * @file
 * Contains \Drupal\language\Form\NegotiationUrlForm.
 */

namespace Drupal\language\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;

/**
 * Configure the URL language negotiation method for this site.
 */
class NegotiationUrlForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_negotiation_configure_url_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    global $base_url;
    $config = $this->configFactory->get('language.negotiation');

    $form['language_negotiation_url_part'] = array(
      '#title' => t('Part of the URL that determines language'),
      '#type' => 'radios',
      '#options' => array(
        LanguageNegotiationUrl::CONFIG_PATH_PREFIX => t('Path prefix'),
        LanguageNegotiationUrl::CONFIG_DOMAIN => t('Domain'),
      ),
      '#default_value' => $config->get('url.source'),
    );

    $form['prefix'] = array(
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => t('Path prefix configuration'),
      '#open' => TRUE,
      '#description' => t('Language codes or other custom text to use as a path prefix for URL language detection. For the default language, this value may be left blank. <strong>Modifying this value may break existing URLs. Use with caution in a production environment.</strong> Example: Specifying "deutsch" as the path prefix code for German results in URLs like "example.com/deutsch/contact".'),
      '#states' => array(
        'visible' => array(
          ':input[name="language_negotiation_url_part"]' => array(
            'value' => (string) LanguageNegotiationUrl::CONFIG_PATH_PREFIX,
          ),
        ),
      ),
    );
    $form['domain'] = array(
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => t('Domain configuration'),
      '#open' => TRUE,
      '#description' => t('The domain names to use for these languages. Leave blank for the default language. Use with caution in a production environment.<strong>Modifying this value may break existing URLs. Use with caution in a production environment.</strong> Example: Specifying "de.example.com" as language domain for German will result in an URL like "http://de.example.com/contact".'),
      '#states' => array(
        'visible' => array(
          ':input[name="language_negotiation_url_part"]' => array(
            'value' => (string) LanguageNegotiationUrl::CONFIG_DOMAIN,
          ),
        ),
      ),
    );

    $languages = language_list();
    $prefixes = language_negotiation_url_prefixes();
    $domains = language_negotiation_url_domains();
    foreach ($languages as $langcode => $language) {
      $t_args = array('%language' => $language->name, '%langcode' => $language->id);
      $form['prefix'][$langcode] = array(
        '#type' => 'textfield',
        '#title' => $language->default ? t('%language (%langcode) path prefix (Default language)', $t_args) : t('%language (%langcode) path prefix', $t_args),
        '#maxlength' => 64,
        '#default_value' => isset($prefixes[$langcode]) ? $prefixes[$langcode] : '',
        '#field_prefix' => $base_url . '/',
      );
      $form['domain'][$langcode] = array(
        '#type' => 'textfield',
        '#title' => t('%language (%langcode) domain', array('%language' => $language->name, '%langcode' => $language->id)),
        '#maxlength' => 128,
        '#default_value' => isset($domains[$langcode]) ? $domains[$langcode] : '',
      );
    }

    $form_state['redirect_route']['route_name'] = 'language.negotiation';

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
    $languages = language_list();

    // Count repeated values for uniqueness check.
    $count = array_count_values($form_state['values']['prefix']);
    foreach ($languages as $langcode => $language) {
      $value = $form_state['values']['prefix'][$langcode];

      if ($value === '') {
        if (!$language->default && $form_state['values']['language_negotiation_url_part'] == LanguageNegotiationUrl::CONFIG_PATH_PREFIX) {
          // Throw a form error if the prefix is blank for a non-default language,
          // although it is required for selected negotiation type.
          $this->setFormError("prefix][$langcode", $form_state, t('The prefix may only be left blank for the default language.'));
        }
      }
      elseif (strpos($value, '/') !== FALSE) {
        // Throw a form error if the string contains a slash,
        // which would not work.
        $this->setFormError("prefix][$langcode", $form_state, t('The prefix may not contain a slash.'));
      }
      elseif (isset($count[$value]) && $count[$value] > 1) {
        // Throw a form error if there are two languages with the same
        // domain/prefix.
        $this->setFormError("prefix][$langcode", $form_state, t('The prefix for %language, %value, is not unique.', array('%language' => $language->name, '%value' => $value)));
      }
    }

    // Count repeated values for uniqueness check.
    $count = array_count_values($form_state['values']['domain']);
    foreach ($languages as $langcode => $language) {
      $value = $form_state['values']['domain'][$langcode];

      if ($value === '') {
        if (!$language->default && $form_state['values']['language_negotiation_url_part'] == LanguageNegotiationUrl::CONFIG_DOMAIN) {
          // Throw a form error if the domain is blank for a non-default language,
          // although it is required for selected negotiation type.
          $this->setFormError("domain][$langcode", $form_state, t('The domain may only be left blank for the default language.'));
        }
      }
      elseif (isset($count[$value]) && $count[$value] > 1) {
        // Throw a form error if there are two languages with the same
        // domain/domain.
        $this->setFormError("domain][$langcode", $form_state, t('The domain for %language, %value, is not unique.', array('%language' => $language->name, '%value' => $value)));
      }
    }

    // Domain names should not contain protocol and/or ports.
    foreach ($languages as $langcode => $name) {
      $value = $form_state['values']['domain'][$langcode];
      if (!empty($value)) {
        // Ensure we have exactly one protocol when checking the hostname.
        $host = 'http://' . str_replace(array('http://', 'https://'), '', $value);
        if (parse_url($host, PHP_URL_HOST) != $value) {
          $this->setFormError("domain][$langcode", $form_state, t('The domain for %language may only contain the domain name, not a protocol and/or port.', array('%language' => $name)));
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    // Save selected format (prefix or domain).
    $this->configFactory->get('language.negotiation')
      ->set('url.source', $form_state['values']['language_negotiation_url_part'])
      ->save();

    // Save new domain and prefix values.
    language_negotiation_url_prefixes_save($form_state['values']['prefix']);
    language_negotiation_url_domains_save($form_state['values']['domain']);

    parent::submitForm($form, $form_state);
  }

}
