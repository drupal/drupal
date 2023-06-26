<?php

namespace Drupal\help\Controller;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\help\HelpTopicPluginManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for help topic plugins.
 *
 * @internal
 *   Controller classes are internal.
 */
class HelpTopicPluginController extends ControllerBase {

  /**
   * Constructs a HelpTopicPluginController object.
   *
   * @param \Drupal\help\HelpTopicPluginManagerInterface $helpTopicPluginManager
   *   The help topic plugin manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(protected HelpTopicPluginManagerInterface $helpTopicPluginManager, protected RendererInterface $renderer) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.help_topic'),
      $container->get('renderer')
    );
  }

  /**
   * Displays a help topic page.
   *
   * @param string $id
   *   The plugin ID. Maps to the {id} placeholder in the
   *   help.help_topic route.
   *
   * @return array
   *   A render array with the contents of a help topic page.
   */
  public function viewHelpTopic($id) {
    $build = [];

    if (!$this->helpTopicPluginManager->hasDefinition($id)) {
      throw new NotFoundHttpException();
    }
    /** @var \Drupal\help\HelpTopicPluginInterface $help_topic */
    $help_topic = $this->helpTopicPluginManager->createInstance($id);

    $build['#body'] = $help_topic->getBody();

    $this->renderer->addCacheableDependency($build, $help_topic);

    // Build the related topics section, starting with the list this topic
    // says are related.
    $links = [];

    $related = $help_topic->getRelated();
    foreach ($related as $other_id) {
      if ($other_id !== $id) {
        /** @var \Drupal\help\HelpTopicPluginInterface $topic */
        $topic = $this->helpTopicPluginManager->createInstance($other_id);
        $links[$other_id] = [
          'title' => $topic->getLabel(),
          'url' => Url::fromRoute('help.help_topic', ['id' => $other_id]),
        ];
        $this->renderer->addCacheableDependency($build, $topic);
      }
    }

    if (count($links)) {
      uasort($links, [SortArray::class, 'sortByTitleElement']);
      $build['#related'] = [
        '#theme' => 'links__related',
        '#heading' => [
          'text' => $this->t('Related topics'),
          'level' => 'h2',
        ],
        '#links' => $links,
      ];
    }

    $build['#theme'] = 'help_topic';
    $build['#title'] = $help_topic->getLabel();
    return $build;
  }

}
