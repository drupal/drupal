<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Block\ViewsBlockBase.
 */

namespace Drupal\views\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\ViewExecutableFactory;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Base class for Views block plugins.
 */
abstract class ViewsBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The View executable object.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * The display ID being used for this View.
   *
   * @var string
   */
  protected $displayID;

  /**
   * Indicates whether the display was successfully set.
   *
   * @var bool
   */
  protected $displaySet;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Constructs a \Drupal\views\Plugin\Block\ViewsBlockBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\views\ViewExecutableFactory $executable_factory
   *   The view executable factory.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The views storage.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewExecutableFactory $executable_factory, EntityStorageInterface $storage, AccountInterface $user) {
    $this->pluginId = $plugin_id;
    $delta = $this->getDerivativeId();
    list($name, $this->displayID) = explode('-', $delta, 2);
    // Load the view.
    $view = $storage->load($name);
    $this->view = $executable_factory->get($view);
    $this->displaySet = $this->view->setDisplay($this->displayID);
    $this->user = $user;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('views.executable'),
      $container->get('entity.manager')->getStorage('view'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return $this->view->access($this->displayID);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('views_label' => '');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Set the default label to '' so the views internal title is used.
    $form['label']['#default_value'] = '';
    $form['label']['#access'] = FALSE;

    // Unset the machine_name provided by BlockForm.
    unset($form['id']['#machine_name']['source']);
    // Prevent users from changing the auto-generated block machine_name.
    $form['id']['#access'] = FALSE;
    $form['#pre_render'][] = '\Drupal\views\Plugin\views\PluginBase::preRenderAddFieldsetMarkup';

    // Allow to override the label on the actual page.
    $form['views_label_checkbox'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Override title'),
      '#default_value' => !empty($this->configuration['views_label']),
    );

    $form['views_label_fieldset'] = array(
      '#type' => 'fieldset',
      '#states' => array(
        'visible' => array(
          array(
            ':input[name="settings[views_label_checkbox]"]' => array('checked' => TRUE),
          ),
        ),
      ),
    );

    $form['views_label'] = array(
      '#title' => $this->t('Title'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['views_label'] ?: $this->view->getTitle(),
      '#states' => array(
        'visible' => array(
          array(
            ':input[name="settings[views_label_checkbox]"]' => array('checked' => TRUE),
          ),
        ),
      ),
      '#fieldset' => 'views_label_fieldset',
    );

    if ($this->view->storage->access('edit') && \Drupal::moduleHandler()->moduleExists('views_ui')) {
      $form['views_label']['#description'] = $this->t('Changing the title here means it cannot be dynamically altered anymore. (Try changing it directly in <a href="@url">@name</a>.)', array('@url' => \Drupal::url('views_ui.edit_display', array('view' => $this->view->storage->id(), 'display_id' => $this->displayID)), '@name' => $this->view->storage->label()));
    }
    else {
      $form['views_label']['#description'] = $this->t('Changing the title here means it cannot be dynamically altered anymore.');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, &$form_state) {
    if (!empty($form_state['values']['views_label_checkbox'])) {
      $this->configuration['views_label'] = $form_state['values']['views_label'];
    }
    else {
      $this->configuration['views_label'] = '';
    }
  }

  /**
   * Converts Views block content to a renderable array with contextual links.
   *
   * @param string|array $output
   *   An string|array representing the block. This will be modified to be a
   *   renderable array, containing the optional '#contextual_links' property (if
   *   there are any contextual links associated with the block).
   * @param string $block_type
   *   The type of the block. If it's 'block' it's a regular views display,
   *   but 'exposed_filter' exist as well.
   */
  protected function addContextualLinks(&$output, $block_type = 'block') {
    // Do not add contextual links to an empty block.
    if (!empty($output)) {
      // Contextual links only work on blocks whose content is a renderable
      // array, so if the block contains a string of already-rendered markup,
      // convert it to an array.
      if (is_string($output)) {
        $output = array('#markup' => $output);
      }
      // Add the contextual links.
      views_add_contextual_links($output, $block_type, $this->view, $this->displayID);
    }
  }

}
