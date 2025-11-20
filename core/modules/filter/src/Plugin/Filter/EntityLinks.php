<?php

declare(strict_types=1);

namespace Drupal\filter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Utility\Error;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides the Entity Links filter.
 */
#[Filter(
  id: 'entity_links',
  title: new TranslatableMarkup('Entity links'),
  type: FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
  description: new TranslatableMarkup('Updates entity links with <code>data-entity-type</code> and <code>data-entity-uuid</code> attributes to point to the latest entity URL aliases.'),
)]
class EntityLinks extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a EntityLinks object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly EntityRepositoryInterface $entityRepository,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    $result = new FilterProcessResult($text);

    if (!str_contains($text, 'data-entity-type') && !str_contains($text, 'data-entity-uuid')) {
      return $result;
    }

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);

    // Note: this filter only processes links (<a href>) to Files, not
    // tags with a File as a source (e.g. <img src>).
    // @see \Drupal\editor\Plugin\Filter\EditorFileReference
    foreach ($xpath->query('//a[@data-entity-type and @data-entity-uuid]') as $element) {
      /** @var \DOMElement $element */
      try {
        // Load the appropriate translation of the linked entity.
        $entity_type = $element->getAttribute('data-entity-type');
        $uuid = $element->getAttribute('data-entity-uuid');

        // Skip empty attributes to prevent loading of non-existing
        // content type.
        if ($entity_type === '' || $uuid === '') {
          continue;
        }

        $entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);
        if ($entity) {
          // @todo Consider using \Drupal\Core\Entity\EntityRepositoryInterface::getTranslationFromContext() after https://drupal.org/i/3061761 is fixed.
          if ($entity instanceof TranslatableInterface && $entity->hasTranslation($langcode)) {
            $entity = $entity->getTranslation($langcode);
          }

          $url = $this->getUrl($entity);

          // Parse link href as URL, extract query and fragment from it.
          $href_url = parse_url($element->getAttribute('href'));
          $anchor = empty($href_url["fragment"]) ? '' : '#' . $href_url["fragment"];
          $query = empty($href_url["query"]) ? '' : '?' . $href_url["query"];

          $element->setAttribute('href', $url->getGeneratedUrl() . $query . $anchor);

          // The processed text now depends on:
          $result
            // The generated URL (which has undergone path & route processing)
            ->addCacheableDependency($url)
            // The linked entity (whose URL and title may change)
            ->addCacheableDependency($entity);
        }

        $element->removeAttribute('data-entity-type');
        $element->removeAttribute('data-entity-uuid');
        $element->removeAttribute('data-entity-metadata');
      }
      catch (\Exception $e) {
        Error::logException('filter', $e);
      }
    }

    $result->setProcessedText(Html::serialize($dom));

    return $result;
  }

  /**
   * Gets the generated URL object for a linked entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A linked entity.
   *
   * @return \Drupal\Core\GeneratedUrl
   *   The generated URL plus cacheability metadata.
   */
  protected static function getUrl(EntityInterface $entity): GeneratedUrl {
    if ($link_target_handler = $entity->getEntityType()->getHandlerClass('link_target', 'view')) {
      return (new $link_target_handler())->getLinkTarget($entity);
    }

    return $entity->toUrl()->toString(TRUE);
  }

}
