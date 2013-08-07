<?php

/**
 * @file
 * Contains \Drupal\block\Form\PlaceBlocksForm.
 */

namespace Drupal\block\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Form\FormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the 'Place blocks' form.
 */
class PlaceBlocksForm implements FormInterface, ControllerInterface {

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * The theme this block will be placed into.
   *
   * @var string
   */
  protected $theme;

  /**
   * Constructs a new PlaceBlocksForm object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The block plugin manager.
   */
  public function __construct(PluginManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'block_plugin_ui';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $theme = NULL, $category = NULL) {
    $this->theme = $theme;
    $form['#theme'] = 'system_plugin_ui_form';
    $rows = array();
    $categories = array();
    foreach ($this->manager->getDefinitions() as $plugin_id => $plugin_definition) {
      if (empty($category) || $plugin_definition['category'] == $category) {
        $rows[$plugin_id] = $this->row($plugin_id, $plugin_definition);
      }
      $categories[$plugin_definition['category']] = array(
        'title' => $plugin_definition['category'],
        'href' => 'admin/structure/block/list/' . $this->theme . '/add/' . $plugin_definition['category'],
      );
    }

    $form['right']['block'] = array(
      '#type' => 'textfield',
      '#title' => t('Search'),
      '#autocomplete_path' => 'block/autocomplete',
    );
    $form['right']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Next'),
    );
    $form['right']['all_plugins'] = array(
      '#type' => 'link',
      '#title' => t('All blocks'),
      '#href' => 'admin/structure/block/list/' . $this->theme . '/add',
    );
    if (!empty($categories)) {
      $form['right']['categories'] = array(
        '#theme' => 'links',
        '#heading' => array(
          'text' => t('Categories'),
          'level' => 'h3',
        ),
        '#links' => $categories,
      );
    }

    // Sort rows alphabetically.
    asort($rows);
    $form['left']['plugin_library'] = array(
      '#theme' => 'table',
      '#header' => array(t('Subject'), t('Operations')),
      '#rows' => $rows,
    );
    return $form;
  }

  /**
   * Generates the row data for a single block plugin.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   *
   * @return array
   *   The row data for a single block plugin.
   */
  protected function row($plugin_id, array $plugin_definition) {
    $row = array();
    $row[] = String::checkPlain($plugin_definition['admin_label']);
    $row[] = array('data' => array(
      '#type' => 'operations',
      '#links' => array(
        'configure' => array(
          'title' => t('Place block'),
          'href' => 'admin/structure/block/add/' . $plugin_id . '/' . $this->theme,
        ),
      ),
    ));
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    if (!$this->manager->getDefinition($form_state['values']['block'])) {
      form_set_error('block', t('You must select a valid block.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $form_state['redirect'] = 'admin/structure/block/add/' . $form_state['values']['block'] . '/' . $this->theme;
  }

}
