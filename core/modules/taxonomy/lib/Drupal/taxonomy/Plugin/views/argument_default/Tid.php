<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Plugin\views\argument_default\Tid.
 */

namespace Drupal\taxonomy\Plugin\views\argument_default;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Taxonomy tid default argument.
 *
 * @ViewsArgumentDefault(
 *   id = "taxonomy_tid",
 *   title = @Translation("Taxonomy term ID from URL")
 * )
 */
class Tid extends ArgumentDefaultPluginBase {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new Tid instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request')
    );
  }

  /**
   * Overrides \Drupal\views\Plugin\views\Plugin\views\PluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // @todo Remove the legacy code.
    // Convert legacy vids option to machine name vocabularies.
    if (!empty($this->options['vids'])) {
      $vocabularies = taxonomy_vocabulary_get_names();
      foreach ($this->options['vids'] as $vid) {
        if (isset($vocabularies[$vid], $vocabularies[$vid]->machine_name)) {
          $this->options['vocabularies'][$vocabularies[$vid]->machine_name] = $vocabularies[$vid]->machine_name;
        }
      }
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['term_page'] = array('default' => TRUE, 'bool' => TRUE);
    $options['node'] = array('default' => FALSE, 'bool' => TRUE);
    $options['anyall'] = array('default' => ',');
    $options['limit'] = array('default' => FALSE, 'bool' => TRUE);
    $options['vids'] = array('default' => array());

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['term_page'] = array(
      '#type' => 'checkbox',
      '#title' => t('Load default filter from term page'),
      '#default_value' => $this->options['term_page'],
    );
    $form['node'] = array(
      '#type' => 'checkbox',
      '#title' => t('Load default filter from node page, that\'s good for related taxonomy blocks'),
      '#default_value' => $this->options['node'],
    );

    $form['limit'] = array(
      '#type' => 'checkbox',
      '#title' => t('Limit terms by vocabulary'),
      '#default_value' => $this->options['limit'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[argument_default][taxonomy_tid][node]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $options = array();
    $vocabularies = entity_load_multiple('taxonomy_vocabulary');
    foreach ($vocabularies as $voc) {
      $options[$voc->id()] = $voc->label();
    }

    $form['vids'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Vocabularies'),
      '#options' => $options,
      '#default_value' => $this->options['vids'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[argument_default][taxonomy_tid][limit]"]' => array('checked' => TRUE),
          ':input[name="options[argument_default][taxonomy_tid][node]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['anyall'] = array(
      '#type' => 'radios',
      '#title' => t('Multiple-value handling'),
      '#default_value' => $this->options['anyall'],
      '#options' => array(
        ',' => t('Filter to items that share all terms'),
        '+' => t('Filter to items that share any term'),
      ),
      '#states' => array(
        'visible' => array(
          ':input[name="options[argument_default][taxonomy_tid][node]"]' => array('checked' => TRUE),
        ),
      ),
    );
  }

  public function submitOptionsForm(&$form, &$form_state, &$options = array()) {
    // Filter unselected items so we don't unnecessarily store giant arrays.
    $options['vids'] = array_filter($options['vids']);
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    // Load default argument from taxonomy page.
    if (!empty($this->options['term_page'])) {
      if (arg(0) == 'taxonomy' && arg(1) == 'term' && is_numeric(arg(2))) {
        return arg(2);
      }
    }
    // Load default argument from node.
    if (!empty($this->options['node'])) {
      // Just check, if a node could be detected.
      if (($node = $this->request->attributes->has('node')) && $node instanceof NodeInterface) {
        $taxonomy = array();
        $instances = field_info_instances('node', $node->getType());
        foreach ($instances as $instance) {
          $field = $instance->getField();
          if ($field->type == 'taxonomy_term_reference') {
            foreach ($node->get($field->name) as $item) {
              $taxonomy[$item->target_id] = $field->settings['allowed_values'][0]['vocabulary'];
            }
          }
        }
        if (!empty($this->options['limit'])) {
          $tids = array();
          // filter by vocabulary
          foreach ($taxonomy as $tid => $vocab) {
            if (!empty($this->options['vids'][$vocab])) {
              $tids[] = $tid;
            }
          }
          return implode($this->options['anyall'], $tids);
        }
        // Return all tids.
        else {
          return implode($this->options['anyall'], array_keys($taxonomy));
        }
      }
    }

    // If the current page is a view that takes tid as an argument,
    // find the tid argument and return it.
    $views_page = views_get_page_view();
    if ($views_page && isset($views_page->argument['tid'])) {
      return $views_page->argument['tid']->argument;
    }
  }

}
