<?php

/**
 * @file
 * Definition of Views\translation\Plugin\views\field\NodeTranslationLink.
 */

namespace Views\translation\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Annotation\Plugin;

/**
 * Field handler to present a link to the node.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "node_translation_link",
 *   module = "translation"
 * )
 */
class NodeTranslationLink extends FieldPluginBase {

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, &$options) {
    parent::init($view, $options);

    $this->additional_fields['nid'] = 'nid';
    $this->additional_fields['tnid'] = 'tnid';
    $this->additional_fields['title'] = 'title';
    $this->additional_fields['langcode'] = 'langcode';
  }

  public function query() {
    $this->ensureMyTable();
    $this->add_additional_fields();
  }

  function render($values) {
    $value = $this->get_value($values, 'tnid');
    return $this->render_link($this->sanitizeValue($value), $values);
  }

  function render_link($data, $values) {
    $language_interface = language(LANGUAGE_TYPE_INTERFACE);

    $tnid = $this->get_value($values, 'tnid');
    // Only load translations if the node isn't in the current language.
    if ($this->get_value($values, 'langcode') != $language_interface->langcode) {
      $translations = translation_node_get_translations($tnid);
      if (isset($translations[$language_interface->langcode])) {
        $values->{$this->aliases['nid']} = $translations[$language_interface->langcode]->nid;
        $values->{$this->aliases['title']} = $translations[$language_interface->langcode]->title;
      }
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "node/" . $this->get_value($values, 'nid');
    return $this->get_value($values, 'title');
  }

}
