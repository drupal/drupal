<?php

namespace Drupal\drupal_autocomplete_test\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for testing autocomplete options.
 */
class AutocompleteTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupal_autocomplete_test_form';
  }

  /**
   * {@inheritdoc}
   *
   * This form tests the various options that can be used to configure an
   * instance of the A11yAutocomplete JavaScript class. Options can be set
   * directly on an element in two ways:
   * - Using a data-autocomplete-(dash separated option name) attribute.
   *   ex: data-autocomplete-min-chars="2"
   * - The data-autocomplete attribute has a JSON string with all custom
   *   options. The option properties are camel cased.
   *   ex: data-autocomplete="{"minChars": 2}"
   * Every option tested via this form has a version implemented via
   * data-autocomplete={option: value} and another version implemented via
   * data-autocomplete-(dash separated option name).
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Inputs with the minimum characters option.
    $form['two_minChar_data_autocomplete'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum 2 Characters data-autocomplete'),
      '#default_value' => '',
      '#description' => $this->t('This also tests appending minChar screenreader hints to descriptions'),
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete' => JSON::encode([
          'minChars' => 2,
        ]),
      ],
    ];
    $form['two_minChar_separate_data_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum 2 Characters data-min-char'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete-min-chars' => 2,
      ],
    ];

    // Inputs with the first character denylist option.
    $form['denylist_data_autocomplete'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Denylist "u" data-autocomplete'),
      '#default_value' => '',
      '#description' => $this->t('This also tests appending default screenreader hints to descriptions'),
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete' => JSON::encode([
          'firstCharacterDenylist' => 'u',
        ]),
      ],
    ];
    $form['denylist_separate_data_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Denylist "u" separate data attributes'),
      '#default_value' => '',
      '#description' => $this->t('This also tests appending default screenreader hints to descriptions'),
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete-first-character-denylist' => 'u',
      ],
    ];

    // Inputs that use options to add custom classes.
    $form['custom_classes_data_autocomplete'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom classes data-autocomplete'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete' => JSON::encode([
          'inputClass' => 'class-added-to-input another-class-added-to-input',
          'ulClass' => 'class-added-to-ul another-class-added-to-ul',
          'itemClass' => 'class-added-to-item another-class-added-to-item',
        ]),
      ],
      // This feature will only work on the non-shimmed autocomplete.
      '#use-core-autocomplete' => TRUE,
    ];
    $form['custom_classes_separate_data_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom classes separate data attributes'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete-input-class' => 'class-added-to-input another-class-added-to-input',
        'data-autocomplete-ul-class' => 'class-added-to-ul another-class-added-to-ul',
        'data-autocomplete-item-class' => 'class-added-to-item another-class-added-to-item',
      ],
      // This feature will only work on the non-shimmed autocomplete.
      '#use-core-autocomplete' => TRUE,
    ];

    // Inputs with set cardinality and a custom separator.
    $form['cardinality_separator_data_autocomplete'] = [
      '#type' => 'textfield',
      '#title' => $this->t('2 Cardinality data-autocomplete'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete' => JSON::encode([
          'cardinality' => '2',
          'separatorChar' => '|',
          'firstCharacterDenylist' => '|',
          'allowRepeatValues' => FALSE,
        ]),
      ],
    ];
    $form['cardinality_separator_separate_data_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('2 Cardinality separate data attributes'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete-cardinality' => '2',
        'data-autocomplete-separator-char' => '|',
        'data-autocomplete-first-character-deny-list' => '|',
        'data-autocomplete-allow-repeat-values' => 'false',
      ],
    ];

    // Inputs with custom max options instead of the default 10.
    $form['maxItems_data_autocomplete'] = [
      '#type' => 'textfield',
      '#title' => $this->t('10 Max items data-autocomplete'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete' => JSON::encode([
          'maxItems' => '10',
        ]),
      ],
    ];
    $form['maxItems_separate_data_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('10 Max items separate data attributes'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete-max-items' => '10',
      ],
    ];

    $custom_list = [
      [
        'label' => 'Zebra Label',
        'value' => 'Zebra Value',
      ],
      [
        'label' => 'Rhino Label',
        'value' => 'Rhino Value',
      ],
      [
        'label' => 'Cheetah Label',
        'value' => 'Cheetah Value',
      ],
      [
        'label' => 'Meerkat Label',
        'value' => 'Meerkat Value',
      ],
    ];

    // Inputs with a preset list instead of requesting it dynamically.
    $form['preset_list_data_autocomplete'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom list data-autocomplete'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete' => JSON::encode([
          'list' => $custom_list,
        ]),
      ],
    ];
    $form['preset_list_separate_data_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom list separate data attributes'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete-list' => JSON::encode($custom_list),
      ],
    ];

    // Inputs with the sort results option enabled.
    $form['sort_data_autocomplete'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sort data-autocomplete'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete' => JSON::encode([
          'list' => $custom_list,
          'sort' => TRUE,
        ]),
      ],
    ];
    $form['sort_separate_data_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sort separate data attributes'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete-list' => JSON::encode($custom_list),
        'data-autocomplete-sort' => TRUE,
      ],
    ];

    // Inputs with the option to display labels instead of values enabled.
    $form['display_labels_data_autocomplete'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display labels data-autocomplete'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete' => JSON::encode([
          'list' => $custom_list,
          'displayLabels' => TRUE,
        ]),
      ],
    ];
    $form['display_labels_data_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display labels data attributes'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#attributes' => [
        'data-autocomplete-list' => JSON::encode($custom_list),
        'data-autocomplete-display-labels' => 'true',
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.autocomplete';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Intentionally empty.
  }

}
