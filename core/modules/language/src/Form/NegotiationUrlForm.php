<?php

namespace Drupal\language\Form;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;

// cspell:ignore deutsch

/**
 * Configure the URL language negotiation method for this site.
 *
 * @internal
 */
class NegotiationUrlForm extends ConfigFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new NegotiationUrlForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, LanguageManagerInterface $language_manager) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_negotiation_configure_url_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['language.negotiation'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $config = $this->config('language.negotiation');

    $form['language_negotiation_url_part'] = [
      '#title' => $this->t('Part of the URL that determines language'),
      '#type' => 'radios',
      '#options' => [
        LanguageNegotiationUrl::CONFIG_PATH_PREFIX => $this->t('Path prefix'),
        LanguageNegotiationUrl::CONFIG_DOMAIN => $this->t('Domain'),
      ],
      '#default_value' => $config->get('url.source'),
    ];

    $form['prefix'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => $this->t('Path prefix configuration'),
      '#open' => TRUE,
      '#description' => $this->t('Language codes or other custom text to use as a path prefix for URL language detection. For the selected fallback language, this value may be left blank. <strong>Modifying this value may break existing URLs. Use with caution in a production environment.</strong> Example: Specifying "deutsch" as the path prefix code for German results in URLs like "example.com/deutsch/contact".'),
      '#states' => [
        'visible' => [
          ':input[name="language_negotiation_url_part"]' => [
            'value' => (string) LanguageNegotiationUrl::CONFIG_PATH_PREFIX,
          ],
        ],
      ],
    ];
    $form['domain'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => $this->t('Domain configuration'),
      '#open' => TRUE,
      '#description' => $this->t('The domain names to use for these languages. <strong>Modifying this value may break existing URLs. Use with caution in a production environment.</strong> Example: Specifying "de.example.com" as language domain for German will result in a URL like "http://de.example.com/contact".'),
      '#states' => [
        'visible' => [
          ':input[name="language_negotiation_url_part"]' => [
            'value' => (string) LanguageNegotiationUrl::CONFIG_DOMAIN,
          ],
        ],
      ],
    ];

    $languages = $this->languageManager->getLanguages();
    $prefixes = $config->get('url.prefixes');
    $domains = $config->get('url.domains');
    foreach ($languages as $langcode => $language) {
      $t_args = ['%language' => $language->getName(), '%langcode' => $language->getId()];
      $form['prefix'][$langcode] = [
        '#type' => 'textfield',
        '#title' => $language->isDefault() ? $this->t('%language (%langcode) path prefix (Default language)', $t_args) : $this->t('%language (%langcode) path prefix', $t_args),
        '#maxlength' => 64,
        '#default_value' => $prefixes[$langcode] ?? '',
        '#field_prefix' => $base_url . '/',
      ];
      $form['domain'][$langcode] = [
        '#type' => 'textfield',
        '#title' => $this->t('%language (%langcode) domain', ['%language' => $language->getName(), '%langcode' => $language->getId()]),
        '#maxlength' => 128,
        '#default_value' => $domains[$langcode] ?? '',
      ];
    }

    $form_state->setRedirect('language.negotiation');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $languages = $this->languageManager->getLanguages();

    // Count repeated values for uniqueness check.
    $count = array_count_values($form_state->getValue('prefix'));
    $default_langcode = $this->config('language.negotiation')->get('selected_langcode');
    if ($default_langcode == LanguageInterface::LANGCODE_SITE_DEFAULT) {
      $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    }
    foreach ($languages as $langcode => $language) {
      $value = $form_state->getValue(['prefix', $langcode]);
      if ($value === '') {
        if (!($default_langcode == $langcode) && $form_state->getValue('language_negotiation_url_part') == LanguageNegotiationUrl::CONFIG_PATH_PREFIX) {
          // Throw a form error if the prefix is blank for a non-default language,
          // although it is required for selected negotiation type.
          $form_state->setErrorByName("prefix][$langcode", $this->t('The prefix may only be left blank for the <a href=":url">selected detection fallback language.</a>', [
            ':url' => Url::fromRoute('language.negotiation_selected')->toString(),
          ]));
        }
      }
      elseif (str_contains($value, '/')) {
        // Throw a form error if the string contains a slash,
        // which would not work.
        $form_state->setErrorByName("prefix][$langcode", $this->t('The prefix may not contain a slash.'));
      }
      elseif (isset($count[$value]) && $count[$value] > 1) {
        // Throw a form error if there are two languages with the same
        // domain/prefix.
        $form_state->setErrorByName("prefix][$langcode", $this->t('The prefix for %language, %value, is not unique.', ['%language' => $language->getName(), '%value' => $value]));
      }
    }

    // Count repeated values for uniqueness check.
    $count = array_count_values($form_state->getValue('domain'));
    foreach ($languages as $langcode => $language) {
      $value = $form_state->getValue(['domain', $langcode]);

      if ($value === '') {
        if ($form_state->getValue('language_negotiation_url_part') == LanguageNegotiationUrl::CONFIG_DOMAIN) {
          // Throw a form error if the domain is blank for a non-default language,
          // although it is required for selected negotiation type.
          $form_state->setErrorByName("domain][$langcode", $this->t('The domain may not be left blank for %language.', ['%language' => $language->getName()]));
        }
      }
      elseif (isset($count[$value]) && $count[$value] > 1) {
        // Throw a form error if there are two languages with the same
        // domain/domain.
        $form_state->setErrorByName("domain][$langcode", $this->t('The domain for %language, %value, is not unique.', ['%language' => $language->getName(), '%value' => $value]));
      }
    }

    // Domain names should not contain protocol and/or ports.
    foreach ($languages as $langcode => $language) {
      $value = $form_state->getValue(['domain', $langcode]);
      if (!empty($value)) {
        // Ensure we have exactly one protocol when checking the hostname.
        $host = 'http://' . str_replace(['http://', 'https://'], '', $value);
        if (parse_url($host, PHP_URL_HOST) != $value) {
          $form_state->setErrorByName("domain][$langcode", $this->t('The domain for %language may only contain the domain name, not a trailing slash, protocol and/or port.', ['%language' => $language->getName()]));
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save selected format (prefix or domain).
    $this->config('language.negotiation')
      ->set('url.source', $form_state->getValue('language_negotiation_url_part'))
      // Save new domain and prefix values.
      ->set('url.prefixes', $form_state->getValue('prefix'))
      ->set('url.domains', $form_state->getValue('domain'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
