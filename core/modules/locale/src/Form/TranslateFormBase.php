<?php

namespace Drupal\locale\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\locale\StringStorageInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the locale user interface translation form base.
 *
 * Provides methods for searching and filtering strings.
 */
abstract class TranslateFormBase extends FormBase {

  /**
   * The locale storage.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $localeStorage;

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Filter values. Shared between objects that inherit this class.
   *
   * @var array|null
   */
  protected static $filterValues;

  /**
   * Constructs a new TranslationFormBase object.
   *
   * @param \Drupal\locale\StringStorageInterface $locale_storage
   *   The locale storage.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(StringStorageInterface $locale_storage, StateInterface $state, LanguageManagerInterface $language_manager) {
    $this->localeStorage = $locale_storage;
    $this->state = $state;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('locale.storage'),
      $container->get('state'),
      $container->get('language_manager')
    );
  }

  /**
   * Builds a string search query and returns an array of string objects.
   *
   * @return \Drupal\locale\TranslationString[]
   *   Array of \Drupal\locale\TranslationString objects.
   */
  protected function translateFilterLoadStrings() {
    $filter_values = $this->translateFilterValues();

    // Language is sanitized to be one of the possible options in
    // translateFilterValues().
    $conditions = ['language' => $filter_values['langcode']];
    $options = ['pager limit' => 30, 'translated' => TRUE, 'untranslated' => TRUE];

    // Add translation status conditions and options.
    switch ($filter_values['translation']) {
      case 'translated':
        $conditions['translated'] = TRUE;
        if ($filter_values['customized'] != 'all') {
          $conditions['customized'] = $filter_values['customized'];
        }
        break;

      case 'untranslated':
        $conditions['translated'] = FALSE;
        break;

    }

    if (!empty($filter_values['string'])) {
      $options['filters']['source'] = $filter_values['string'];
      if ($options['translated']) {
        $options['filters']['translation'] = $filter_values['string'];
      }
    }

    return $this->localeStorage->getTranslations($conditions, $options);
  }

  /**
   * Builds an array out of search criteria specified in request variables.
   *
   * @param bool $reset
   *   If the list of values should be reset.
   *
   * @return array
   *   The filter values.
   */
  protected function translateFilterValues($reset = FALSE) {
    if (!$reset && static::$filterValues) {
      return static::$filterValues;
    }

    $filter_values = [];
    $filters = $this->translateFilters();
    $request = $this->getRequest();
    $session_filters = $request->getSession()->get('locale_translate_filter', []);
    foreach ($filters as $key => $filter) {
      $filter_values[$key] = $filter['default'];
      // Let the filter defaults be overwritten by parameters in the URL.
      if ($request->query->has($key)) {
        // Only allow this value if it was among the options, or
        // if there were no fixed options to filter for.
        $value = $request->query->get($key);
        if (!isset($filter['options']) || isset($filter['options'][$value])) {
          $filter_values[$key] = $value;
        }
      }
      elseif (isset($session_filters[$key])) {
        // Only allow this value if it was among the options, or
        // if there were no fixed options to filter for.
        if (!isset($filter['options']) || isset($filter['options'][$session_filters[$key]])) {
          $filter_values[$key] = $session_filters[$key];
        }
      }
    }

    return static::$filterValues = $filter_values;
  }

  /**
   * Lists locale translation filters that can be applied.
   */
  protected function translateFilters() {
    $filters = [];

    // Get all languages, except English.
    $this->languageManager->reset();
    $languages = $this->languageManager->getLanguages();
    $language_options = [];
    foreach ($languages as $langcode => $language) {
      if (locale_is_translatable($langcode)) {
        $language_options[$langcode] = $language->getName();
      }
    }

    // Pick the current interface language code for the filter.
    $default_langcode = $this->languageManager->getCurrentLanguage()->getId();
    if (!isset($language_options[$default_langcode])) {
      $available_langcodes = array_keys($language_options);
      $default_langcode = array_shift($available_langcodes);
    }

    $filters['string'] = [
      'title' => $this->t('String contains'),
      'description' => $this->t('Leave blank to show all strings. The search is case sensitive.'),
      'default' => '',
    ];

    $filters['langcode'] = [
      'title' => $this->t('Translation language'),
      'options' => $language_options,
      'default' => $default_langcode,
    ];

    $filters['translation'] = [
      'title' => $this->t('Search in'),
      'options' => [
        'all' => $this->t('Both translated and untranslated strings'),
        'translated' => $this->t('Only translated strings'),
        'untranslated' => $this->t('Only untranslated strings'),
      ],
      'default' => 'all',
    ];

    $filters['customized'] = [
      'title' => $this->t('Translation type'),
      'options' => [
        'all' => $this->t('All'),
        LOCALE_NOT_CUSTOMIZED => $this->t('Non-customized translation'),
        LOCALE_CUSTOMIZED => $this->t('Customized translation'),
      ],
      'states' => [
        'visible' => [
          ':input[name=translation]' => ['value' => 'translated'],
        ],
      ],
      'default' => 'all',
    ];

    return $filters;
  }

}
