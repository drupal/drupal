<?php

/**
 * @file
 * Contains \Drupal\locale\Form\TranslateFormBase.
 */

namespace Drupal\locale\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Language\LanguageManager;
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
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /*
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
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   */
  public function __construct(StringStorageInterface $locale_storage, StateInterface $state, LanguageManager $language_manager) {
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
    $conditions = array('language' => $filter_values['langcode']);
    $options = array('pager limit' => 30, 'translated' => TRUE, 'untranslated' => TRUE);

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
   * @return array $filter_values
   *   The filter values.
   */
  protected function translateFilterValues($reset = FALSE) {
    if (!$reset && static::$filterValues) {
      return static::$filterValues;
    }

    $filter_values = array();
    $filters = $this->translateFilters();
    foreach ($filters as $key => $filter) {
      $filter_values[$key] = $filter['default'];
      // Let the filter defaults be overwritten by parameters in the URL.
      if ($this->getRequest()->query->has($key)) {
        // Only allow this value if it was among the options, or
        // if there were no fixed options to filter for.
        $value = $this->getRequest()->query->get($key);
        if (!isset($filter['options']) || isset($filter['options'][$value])) {
          $filter_values[$key] = $value;
        }
      }
      elseif (isset($_SESSION['locale_translate_filter'][$key])) {
        // Only allow this value if it was among the options, or
        // if there were no fixed options to filter for.
        if (!isset($filter['options']) || isset($filter['options'][$_SESSION['locale_translate_filter'][$key]])) {
          $filter_values[$key] = $_SESSION['locale_translate_filter'][$key];
        }
      }
    }

    return static::$filterValues = $filter_values;
  }

  /**
   * Lists locale translation filters that can be applied.
   */
  protected function translateFilters() {
    $filters = array();

    // Get all languages, except English.
    $this->languageManager->reset();
    $languages = language_list();
    $language_options = array();
    foreach ($languages as $langcode => $language) {
      if ($langcode != 'en' || locale_translate_english()) {
        $language_options[$langcode] = $language->name;
      }
    }

    // Pick the current interface language code for the filter.
    $default_langcode = $this->languageManager->getCurrentLanguage()->id;
    if (!isset($language_options[$default_langcode])) {
      $available_langcodes = array_keys($language_options);
      $default_langcode = array_shift($available_langcodes);
    }

    $filters['string'] = array(
      'title' => $this->t('String contains'),
      'description' => $this->t('Leave blank to show all strings. The search is case sensitive.'),
      'default' => '',
    );

    $filters['langcode'] = array(
      'title' => $this->t('Translation language'),
      'options' => $language_options,
      'default' => $default_langcode,
    );

    $filters['translation'] = array(
      'title' => $this->t('Search in'),
      'options' => array(
        'all' => $this->t('Both translated and untranslated strings'),
        'translated' => $this->t('Only translated strings'),
        'untranslated' => $this->t('Only untranslated strings'),
      ),
      'default' => 'all',
    );

    $filters['customized'] = array(
      'title' => $this->t('Translation type'),
      'options' => array(
        'all' => $this->t('All'),
        LOCALE_NOT_CUSTOMIZED => $this->t('Non-customized translation'),
        LOCALE_CUSTOMIZED => $this->t('Customized translation'),
      ),
      'states' => array(
        'visible' => array(
          ':input[name=translation]' => array('value' => 'translated'),
        ),
      ),
      'default' => 'all',
    );

    return $filters;
  }

}
