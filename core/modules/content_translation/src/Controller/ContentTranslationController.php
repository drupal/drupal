<?php

namespace Drupal\content_translation\Controller;

use Drupal\content_translation\ContentTranslationManager;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for entity translation controllers.
 */
class ContentTranslationController extends ControllerBase {

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $manager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Initializes a content translation controller.
   *
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $manager
   *   A content translation manager instance.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct(ContentTranslationManagerInterface $manager, EntityFieldManagerInterface $entity_field_manager = NULL) {
    $this->manager = $manager;
    if (!$entity_field_manager) {
      @trigger_error('The entity_field.manager service must be passed to ContentTranslationController::__construct(), it is required before Drupal 9.0.0. See https://www.drupal.org/node/2549139.', E_USER_DEPRECATED);
      $entity_field_manager = \Drupal::service('entity_field.manager');
    }
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_translation.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Populates target values with the source values.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being translated.
   * @param \Drupal\Core\Language\LanguageInterface $source
   *   The language to be used as source.
   * @param \Drupal\Core\Language\LanguageInterface $target
   *   The language to be used as target.
   */
  public function prepareTranslation(ContentEntityInterface $entity, LanguageInterface $source, LanguageInterface $target) {
    $source_langcode = $source->getId();
    /* @var \Drupal\Core\Entity\ContentEntityInterface $source_translation */
    $source_translation = $entity->getTranslation($source_langcode);
    $target_translation = $entity->addTranslation($target->getId(), $source_translation->toArray());

    // Make sure we do not inherit the affected status from the source values.
    if ($entity->getEntityType()->isRevisionable()) {
      $target_translation->setRevisionTranslationAffected(NULL);
    }

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());
    $metadata = $this->manager->getTranslationMetadata($target_translation);

    // Update the translation author to current user, as well the translation
    // creation time.
    $metadata->setAuthor($user);
    $metadata->setCreatedTime(REQUEST_TIME);
    $metadata->setSource($source_langcode);
  }

  /**
   * Builds the translations overview page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   * @return array
   *   Array of page elements to render.
   */
  public function overview(RouteMatchInterface $route_match, $entity_type_id = NULL) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $route_match->getParameter($entity_type_id);
    $account = $this->currentUser();
    $handler = $this->entityTypeManager()->getHandler($entity_type_id, 'translation');
    $manager = $this->manager;
    $entity_type = $entity->getEntityType();
    $use_latest_revisions = $entity_type->isRevisionable() && ContentTranslationManager::isPendingRevisionSupportEnabled($entity_type_id, $entity->bundle());

    // Start collecting the cacheability metadata, starting with the entity and
    // later merge in the access result cacheability metadata.
    $cacheability = CacheableMetadata::createFromObject($entity);

    $languages = $this->languageManager()->getLanguages();
    $original = $entity->getUntranslated()->language()->getId();
    $translations = $entity->getTranslationLanguages();
    $field_ui = $this->moduleHandler()->moduleExists('field_ui') && $account->hasPermission('administer ' . $entity_type_id . ' fields');

    $rows = [];
    $show_source_column = FALSE;
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage($entity_type_id);
    $default_revision = $storage->load($entity->id());

