<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\field\LinkBase.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to present a link to an entity.
 *
 * @ingroup views_field_handlers
 */
abstract class LinkBase extends FieldPluginBase {

  use RedirectDestinationTrait;

  /**
   * The access manager service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a LinkBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessManagerInterface $access_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->accessManager = $access_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('access_manager')
    );
  }

  /**
   * Gets the current active user.
   *
   * @todo: https://drupal.org/node/2105123 put this method in
   *   \Drupal\Core\Plugin\PluginBase instead.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function currentUser() {
    if (!$this->currentUser) {
      $this->currentUser = \Drupal::currentUser();
    }
    return $this->currentUser;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text'] = array('default' => $this->getDefaultLabel());
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to display'),
      '#default_value' => $this->options['text'],
    ];
    parent::buildOptionsForm($form, $form_state);

    // The path is set by ::renderLink() so we do not allow to set it.
    $form['alter']['path'] += ['#access' => FALSE];
    $form['alter']['query'] += ['#access' => FALSE];
    $form['alter']['external'] += ['#access' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $this->checkUrlAccess($values)->isAllowed() ? $this->renderLink($values) : '';
  }

  /**
   * Checks access to the link route.
   *
   * @param \Drupal\views\ResultRow $row
   *   A view result row.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkUrlAccess(ResultRow $row) {
    $url = $this->getUrlInfo($row);
    return $this->accessManager->checkNamedRoute($url->getRouteName(), $url->getRouteParameters(), $this->currentUser(), TRUE);
  }

  /**
   * Returns the URI elements of the link.
   *
   * @param \Drupal\views\ResultRow $row
   *   A view result row.
   *
   * @return \Drupal\Core\Url
   *   The URI elements of the link.
   */
  abstract protected function getUrlInfo(ResultRow $row);

  /**
   * Prepares the link to view a entity.
   *
   * @param \Drupal\views\ResultRow $row
   *   A view result row.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink(ResultRow $row) {
    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['url'] = $this->getUrlInfo($row);
    $text = !empty($this->options['text']) ? $this->sanitizeValue($this->options['text']) : $this->getDefaultLabel();
    $this->addLangcode($row);
    return $text;
  }

  /**
   * Adds language information to the options.
   *
   * @param \Drupal\views\ResultRow $row
   *   A view result row.
   */
  protected function addLangcode(ResultRow $row) {
    $entity = $this->getEntity($row);
    $langcode_key = $entity ? $entity->getEntityType()->getKey('langcode') : FALSE;
    if ($langcode_key && isset($this->aliases[$langcode_key])) {
      $this->options['alter']['language'] = $entity->language();
    }
  }

  /**
   * Returns the default label for this link.
   *
   * @return string
   *   The default link label.
   */
  protected function getDefaultLabel() {
    return $this->t('link');
  }

}
