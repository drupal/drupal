<?php

namespace Drupal\Core\Render\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Form\FormHelper;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element;
use Drupal\Core\Url as CoreUrl;

/**
 * Provides a link render element.
 *
 * Properties:
 * - #title: The link text.
 * - #url: \Drupal\Core\Url object containing URL information pointing to an
 *   internal or external link. See \Drupal\Core\Utility\LinkGeneratorInterface.
 *
 * Usage example:
 * @code
 * $build['examples_link'] = [
 *   '#title' => $this->t('Examples'),
 *   '#type' => 'link',
 *   '#url' => \Drupal\Core\Url::fromRoute('examples.description')
 * ];
 * @endcode
 *
 * @RenderElement("link")
 */
class Link extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#pre_render' => [
        [$class, 'preRenderLink'],
      ],
    ];
  }

  /**
   * Pre-render callback: Renders a link into #markup.
   *
   * Doing so during pre_render gives modules a chance to alter the link parts.
   *
   * @param array $element
   *   A structured array whose keys form the arguments to
   *   \Drupal\Core\Utility\LinkGeneratorInterface::generate():
   *   - #title: The link text.
   *   - #url: The URL info either pointing to a route or a non routed path.
   *   - #options: (optional) An array of options to pass to the link generator.
   *
   * @return array
   *   The passed-in element containing a rendered link in '#markup'.
   */
  public static function preRenderLink($element) {
    // As the preRenderLink() method is executed before Renderer::doRender(),
    // call processStates() to make sure that states are added to link elements.
    if (!empty($element['#states'])) {
      FormHelper::processStates($element);
    }

    // By default, link options to pass to the link generator are normally set
    // in #options.
    $element += ['#options' => []];
    // However, within the scope of renderable elements, #attributes is a valid
    // way to specify attributes, too. Take them into account, but do not override
    // attributes from #options.
    if (isset($element['#attributes'])) {
      $element['#options'] += ['attributes' => []];
      $element['#options']['attributes'] += $element['#attributes'];
    }

    // This #pre_render callback can be invoked from inside or outside of a Form
    // API context, and depending on that, an HTML ID may be already set in
    // different locations. #options should have precedence over Form API's #id.
    // #attributes have been taken over into #options above already.
    if (isset($element['#options']['attributes']['id'])) {
      $element['#id'] = $element['#options']['attributes']['id'];
    }
    elseif (isset($element['#id'])) {
      $element['#options']['attributes']['id'] = $element['#id'];
    }

    // Conditionally invoke self::preRenderAjaxForm(), if #ajax is set.
    if (isset($element['#ajax']) && !isset($element['#ajax_processed'])) {
      // If no HTML ID was found above, automatically create one.
      if (!isset($element['#id'])) {
        $element['#id'] = $element['#options']['attributes']['id'] = HtmlUtility::getUniqueId('ajax-link');
      }
      $element = static::preRenderAjaxForm($element);
    }

    if (!empty($element['#url']) && $element['#url'] instanceof CoreUrl) {
      $options = NestedArray::mergeDeep($element['#url']->getOptions(), $element['#options']);
      /** @var \Drupal\Core\Utility\LinkGenerator $link_generator */
      $link_generator = \Drupal::service('link_generator');
      $generated_link = $link_generator->generate($element['#title'], $element['#url']->setOptions($options));
      $element['#markup'] = $generated_link;
      $generated_link->merge(BubbleableMetadata::createFromRenderArray($element))
        ->applyTo($element);
    }
    return $element;
  }

  /**
   * Pre-render callback: Collects child links into a single array.
   *
   * This method can be added as a pre_render callback for a renderable array,
   * usually one which will be themed by links.html.twig. It iterates through
   * all unrendered children of the element, collects any #links properties it
   * finds, merges them into the parent element's #links array, and prevents
   * those children from being rendered separately.
   *
   * The purpose of this is to allow links to be logically grouped into related
   * categories, so that each child group can be rendered as its own list of
   * links if RendererInterface::render() is called on it, but
   * calling RendererInterface::render() on the parent element will
   * still produce a single list containing all the remaining links, regardless
   * of what group they were in.
   *
   * A typical example comes from node links, which are stored in a renderable
   * array similar to this:
   * @code
   * $build['links'] = array(
   *   '#theme' => 'links__node',
   *   '#pre_render' => array(Link::class, 'preRenderLinks'),
   *   'comment' => array(
   *     '#theme' => 'links__node__comment',
   *     '#links' => array(
   *       // An array of links associated with node comments, suitable for
   *       // passing in to links.html.twig.
   *     ),
   *   ),
   *   'statistics' => array(
   *     '#theme' => 'links__node__statistics',
   *     '#links' => array(
   *       // An array of links associated with node statistics, suitable for
   *       // passing in to links.html.twig.
   *     ),
   *   ),
   *   'translation' => array(
   *     '#theme' => 'links__node__translation',
   *     '#links' => array(
   *       // An array of links associated with node translation, suitable for
   *       // passing in to links.html.twig.
   *     ),
   *   ),
   * );
   * @endcode
   *
   * In this example, the links are grouped by functionality, which can be
   * helpful to themers who want to display certain kinds of links
   * independently. For example, adding this code to node.html.twig will result
   * in the comment links being rendered as a single list:
   * @code
   * {{ content.links.comment }}
   * @endcode
   *
   * (where a node's content has been transformed into $content before handing
   * control to the node.html.twig template).
   *
   * The preRenderLinks method defined here allows the above flexibility, but
   * also allows the following code to be used to render all remaining links
   * into a single list, regardless of their group:
   * @code
   * {{ content.links }}
   * @endcode
   *
   * In the above example, this will result in the statistics and translation
   * links being rendered together in a single list (but not the comment links,
   * which were rendered previously on their own).
   *
   * Because of the way this method works, the individual properties of each
   * group (for example, a group-specific #theme property such as
   * 'links__node__comment' in the example above, or any other property such as
   * #attributes or #pre_render that is attached to it) are only used when that
   * group is rendered on its own. When the group is rendered together with
   * other children, these child-specific properties are ignored, and only the
   * overall properties of the parent are used.
   *
   * @param array $element
   *   Render array containing child links to group.
   *
   * @return array
   *   Render array containing child links grouped into a single array.
   */
  public static function preRenderLinks($element) {
    $element += ['#links' => [], '#attached' => []];
    foreach (Element::children($element) as $key) {
      $child = &$element[$key];
      // If the child has links which have not been printed yet and the user has
      // access to it, merge its links in to the parent.
      if (isset($child['#links']) && empty($child['#printed']) && Element::isVisibleElement($child)) {
        $element['#links'] += $child['#links'];
        // Mark the child as having been printed already (so that its links
        // cannot be mistakenly rendered twice).
        $child['#printed'] = TRUE;
      }
      // Merge attachments.
      if (isset($child['#attached'])) {
        $element['#attached'] = BubbleableMetadata::mergeAttachments($element['#attached'], $child['#attached']);
      }
    }
    return $element;
  }

}
