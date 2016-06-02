<?php

namespace Drupal\content_translation\Controller;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;
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
   * Initializes a content translation controller.
   *
   * @param \Drupal\content_translation\ContentTranslationManagerInterface
   *   A content translation manager instance.
   */
  public function __construct(ContentTranslationManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('content_translation.manager'));
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
    /* @var \Drupal\Core\Entity\ContentEntityInterface $source_translation */
    $source_translation = $entity->getTranslation($source->getId());
    $target_translation = $entity->addTranslation($target->getId(), $source_translation->toArray());

    // Make sure we do not inherit the affected status from the source values.
    if ($entity->getEntityType()->isRevisionable()) {
      $target_translation->setRevisionTranslationAffected(NULL);
    }

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityManager()->getStorage('user')->load($this->currentUser()->id());
    $metadata = $this->manager->getTranslationMetadata($target_translation);

    // Update the translation author to current user, as well the translation
    // creation time.
    $metadata->setAuthor($user);
    $metadata->setCreatedTime(REQUEST_TIME);
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
    $handler = $this->entityManager()->getHandler($entity_type_id, 'translation');
    $manager = $this->manager;
    $entity_type = $entity->getEntityType();

    // Start collecting the cacheability metadata, starting with the entity and
    // later merge in the access result cacheability metadata.
    $cacheability = CacheableMetadata::createFromObject($entity);

    $languages = $this->languageManager()->getLanguages();
    $original = $entity->getUntranslated()->language()->getId();
    $translations = $entity->getTranslationLanguages();
    $field_ui = $this->moduleHandler()->moduleExists('field_ui') && $account->hasPermission('administer ' . $entity_type_id . ' fields');

    $rows = array();
    $show_source_column = FALSE;

    if ($this->languageManager()->isMultilingual()) {
      // Determine whether the current entity is translatable.
      $translatable = FALSE;
      foreach ($this->entityManager->getFieldDefinitions($entity_type_id, $entity->bundle()) as $instance) {
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

        $add_url = new Url(
          "entity.$entity_type_id.content_translation_add",
          array(
            'source' => $original,
            'target' => $language->getId(),
            $entity_type_id => $entity->id(),
          ),
          array(
            'language' => $language,
          )
        );
        $edit_url = new Url(
          "entity.$entity_type_id.content_translation_edit",
          array(
            'language' => $language->getId(),
            $entity_type_id => $entity->id(),
          ),
          array(
            'language' => $language,
          )
        );
        $delete_url = new Url(
          "entity.$entity_type_id.content_translation_delete",
          array(
            'language' => $language->getId(),
            $entity_type_id => $entity->id(),
          ),
          array(
            'language' => $language,
          )
        );
        $operations = array(
          'data' => array(
            '#type' => 'operations',
            '#links' => array(),
          ),
        );

        $links = &$operations['data']['#links'];
        if (array_key_exists($langcode, $translations)) {
          // Existing translation in the translation set: display status.
          $translation = $entity->getTranslation($langcode);
          $metadata = $manager->getTranslationMetadata($translation);
          $source = $metadata->getSource() ?: LanguageInterface::LANGCODE_NOT_SPECIFIED;
          $is_original = $langcode == $original;
          $label = $entity->getTranslation($langcode)->label();
          $link = isset($links->links[$langcode]['url']) ? $links->links[$langcode] : array('url' => $entity->urlInfo());
          if (!empty($link['url'])) {
            $link['url']->setOption('language', $language);
            $row_title = $this->l($label, $link['url']);
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
            $links['edit']['url'] = $entity->urlInfo('edit-form');
            $links['edit']['language'] = $language;
          }
          elseif (!$is_original && $translation_access->isAllowed()) {
            $links['edit']['url'] = $edit_url;
          }

          if (isset($links['edit'])) {
            $links['edit']['title'] = $this->t('Edit');
          }
          $status = array('data' => array(
            '#type' => 'inline_template',
            '#template' => '<span class="status">{% if status %}{{ "Published"|t }}{% else %}{{ "Not published"|t }}{% endif %}</span>{% if outdated %} <span class="marker">{{ "outdated"|t }}</span>{% endif %}',
            '#context' => array(
              'status' => $metadata->isPublished(),
              'outdated' => $metadata->isOutdated(),
            ),
          ));

          if ($is_original) {
            $language_name = $this->t('<strong>@language_name (Original language)</strong>', array('@language_name' => $language_name));
            $source_name = $this->t('n/a');
          }
          else {
            $source_name = isset($languages[$source]) ? $languages[$source]->getName() : $this->t('n/a');
            $delete_access = $entity->access('delete', NULL, TRUE);
            $translation_access = $handler->getTranslationAccess($entity, 'delete');
            $cacheability = $cacheability
              ->merge(CacheableMetadata::createFromObject($delete_access))
              ->merge(CacheableMetadata::createFromObject($translation_access));
            if ($entity->access('delete') && $entity_type->hasLinkTemplate('delete-form')) {
              $links['delete'] = array(
                'title' => $this->t('Delete'),
                'url' => $entity->urlInfo('delete-form'),
                'language' => $language,
              );
            }
            elseif ($translation_access->isAllowed()) {
              $links['delete'] = array(
                'title' => $this->t('Delete'),
                'url' => $delete_url,
              );
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
              $links['add'] = array(
                'title' => $this->t('Add'),
                'url' => $add_url,
              );
            }
            elseif ($field_ui) {
              $url = new Url('language.content_settings_page');

              // Link directly to the fields tab to make it easier to find the
              // setting to enable translation on fields.
              $links['nofields'] = array(
                'title' => $this->t('No translatable fields'),
                'url' => $url,
              );
            }
          }

          $status = $this->t('Not translated');
        }
        if ($show_source_column) {
          $rows[] = array(
            $language_name,
            $row_title,
            $source_name,
            $status,
            $operations,
          );
        }
        else {
          $rows[] = array($language_name, $row_title, $status, $operations);
        }
      }
    }
    if ($show_source_column) {
      $header = array(
        $this->t('Language'),
        $this->t('Translation'),
        $this->t('Source language'),
        $this->t('Status'),
        $this->t('Operations'),
      );
    }
    else {
      $header = array(
        $this->t('Language'),
        $this->t('Translation'),
        $this->t('Status'),
        $this->t('Operations'),
      );
    }

    $build['#title'] = $this->t('Translations of %label', array('%label' => $entity->label()));

    // Add metadata to the build render array to let other modules know about
    // which entity this is.
    $build['#entity'] = $entity;
    $cacheability
      ->addCacheTags($entity->getCacheTags())
      ->applyTo($build);

    $build['content_translation_overview'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

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
   * @param \Drupal\Core\Routing\RouteMatchInterface
   *   The route match object from which to extract the entity type.
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   *
   * @return array
   *   A processed form array ready to be rendered.
   */
  public function add(LanguageInterface $source, LanguageInterface $target, RouteMatchInterface $route_match, $entity_type_id = NULL) {
    $entity = $route_match->getParameter($entity_type_id);

    // @todo Exploit the upcoming hook_entity_prepare() when available.
    // See https://www.drupal.org/node/1810394.
    $this->prepareTranslation($entity, $source, $target);

    // @todo Provide a way to figure out the default form operation. Maybe like
    //   $operation = isset($info['default_operation']) ? $info['default_operation'] : 'default';
    //   See https://www.drupal.org/node/2006348.
    $operation = 'default';

    $form_state_additions = array();
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
   * @param \Drupal\Core\Routing\RouteMatchInterface
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
    $operation = 'default';

    $form_state_additions = array();
    $form_state_additions['langcode'] = $language->getId();
    $form_state_additions['content_translation']['translation_form'] = TRUE;

    return $this->entityFormBuilder()->getForm($entity, $operation, $form_state_additions);
  }

}
