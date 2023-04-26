<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\views\Entity\Render\EntityTranslationRenderTrait;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to present a link to an entity.
 *
 * @ingroup views_field_handlers
 */
abstract class LinkBase extends FieldPluginBase {

  use RedirectDestinationTrait;
  use EntityTranslationRenderTrait;

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
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessManagerInterface $access_manager, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->accessManager = $access_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('access_manager'),
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('language_manager')
    );
  }

  /**
   * Gets the current active user.
   *
   * @todo: https://www.drupal.org/node/2105123 put this method in
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
    $options['text'] = ['default' => $this->getDefaultLabel()];
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
    $form['alter'] += ['path' => [], 'query' => [], 'external' => []];
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
    if ($this->languageManager->isMultilingual()) {
      $this->getEntityTranslationRenderer()->query($this->query, $this->relationship);
    }
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $access = $this->checkUrlAccess($row);
    if ($access) {
      $build = ['#markup' => $access->isAllowed() ? $this->renderLink($row) : ''];
      BubbleableMetadata::createFromObject($access)->applyTo($build);
      return $build;
    }
    return '';
  }

  /**
   * Checks access to the link route.
   *
   * @param \Drupal\views\ResultRow $row
   *   A view result row.
   *
   * @return \Drupal\Core\Access\AccessResultInterface|null
   *   The access result, or NULL if the URI elements of the link doesn't exist.
   */
  protected function checkUrlAccess(ResultRow $row) {
    if ($url = $this->getUrlInfo($row)) {
      return $this->accessManager->checkNamedRoute($url->getRouteName(), $url->getRouteParameters(), $this->currentUser(), TRUE);
    }
  }

  /**
   * Returns the URI elements of the link.
   *
   * @param \Drupal\views\ResultRow $row
   *   A view result row.
   *
   * @return \Drupal\Core\Url|null
   *   The URI elements of the link.
   */
  abstract protected function getUrlInfo(ResultRow $row);

  /**
   * Prepares the link to view an entity.
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
    if ($entity && $this->languageManager->isMultilingual()) {
      $this->options['alter']['language'] = $this->getEntityTranslationByRelationship($entity, $row)->language();
    }
  }

  /**
   * Returns the default label for this link.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The default link label.
   */
  protected function getDefaultLabel() {
    return $this->t('link');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeId() {
    return $this->getEntityType();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityRepository() {
    return $this->entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguageManager() {
    return $this->languageManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getView() {
    return $this->view;
  }

}
