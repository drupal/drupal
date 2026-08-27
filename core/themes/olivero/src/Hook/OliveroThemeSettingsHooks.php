<?php

namespace Drupal\olivero\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for olivero.
 */
class OliveroThemeSettingsHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_system_theme_settings_alter')]
  public function formSystemThemeSettingsAlter(array &$form, FormStateInterface $form_state): void {
    $form['#attached']['library'][] = 'olivero/color-picker';

    $color_config = [
      'colors' => [
        'base_primary_color' => $this->t('Primary base color'),
      ],
      'schemes' => [
        'default' => [
          'label' => 'Blue Lagoon',
          'colors' => [
            'base_primary_color' => '#1b9ae4',
          ],
        ],
        'firehouse' => [
          'label' => 'Firehouse',
          'colors' => [
            'base_primary_color' => '#a30f0f',
          ],
        ],
        'ice' => [
          'label' => 'Ice',
          'colors' => [
            'base_primary_color' => '#57919e',
          ],
        ],
        'plum' => [
          'label' => 'Plum',
          'colors' => [
            'base_primary_color' => '#7a4587',
          ],
        ],
        'slate' => [
          'label' => 'Slate',
          'colors' => [
            'base_primary_color' => '#47625b',
          ],
        ],
      ],
    ];

    $form['#attached']['drupalSettings']['olivero']['colorSchemes'] = $color_config['schemes'];

    $form['olivero_settings']['olivero_utilities'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Olivero Utilities'),
    ];
    $form['olivero_settings']['olivero_utilities']['mobile_menu_all_widths'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable mobile menu at all widths'),
      '#config_target' => 'olivero.settings:mobile_menu_all_widths',
      '#description' => $this->t('Enables the mobile menu toggle at all widths.'),
    ];
    $form['olivero_settings']['olivero_utilities']['site_branding_bg_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Header site branding background color'),
      '#options' => [
        'default' => $this->t('Primary Branding Color'),
        'gray' => $this->t('Gray'),
        'white' => $this->t('White'),
      ],
      '#config_target' => 'olivero.settings:site_branding_bg_color',
    ];
    $form['olivero_settings']['olivero_utilities']['olivero_color_scheme'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Olivero Color Scheme Settings'),
    ];
    $form['olivero_settings']['olivero_utilities']['olivero_color_scheme']['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('These settings adjust the look and feel of the Olivero theme. Changing the color below will change the base hue, saturation, and lightness values the Olivero theme uses to determine its internal colors.'),
    ];
    $form['olivero_settings']['olivero_utilities']['olivero_color_scheme']['color_scheme'] = [
      '#type' => 'select',
      '#title' => $this->t('Olivero Color Scheme'),
      '#empty_option' => $this->t('Custom'),
      '#empty_value' => '',
      '#options' => [
        'default' => $this->t('Blue Lagoon (Default)'),
        'firehouse' => $this->t('Firehouse'),
        'ice' => $this->t('Ice'),
        'plum' => $this->t('Plum'),
        'slate' => $this->t('Slate'),
      ],
      '#input' => FALSE,
      '#wrapper_attributes' => [
        'style' => 'display:none;',
      ],
    ];

    foreach ($color_config['colors'] as $key => $title) {
      $form['olivero_settings']['olivero_utilities']['olivero_color_scheme'][$key] = [
        '#type' => 'textfield',
        '#maxlength' => 7,
        '#size' => 10,
        '#title' => $title,
        '#description' => $this->t('Enter color in hexadecimal format (#abc123).') . '<br/>' . $this->t('Derivatives will be formed from this color.'),
        '#config_target' => "olivero.settings:$key",
        '#attributes' => [
          // Regex copied from Color::validateHex()
          'pattern' => '^[#]?([0-9a-fA-F]{3}){1,2}$',
        ],
        '#wrapper_attributes' => [
          'data-drupal-selector' => 'olivero-color-picker',
        ],
      ];
    }
  }

}
