<?php

/**
 * @file
 * Contains \Drupal\comment\Controller\CommentController.
 */

namespace Drupal\comment\Controller;

use Drupal\comment\CommentInterface;
use Drupal\comment\Plugin\Core\Entity\Comment;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Routing\PathBasedGeneratorInterface;
use Drupal\field\FieldInfo;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for the comment entity.
 *
 * @see \Drupal\comment\Plugin\Core\Entity\Comment.
 */
class CommentController implements ControllerInterface {

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\PathBasedGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Field info service.
   *
   * @var \Drupal\field\FieldInfo
   */
  protected $fieldInfo;

  /**
   * Constructs a CommentController object.
   *
   * @param \Drupal\Core\Routing\PathBasedGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\field\FieldInfo $field_info
   *   Field Info service.
   */
  public function __construct(PathBasedGeneratorInterface $url_generator, FieldInfo $field_info) {
    $this->urlGenerator = $url_generator;
    $this->fieldInfo = $field_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('url_generator'),
      $container->get('field.info')
    );
  }

  /**
   * Publishes the specified comment.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\comment\CommentInterface $comment
   *   A comment entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @return \Symfony\Component\HttpFoundation\RedirectResponse.
   */
  public function commentApprove(Request $request, CommentInterface $comment) {
    // @todo CRSF tokens are validated in the content controller until it gets
    //   moved to the access layer:
    //   Integrate CSRF link token directly into routing system:
    //   https://drupal.org/node/1798296.
    $token = $request->query->get('token');
    if (!isset($token) || !drupal_valid_token($token, 'comment/' . $comment->id() . '/approve')) {
      throw new AccessDeniedHttpException();
    }

    $comment->status->value = COMMENT_PUBLISHED;
    $comment->save();

    drupal_set_message(t('Comment approved.'));
    $permalink_uri = $comment->permalink();
    $permalink_uri['options']['absolute'] = TRUE;
    $url = $this->urlGenerator->generateFromPath($permalink_uri['path'], $permalink_uri['options']);
    return new RedirectResponse($url);
  }

  /**
   * Redirects legacy node links to new path.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node which the comment is a reply to.
   */
  public function redirectNode(EntityInterface $node) {
    $fields = array_filter($this->fieldInfo->getFieldMap(), function ($value) use ($node) {
      if ($value['type'] == 'comment' && isset($value['bundles']['node']) &&
          in_array($node->bundle(), $value['bundles']['node'])) {
        return TRUE;
      }
    });
    // First field will do.
    if (!empty($fields) && ($field_names = array_keys($fields)) && ($field_name = reset($field_names))) {
      return new RedirectResponse(url('comment/reply/node/' . $node->id() . '/' . $field_name, array('absolute' => TRUE)));
    }

    throw new NotFoundHttpException();
  }

}
