<?php

namespace Drupal\views\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsRow;

/**
 * EntityReference row plugin.
 *
 * @ingroup views_row_plugins
 */
#[ViewsRow(
  id: "entity_reference",
  title: new TranslatableMarkup("Entity Reference inline fields"),
  help: new TranslatableMarkup("Displays the fields with an optional template."),
  theme: "views_view_fields",
  register_theme: FALSE,
  display_types: ["entity_reference"]
)]
class EntityReference extends Fields {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['separator'] = ['default' => '-'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Expand the description of the 'Inline field' checkboxes.
    $form['inline']['#description'] .= '<br />' . $this->t("<strong>Note:</strong> In 'Entity Reference' displays, all fields will be displayed inline unless an explicit selection of inline fields is made here.");
  }

  /**
   * {@inheritdoc}
   */
  public function preRender($row) {
    // Force all fields to be inline by default.
    if (empty($this->options['inline'])) {
      $fields = $this->view->getHandlers('field', $this->displayHandler->display['id']);
      $names = array_keys($fields);
      $this->options['inline'] = array_combine($names, $names);
    }

    return parent::preRender($row);
  }

}
