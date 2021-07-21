<?php

namespace Drupal\contextual\Plugin\views\field;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Provides a handler that adds contextual links.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("contextual_links")
 */
class ContextualLinks extends FieldPluginBase {

  use RedirectDestinationTrait;

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['fields'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $all_fields = $this->view->display_handler->getFieldLabels();
    // Offer to include only those fields that follow this one.
    $field_options = array_slice($all_fields, 0, array_search($this->options['id'], array_keys($all_fields)));
    $form['fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Fields'),
      '#description' => $this->t('Fields to be included as contextual links.'),
      '#options' => $field_options,
      '#default_value' => $this->options['fields'],
      '#element_validate' => [[static::class, 'validateOptions']],
    ];
  }

  /**
   * Form API #element_validate for options.
   *
   * @todo Make this reusable (or replace with reusable method).
   * @see \Drupal\Core\Render\Element\Checkboxes::getCheckedCheckboxes
   */
  public static function validateOptions(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = array_filter($element['#value'], function ($value) {
      return $value !== 0;
    });
    $form_state->setValueForElement($element, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    // Add a row plugin css class for the contextual link.
    $class = 'contextual-region';
    if (!empty($this->view->style_plugin->options['row_class'])) {
      $this->view->style_plugin->options['row_class'] .= " $class";
    }
    else {
      $this->view->style_plugin->options['row_class'] = $class;
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::render().
   *
   * Renders the contextual fields.
   *
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @see contextual_preprocess()
   * @see contextual_contextual_links_view_alter()
   */
  public function render(ResultRow $values) {
    $links = [];
    foreach ($this->options['fields'] as $field) {
      $rendered_field = $this->view->field[$field]->last_render;
      if (empty($rendered_field)) {
        continue;
      }
      $title = $this->view->field[$field]->last_render_text;
      $path = '';
      if (!empty($this->view->field[$field]->options['alter']['path'])) {
        $path = $this->view->field[$field]->options['alter']['path'];
      }
      elseif (!empty($this->view->field[$field]->options['alter']['url']) && $this->view->field[$field]->options['alter']['url'] instanceof Url) {
        $path = $this->view->field[$field]->options['alter']['url']->toString();
      }
      if (!empty($title) && !empty($path)) {
        // Make sure that tokens are replaced for this paths as well.
        $tokens = $this->getRenderTokens([]);
        $path = strip_tags(Html::decodeEntities(strtr($path, $tokens)));

        $link_key = "{$this->view->id()}__{$this->view->current_display}__$field";
        $links[$link_key] = [
          'path' => $path,
          'title' => $title,
        ];
        if (!empty($this->options['destination'])) {
          $links[$link_key]['options']['query'] = $this->getDestinationArray();
        }
      }
    }

    // Renders a contextual links placeholder.
    // Links must be a nested array of strings, so that _contextual_links_to_id
    // can serialize them.
    // @see \Drupal\contextual\Element\ContextualLinks::preRenderLinks
    if (!empty($links)) {
      $contextual_links = [
        'contextual' => [
          'route_parameters' => $links,
        ],
      ];

      $element = [
        '#type' => 'contextual_links_placeholder',
        '#id' => _contextual_links_to_id($contextual_links),
      ];
      return \Drupal::service('renderer')->render($element);
    }
    else {
      return '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

}
