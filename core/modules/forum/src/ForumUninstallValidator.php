<?php

namespace Drupal\forum;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Prevents forum module from being uninstalled whilst any forum nodes exist
 * or there are any terms in the forum vocabulary.
 */
class ForumUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ForumUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    if ($module == 'forum') {
      if ($this->hasForumNodes()) {
        $reasons[] = $this->t('To uninstall Forum, first delete all <em>Forum</em> content');
      }

      $vocabulary = $this->getForumVocabulary();
      if ($this->hasTermsForVocabulary($vocabulary)) {
        if ($vocabulary->access('view')) {
          $reasons[] = $this->t('To uninstall Forum, first delete all <a href=":url">%vocabulary</a> terms', [
            '%vocabulary' => $vocabulary->label(),
            ':url' => $vocabulary->toUrl('overview-form')->toString(),
          ]);
        }
        else {
          $reasons[] = $this->t('To uninstall Forum, first delete all %vocabulary terms', [
            '%vocabulary' => $vocabulary->label(),
          ]);
        }
      }
    }

    return $reasons;
  }

  /**
   * Determines if there are any forum nodes or not.
   *
   * @return bool
   *   TRUE if there are forum nodes, FALSE otherwise.
   */
  protected function hasForumNodes() {
    $nodes = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'forum')
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return !empty($nodes);
  }

  /**
   * Determines if there are any taxonomy terms for a specified vocabulary.
   *
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   *   The vocabulary to check for terms.
   *
   * @return bool
   *   TRUE if there are terms for this vocabulary, FALSE otherwise.
   */
  protected function hasTermsForVocabulary(VocabularyInterface $vocabulary) {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->condition('vid', $vocabulary->id())
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return !empty($terms);
  }

  /**
   * Returns the vocabulary configured for forums.
   *
   * @return \Drupal\taxonomy\VocabularyInterface
   *   The vocabulary entity for forums.
   */
  protected function getForumVocabulary() {
    $vid = $this->configFactory->get('forum.settings')->get('vocabulary');
    return $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($vid);
  }

}
