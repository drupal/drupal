<?php

namespace Drupal\views\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
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
   *   The plugin ID for the plugin instance.
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
    [$name, $this->displayID] = explode('-', $delta, 2);
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
      $container->get('entity_type.manager')->getStorage('view'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = $this->view->display_handler->getCacheMetadata()->getCacheContexts();
    return Cache::mergeContexts(parent::getCacheContexts(), $contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = $this->view->display_handler->getCacheMetadata()->getCacheTags();
    return Cache::mergeTags(parent::getCacheTags(), $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $max_age = $this->view->display_handler->getCacheMetadata()->getCacheMaxAge();
    return Cache::mergeMaxAges(parent::getCacheMaxAge(), $max_age);
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($this->view->access($this->displayID)) {
      $access = AccessResult::allowed();
    }
    else {
      $access = AccessResult::forbidden();
    }
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['views_label' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewFallbackString() {
    if (!empty($this->pluginDefinition["admin_label"])) {
      return $this->t('"@view" views block', ['@view' => $this->pluginDefinition["admin_label"]]);
    }
    else {
      return $this->t('"@view" views block', ['@view' => $this->view->storage->label() . '::' . $this->displayID]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
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
    $form['views_label_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override title'),
      '#default_value' => !empty($this->configuration['views_label']),
    ];

    $form['views_label_fieldset'] = [
      '#type' => 'fieldset',
      '#states' => [
        'visible' => [
          [
            ':input[name="settings[views_label_checkbox]"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];

    $form['views_label'] = [
      '#title' => $this->t('Title'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['views_label'] ?: $this->view->getTitle(),
      '#states' => [
        'visible' => [
          [
            ':input[name="settings[views_label_checkbox]"]' => ['checked' => TRUE],
          ],
        ],
      ],
      '#fieldset' => 'views_label_fieldset',
    ];

    if ($this->view->storage->access('edit') && \Drupal::moduleHandler()->moduleExists('views_ui')) {
      $form['views_label']['#description'] = $this->t('Changing the title here means it cannot be dynamically altered anymore. (Try changing it directly in <a href=":url">@name</a>.)', [':url' => Url::fromRoute('entity.view.edit_display_form', ['view' => $this->view->storage->id(), 'display_id' => $this->displayID])->toString(), '@name' => $this->view->storage->label()]);
    }
    else {
      $form['views_label']['#description'] = $this->t('Changing the title here means it cannot be dynamically altered anymore.');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('views_label_checkbox')) {
      $this->configuration['views_label'] = $form_state->getValue('views_label');
    }
    else {
      $this->configuration['views_label'] = '';
    }
    $form_state->unsetValue('views_label_checkbox');
  }

  /**
   * Converts Views block content to a renderable array with contextual links.
   *
   * @param string|array $output
   *   A string|array representing the block. This will be modified to be a
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
        $output = ['#markup' => $output];
      }

      // views_add_contextual_links() needs the following information in
      // order to be attached to the view.
      $output['#view_id'] = $this->view->storage->id();
      $output['#view_display_show_admin_links'] = $this->view->getShowAdminLinks();
      $output['#view_display_plugin_id'] = $this->view->display_handler->getPluginId();
      views_add_contextual_links($output, $block_type, $this->displayID);
    }
  }

  /**
   * Gets the view executable.
   *
   * @return \Drupal\views\ViewExecutable
   *   The view executable.
   *
   * @todo revisit after https://www.drupal.org/node/3027653. This method was
   *   added in https://www.drupal.org/node/3002608, but should not be
   *   necessary once block plugins can determine if they are being previewed.
   */
  public function getViewExecutable() {
    return $this->view;
  }

}
