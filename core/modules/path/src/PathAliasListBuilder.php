<?php

namespace Drupal\path;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Url;
use Drupal\path\Form\PathFilterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a class to build a listing of path_alias entities.
 *
 * @see \Drupal\path_alias\Entity\PathAlias
 */
class PathAliasListBuilder extends EntityListBuilder {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a new PathAliasListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Symfony\Component\HttpFoundation\Request $current_request
   *   The current request.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, Request $current_request, FormBuilderInterface $form_builder, LanguageManagerInterface $language_manager, AliasManagerInterface $alias_manager) {
    parent::__construct($entity_type, $storage);

    $this->currentRequest = $current_request;
    $this->formBuilder = $form_builder;
    $this->languageManager = $language_manager;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('form_builder'),
      $container->get('language_manager'),
      $container->get('path_alias.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery();

    $search = $this->currentRequest->query->get('search');
    if ($search) {
      $query->condition('alias', $search, 'CONTAINS');
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    // Allow the entity query to sort using the table header.
    $header = $this->buildHeader();
    $query->tableSort($header);

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $keys = $this->currentRequest->query->get('search');
    $build['path_admin_filter_form'] = $this->formBuilder->getForm(PathFilterForm::class, $keys);
    $build += parent::render();

    $build['table']['#empty'] = $this->t('No path aliases available. <a href=":link">Add URL alias</a>.', [':link' => Url::fromRoute('entity.path_alias.add_form')->toString()]);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'alias' => [
        'data' => $this->t('Alias'),
        'field' => 'alias',
        'specifier' => 'alias',
        'sort' => 'asc',
      ],
      'path' => [
        'data' => $this->t('System path'),
        'field' => 'path',
        'specifier' => 'path',
      ],
    ];

    // Enable language column and filter if multiple languages are added.
    if ($this->languageManager->isMultilingual()) {
      $header['language_name'] = [
        'data' => $this->t('Language'),
        'field' => 'langcode',
        'specifier' => 'langcode',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ];
    }

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\Core\Path\Entity\PathAlias $entity */
    $langcode = $entity->language()->getId();
    $alias = $entity->getAlias();
    $path = $entity->getPath();
    $url = Url::fromUserInput($path);

    $row['data']['alias']['data'] = [
      '#type' => 'link',
      '#title' => Unicode::truncate($alias, 50, FALSE, TRUE),
      '#url' => $url->setOption('attributes', ['title' => $alias]),
    ];
    $row['data']['path']['data'] = [
      '#type' => 'link',
      '#title' => Unicode::truncate($path, 50, FALSE, TRUE),
      '#url' => $url->setOption('attributes', ['title' => $path]),
    ];

    if ($this->languageManager->isMultilingual()) {
      $row['data']['language_name'] = $this->languageManager->getLanguageName($langcode);
    }

    $row['data']['operations']['data'] = $this->buildOperations($entity);

    // If the system path maps to a different URL alias, highlight this table
    // row to let the user know of old aliases.
    if ($alias != $this->aliasManager->getAliasByPath($path, $langcode)) {
      $row['class'] = ['warning'];
    }

    return $row;
  }

}
