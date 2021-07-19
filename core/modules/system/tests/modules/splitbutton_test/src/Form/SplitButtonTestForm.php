<?php

namespace Drupal\splitbutton_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form for testing splitbuttons.
 */
class SplitButtonTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'splitbutton_test_form';
  }

  /**
   * Returns a renderable array for a test page.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $disabled = FALSE) {
    $button_types = [
      'default' => 'Default',
      'primary' => 'Primary',
      'danger' => 'Danger',
      'small' => 'Small',
      'extrasmall' => 'Extra Small',
    ];

    $links_dropbutton = [
      'link_one' => [
        'title' => $this->t('Link One'),
        'url' => Url::fromRoute('splitbutton.test_link_1'),
      ],
      'link_two' => [
        'title' => $this->t('Link Two'),
        'url' => Url::fromRoute('splitbutton.test_link_2'),
      ],
      'link_three' => [
        'title' => $this->t('Link Three'),
        'url' => Url::fromRoute('splitbutton.test_link_3'),
      ],
      'link_four' => [
        'title' => $this->t('Link Four'),
        'url' => Url::fromRoute('splitbutton.test_link_4'),
      ],
    ];

    $links_plus_button = [
      'link_one' => [
        '#type' => 'link',
        '#title' => $this->t('Link One'),
        '#url' => Url::fromRoute('splitbutton.test_link_1'),
      ],
      'link_two' => [
        '#type' => 'link',
        '#title' => $this->t('Link Two'),
        '#url' => Url::fromRoute('splitbutton.test_link_2'),
      ],
      'link_three' => [
        '#type' => 'link',
        '#title' => $this->t('Link Three'),
        '#url' => Url::fromRoute('splitbutton.test_link_3'),
      ],
      'link_four' => [
        '#type' => 'link',
        '#title' => $this->t('Link Four'),
        '#url' => Url::fromRoute('splitbutton.test_link_4'),
      ],
      'added_button' => [
        '#type' => 'button',
        '#value' => $this->t('Added Button'),
      ],
      'another_added_button' => [
        '#type' => 'submit',
        '#value' => $this->t('Another Added Button'),
      ],
    ];

    $links_starts_with_button = [
      'start_button' => [
        '#type' => 'submit',
        '#value' => $this->t('Beginning Button'),
      ],
    ] + $links_plus_button;

    $scenarios = [
      'splitbutton_link_first' => [
        'title' => 'Splitbuttons with link as first item',
        'element_type' => 'splitbutton',
        'list' => $links_plus_button,
      ],
      'splitbutton_submit_first' => [
        'title' => 'Splitbuttons with submit as first item',
        'element_type' => 'splitbutton',
        'list' => $links_starts_with_button,
      ],
      'splitbutton_with_title' => [
        'title' => 'Splitbuttons where the primary button is just a toggle',
        'element_type' => 'splitbutton',
        'list' => $links_plus_button,
        'splitbutton_title' => 'Toggle Only',
      ],
      'dropbutton_converted' => [
        'title' => 'Dropbuttons converted to Splitbuttons by changing #type',
        'element_type' => 'splitbutton',
        'list' => $links_dropbutton,
        'use_links' => TRUE,
      ],
    ];

    foreach ($scenarios as $scenario_key => $scenario) {
      $form[$scenario_key] = [
        '#type' => 'container',
      ];
      $form[$scenario_key]['title'] = [
        '#type' => 'item',
        '#name' => $scenario_key,
        '#title' => $scenario['title'],
      ];
      foreach ($button_types as $button_type => $button_type_label) {
        $form[$scenario_key][$button_type] = [
          '#type' => $scenario['element_type'],
          '#attributes' => [
            'data-splitbutton-test-id' => "$scenario_key-$button_type",
          ],
        ];
        if (empty($scenario['splitbutton_title'])) {
          $first_item_key = key($scenario['list']);
          if (isset($scenario['list'][$first_item_key]['#type'])) {
            $first_item_type = $scenario['list'][$first_item_key]['#type'] ?? 'link';
            $label_key = $first_item_type === 'link' ? '#title' : '#value';
            $scenario['list'][$first_item_key][$label_key] = "$button_type_label - $first_item_type ";
            if ($first_item_type === 'link') {
              $scenario['list'][$first_item_key]['#url'] = Url::fromRoute('splitbutton.test', ['prevent_generated_link' => microtime()]);
            }
          }
          else {
            $scenario['list'][$first_item_key]['title'] = "$button_type_label - link";
          }
        }
        else {
          $form[$scenario_key][$button_type]['#title'] = "{$scenario['splitbutton_title']} - $button_type_label";
        }

        if ($scenario['element_type'] === 'splitbutton') {
          if ($button_type !== 'default') {
            $form[$scenario_key][$button_type]['#splitbutton_type'] = $button_type;
          }
        }
        else {
          $form[$scenario_key][$button_type]['#dropbutton_type'] = $button_type;
        }

        if (!empty($scenario['use_links'])) {
          $scenario['list'][key($scenario['list'])]['url'] = Url::fromRoute('splitbutton.test', ['prevent_generated_link' => microtime()]);
          $form[$scenario_key][$button_type]['#links'] = $scenario['list'];
        }
        else {
          $form[$scenario_key][$button_type]['#splitbutton_items'] = $scenario['list'];
        }

      }
    }

    $form['combined'] = [
      '#type' => 'container',
    ];
    $form['combined']['title'] = [
      '#type' => 'item',
      '#name' => 'combined_types',
      '#title' => $this->t('Combined types'),
    ];
    $form['combined']['primary_small'] = [
      '#type' => 'splitbutton',
      '#attributes' => [
        'data-splitbutton-test-id' => 'splitbutton-primary-small',
      ],
      '#splitbutton_type' => [
        'small',
        'primary',
      ],
      '#splitbutton_items' => [
        'item1' => [
          '#type' => 'link',
          '#title' => $this->t('Small + Primary'),
          '#url' => Url::fromRoute('splitbutton.test', ['prevent_generated_link' => microtime()]),
        ],
      ] + $links_plus_button,
    ];
    $form['combined']['danger_extrasmall'] = [
      '#type' => 'splitbutton',
      '#attributes' => [
        'data-splitbutton-test-id' => 'splitbutton-danger-extrasmall',
      ],
      '#splitbutton_type' => [
        'extrasmall',
        'danger',
      ],
      '#splitbutton_items' => [
        'item1' => [
          '#type' => 'link',
          '#title' => $this->t('Extrasmall + danger'),
          '#url' => Url::fromRoute('splitbutton.test', ['prevent_generated_link' => microtime()]),
        ],
      ] + $links_plus_button,
    ];

    $form['single_items'] = [
      '#type' => 'container',
    ];
    $form['single_items']['title'] = [
      '#type' => 'item',
      '#name' => 'single_items',
      '#title' => $this->t('Single item splitbuttons'),
    ];
    $form['single_items']['primary_small'] = [
      '#type' => 'splitbutton',
      '#attributes' => [
        'data-splitbutton-test-id' => 'splitbutton-single-default',
      ],
      '#splitbutton_items' => [
        'item1' => [
          '#type' => 'link',
          '#title' => $this->t('Single and Default'),
          '#url' => Url::fromRoute('splitbutton.test', ['prevent_generated_link' => microtime()]),
        ],
      ],
    ];

    // The Danger button has different styling so it get its own item here.
    $form['single_items']['danger_single'] = [
      '#type' => 'splitbutton',
      '#attributes' => [
        'data-splitbutton-test-id' => 'splitbutton-single-danger',
      ],
      '#splitbutton_type' => 'danger',
      '#splitbutton_items' => [
        'item1' => [
          '#type' => 'link',
          '#title' => $this->t('Single and Danger'),
          '#url' => Url::fromRoute('splitbutton.test', ['prevent_generated_link' => microtime()]),
        ],
      ],
    ];

    $form['hover'] = [
      '#type' => 'container',
    ];
    $form['hover']['title'] = [
      '#type' => 'item',
      '#name' => 'extends_splitbutton',
      '#title' => $this->t('Splitbutton that opens on hover'),
    ];
    $form['hover']['hover_splitbutton'] = [
      '#type' => 'splitbutton',
      '#splitbutton_items' => $links_plus_button,
      '#title' => $this->t('Hover over me'),
      '#hover' => TRUE,
    ];

    $form['extends_splitbutton'] = [
      '#type' => 'container',
    ];
    $form['extends_splitbutton']['title'] = [
      '#type' => 'item',
      '#name' => 'extends_splitbutton',
      '#title' => $this->t('Element extending splitbutton'),
    ];

    $form['extends_splitbutton']['dropbutton_that_extends_splitbutton'] = [
      '#type' => 'dropdown_extends_splitbutton',
      '#items' => [
        'item1' => [
          '#type' => 'link',
          '#title' => $this->t('First Dropdown Item'),
          '#url' => Url::fromRoute('splitbutton.test', ['prevent_generated_link' => microtime()]),
        ],
        'item2' => [
          '#type' => 'link',
          '#title' => $this->t('Second Dropdown Item'),
          '#url' => Url::fromRoute('splitbutton.test', ['prevent_generated_link' => microtime()]),
        ],
        'item3' => [
          '#type' => 'link',
          '#title' => $this->t('Third Dropdown Item'),
          '#url' => Url::fromRoute('splitbutton.test', ['prevent_generated_link' => microtime()]),
        ],
      ],
      '#attributes' => [
        'data-splitbutton-test-id' => 'dropdown-extends-splitbutton',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