    if ($this->languageManager()->isMultilingual()) {
      // Determine whether the current entity is translatable.
      $translatable = FALSE;
      foreach ($this->entityFieldManager->getFieldDefinitions($entity_type_id, $entity->bundle()) as $instance) {
        if ($instance->isTranslatable()) {
          $translatable = TRUE;
          break;
        }
      }

      // Show source-language column if there are non-original source langcodes.
      $additional_source_langcodes = array_filter(array_keys($translations), function ($langcode) use ($entity, $original, $manager) {
        $source = $manager->getTranslationMetadata($entity->getTranslation($langcode))->getSource();
        return $source != $original && $source != LanguageInterface::LANGCODE_NOT_SPECIFIED;
      });
      $show_source_column = !empty($additional_source_langcodes);

      foreach ($languages as $language) {
        $language_name = $language->getName();
        $langcode = $language->getId();

        // If the entity type is revisionable, we may have pending revisions
        // with translations not available yet in the default revision. Thus we
        // need to load the latest translation-affecting revision for each
        // language to be sure we are listing all available translations.
        if ($use_latest_revisions) {
          $entity = $default_revision;
          $latest_revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $langcode);
          if ($latest_revision_id) {
            /** @var \Drupal\Core\Entity\ContentEntityInterface $latest_revision */
            $latest_revision = $storage->loadRevision($latest_revision_id);
            // Make sure we do not list removed translations, i.e. translations
            // that have been part of a default revision but no longer are.
            if (!$latest_revision->wasDefaultRevision() || $default_revision->hasTranslation($langcode)) {
              $entity = $latest_revision;
            }
          }
          $translations = $entity->getTranslationLanguages();
        }

        $options = ['language' => $language];
        $add_url = $entity->toUrl('drupal:content-translation-add', $options)
          ->setRouteParameter('source', $original)
          ->setRouteParameter('target', $language->getId());
        $edit_url = $entity->toUrl('drupal:content-translation-edit', $options)
          ->setRouteParameter('language', $language->getId());
        $delete_url = $entity->toUrl('drupal:content-translation-delete', $options)
          ->setRouteParameter('language', $language->getId());
        $operations = [
          'data' => [
            '#type' => 'operations',
            '#links' => [],
          ],
        ];

        $links = &$operations['data']['#links'];
        if (array_key_exists($langcode, $translations)) {
          // Existing translation in the translation set: display status.
          $translation = $entity->getTranslation($langcode);
          $metadata = $manager->getTranslationMetadata($translation);
          $source = $metadata->getSource() ?: LanguageInterface::LANGCODE_NOT_SPECIFIED;
          $is_original = $langcode == $original;
          $label = $entity->getTranslation($langcode)->label();
          $link = isset($links->links[$langcode]['url']) ? $links->links[$langcode] : ['url' => $entity->toUrl()];
          if (!empty($link['url'])) {
            $link['url']->setOption('language', $language);
            $row_title = Link::fromTextAndUrl($label, $link['url'])->toString();
          }

          if (empty($link['url'])) {
            $row_title = $is_original ? $label : $this->t('n/a');
          }

          // If the user is allowed to edit the entity we point the edit link to
          // the entity form, otherwise if we are not dealing with the original
          // language we point the link to the translation form.
          $update_access = $entity->access('update', NULL, TRUE);
          $translation_access = $handler->getTranslationAccess($entity, 'update');
          $cacheability = $cacheability
            ->merge(CacheableMetadata::createFromObject($update_access))
            ->merge(CacheableMetadata::createFromObject($translation_access));
          if ($update_access->isAllowed() && $entity_type->hasLinkTemplate('edit-form')) {
            $links['edit']['url'] = $entity->toUrl('edit-form');
            $links['edit']['language'] = $language;
          }
          elseif (!$is_original && $translation_access->isAllowed()) {
            $links['edit']['url'] = $edit_url;
          }

          if (isset($links['edit'])) {
            $links['edit']['title'] = $this->t('Edit');
          }
          $status = [
            'data' => [
              '#type' => 'inline_template',
              '#template' => '<span class="status">{% if status %}{{ "Published"|t }}{% else %}{{ "Not published"|t }}{% endif %}</span>{% if outdated %} <span class="marker">{{ "outdated"|t }}</span>{% endif %}',
              '#context' => [
                'status' => $metadata->isPublished(),
                'outdated' => $metadata->isOutdated(),
              ],
            ],
          ];

          if ($is_original) {
            $language_name = $this->t('<strong>@language_name (Original language)</strong>', ['@language_name' => $language_name]);
            $source_name = $this->t('n/a');
          }
          else {
            /** @var \Drupal\Core\Access\AccessResultInterface $delete_route_access */
            $delete_route_access = \Drupal::service('content_translation.delete_access')->checkAccess($translation);
            $cacheability->addCacheableDependency($delete_route_access);

            if ($delete_route_access->isAllowed()) {
              $source_name = isset($languages[$source]) ? $languages[$source]->getName() : $this->t('n/a');
              $delete_access = $entity->access('delete', NULL, TRUE);
              $translation_access = $handler->getTranslationAccess($entity, 'delete');
              $cacheability
                ->addCacheableDependency($delete_access)
                ->addCacheableDependency($translation_access);

              if ($delete_access->isAllowed() && $entity_type->hasLinkTemplate('delete-form')) {
                $links['delete'] = [
                  'title' => $this->t('Delete'),
                  'url' => $entity->toUrl('delete-form'),
                  'language' => $language,
                ];
              }
              elseif ($translation_access->isAllowed()) {
                $links['delete'] = [
                  'title' => $this->t('Delete'),
                  'url' => $delete_url,
                ];
              }
            }
            else {
              $this->messenger()->addWarning($this->t('The "Delete translation" action is only available for published translations.'), FALSE);
            }
          }
        }
        else {
          // No such translation in the set yet: help user to create it.
          $row_title = $source_name = $this->t('n/a');
          $source = $entity->language()->getId();

          $create_translation_access = $handler->getTranslationAccess($entity, 'create');
          $cacheability = $cacheability
            ->merge(CacheableMetadata::createFromObject($create_translation_access));
          if ($source != $langcode && $create_translation_access->isAllowed()) {
            if ($translatable) {
              $links['add'] = [
                'title' => $this->t('Add'),
                'url' => $add_url,
              ];
            }
            elseif ($field_ui) {
              $url = new Url('language.content_settings_page');

              // Link directly to the fields tab to make it easier to find the
              // setting to enable translation on fields.
              $links['nofields'] = [
                'title' => $this->t('No translatable fields'),
                'url' => $url,
              ];
            }
          }

          $status = $this->t('Not translated');
        }
        if ($show_source_column) {
          $rows[] = [
            $language_name,
            $row_title,
            $source_name,
            $status,
            $operations,
          ];
        }
        else {
          $rows[] = [$language_name, $row_title, $status, $operations];
        }
      }
    }
    if ($show_source_column) {
      $header = [
        $this->t('Language'),
        $this->t('Translation'),
        $this->t('Source language'),
        $this->t('Status'),
        $this->t('Operations'),
      ];
    }
    else {
      $header = [
        $this->t('Language'),
        $this->t('Translation'),
        $this->t('Status'),
        $this->t('Operations'),
      ];
    }

    $build['#title'] = $this->t('Translations of %label', ['%label' => $entity->label()]);

    // Add metadata to the build render array to let other modules know about
    // which entity this is.
    $build['#entity'] = $entity;
    $cacheability
      ->addCacheTags($entity->getCacheTags())
      ->applyTo($build);

    $build['content_translation_overview'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

  /**
   * Builds an add translation page.
   *
   * @param \Drupal\Core\Language\LanguageInterface $source
   *   The language of the values being translated. Defaults to the entity
   *   language.
   * @param \Drupal\Core\Language\LanguageInterface $target
   *   The language of the translated values. Defaults to the current content
   *   language.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object from which to extract the entity type.
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   *
   * @return array
   *   A processed form array ready to be rendered.
   */
  public function add(LanguageInterface $source, LanguageInterface $target, RouteMatchInterface $route_match, $entity_type_id = NULL) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $route_match->getParameter($entity_type_id);

    // In case of a pending revision, make sure we load the latest
    // translation-affecting revision for the source language, otherwise the
    // initial form values may not be up-to-date.
    if (!$entity->isDefaultRevision() && ContentTranslationManager::isPendingRevisionSupportEnabled($entity_type_id, $entity->bundle())) {
      /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
      $storage = $this->entityTypeManager()->getStorage($entity->getEntityTypeId());
      $revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $source->getId());
      if ($revision_id != $entity->getRevisionId()) {
        $entity = $storage->loadRevision($revision_id);
      }
    }

    // @todo Exploit the upcoming hook_entity_prepare() when available.
    // See https://www.drupal.org/node/1810394.
    $this->prepareTranslation($entity, $source, $target);

    // @todo Provide a way to figure out the default form operation. Maybe like
    //   $operation = isset($info['default_operation']) ? $info['default_operation'] : 'default';
    //   See https://www.drupal.org/node/2006348.

    // Use the add form handler, if available, otherwise default.
    $operation = $entity->getEntityType()->hasHandlerClass('form', 'add') ? 'add' : 'default';

    $form_state_additions = [];
    $form_state_additions['langcode'] = $target->getId();
    $form_state_additions['content_translation']['source'] = $source;
    $form_state_additions['content_translation']['target'] = $target;
    $form_state_additions['content_translation']['translation_form'] = !$entity->access('update');

    return $this->entityFormBuilder()->getForm($entity, $operation, $form_state_additions);
  }

  /**
   * Builds the edit translation page.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language of the translated values. Defaults to the current content
   *   language.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object from which to extract the entity type.
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   *
   * @return array
   *   A processed form array ready to be rendered.
   */
  public function edit(LanguageInterface $language, RouteMatchInterface $route_match, $entity_type_id = NULL) {
    $entity = $route_match->getParameter($entity_type_id);

    // @todo Provide a way to figure out the default form operation. Maybe like
    //   $operation = isset($info['default_operation']) ? $info['default_operation'] : 'default';
    //   See https://www.drupal.org/node/2006348.

    // Use the edit form handler, if available, otherwise default.
    $operation = $entity->getEntityType()->hasHandlerClass('form', 'edit') ? 'edit' : 'default';

    $form_state_additions = [];
    $form_state_additions['langcode'] = $language->getId();
    $form_state_additions['content_translation']['translation_form'] = TRUE;

    return $this->entityFormBuilder()->getForm($entity, $operation, $form_state_additions);
  }

}
