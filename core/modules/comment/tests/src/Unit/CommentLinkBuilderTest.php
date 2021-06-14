<?php

namespace Drupal\Tests\comment\Unit;

use Drupal\comment\CommentLinkBuilder;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\Tests\Traits\Core\GeneratePermutationsTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\comment\CommentLinkBuilder
 * @group comment
 */
class CommentLinkBuilderTest extends UnitTestCase {

  use GeneratePermutationsTrait;

  /**
   * Comment manager mock.
   *
   * @var \Drupal\comment\CommentManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $commentManager;

  /**
   * String translation mock.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Module handler mock.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * Current user proxy mock.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * Timestamp used in test.
   *
   * @var int
   */
  protected $timestamp;

  /**
   * @var \Drupal\comment\CommentLinkBuilderInterface
   */
  protected $commentLinkBuilder;

  /**
   * Prepares mocks for the test.
   */
  protected function setUp(): void {
    $this->commentManager = $this->createMock('\Drupal\comment\CommentManagerInterface');
    $this->stringTranslation = $this->getStringTranslationStub();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->moduleHandler = $this->createMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $this->currentUser = $this->createMock('\Drupal\Core\Session\AccountProxyInterface');
    $this->commentLinkBuilder = new CommentLinkBuilder($this->currentUser, $this->commentManager, $this->moduleHandler, $this->stringTranslation, $this->entityTypeManager);
    $this->commentManager->expects($this->any())
      ->method('getFields')
      ->with('node')
      ->willReturn([
        'comment' => [],
      ]);
    $this->commentManager->expects($this->any())
      ->method('forbiddenMessage')
      ->willReturn("Can't let you do that Dave.");
    $this->stringTranslation->expects($this->any())
      ->method('formatPlural')
      ->willReturnArgument(1);
  }

  /**
   * Tests the buildCommentedEntityLinks method.
   *
   * @param \Drupal\node\NodeInterface|\PHPUnit\Framework\MockObject\MockObject $node
   *   Mock node.
   * @param array $context
   *   Context for the links.
   * @param bool $has_access_comments
   *   TRUE if the user has 'access comments' permission.
   * @param bool $history_exists
   *   TRUE if the history module exists.
   * @param bool $has_post_comments
   *   TRUE if the use has 'post comments' permission.
   * @param bool $is_anonymous
   *   TRUE if the user is anonymous.
   * @param array $expected
   *   Array of expected links keyed by link ID. Can be either string (link
   *   title) or array of link properties.
   *
   * @dataProvider getLinkCombinations
   *
   * @covers ::buildCommentedEntityLinks
   */
  public function testCommentLinkBuilder(NodeInterface $node, $context, $has_access_comments, $history_exists, $has_post_comments, $is_anonymous, $expected) {
    $this->moduleHandler->expects($this->any())
      ->method('moduleExists')
      ->with('history')
      ->willReturn($history_exists);
    $this->currentUser->expects($this->any())
      ->method('hasPermission')
      ->willReturnMap([
        ['access comments', $has_access_comments],
        ['post comments', $has_post_comments],
      ]);
    $this->currentUser->expects($this->any())
      ->method('isAuthenticated')
      ->willReturn(!$is_anonymous);
    $this->currentUser->expects($this->any())
      ->method('isAnonymous')
      ->willReturn($is_anonymous);
    $links = $this->commentLinkBuilder->buildCommentedEntityLinks($node, $context);
    if (!empty($expected)) {
      if (!empty($links)) {
        foreach ($expected as $link => $detail) {
          if (is_array($detail)) {
            // Array of link attributes.
            foreach ($detail as $key => $value) {
              $this->assertEquals($value, $links['comment__comment']['#links'][$link][$key]);
            }
          }
          else {
            // Just the title.
            $this->assertEquals($detail, $links['comment__comment']['#links'][$link]['title']);
          }
        }
      }
      else {
        $this->fail('Expected links but found none.');
      }
    }
    else {
      $this->assertSame($links, $expected);
    }
  }

  /**
   * Data provider for ::testCommentLinkBuilder.
   */
  public function getLinkCombinations() {
    $cases = [];
    // No links should be created if the entity doesn't have the field.
    $cases[] = [
      $this->getMockNode(FALSE, CommentItemInterface::OPEN, CommentItemInterface::FORM_BELOW, 1),
      ['view_mode' => 'teaser'],
      TRUE,
      TRUE,
      TRUE,
      TRUE,
      [],
    ];
    foreach (['search_result', 'search_index', 'print'] as $view_mode) {
      // Nothing should be output in these view modes.
      $cases[] = [
        $this->getMockNode(TRUE, CommentItemInterface::OPEN, CommentItemInterface::FORM_BELOW, 1),
        ['view_mode' => $view_mode],
        TRUE,
        TRUE,
        TRUE,
        TRUE,
        [],
      ];
    }
    // All other combinations.
    $combinations = [
      'is_anonymous' => [FALSE, TRUE],
      'comment_count' => [0, 1],
      'has_access_comments' => [0, 1],
      'history_exists' => [FALSE, TRUE],
      'has_post_comments'   => [0, 1],
      'form_location'            => [CommentItemInterface::FORM_BELOW, CommentItemInterface::FORM_SEPARATE_PAGE],
      'comments'        => [
        CommentItemInterface::OPEN,
        CommentItemInterface::CLOSED,
        CommentItemInterface::HIDDEN,
      ],
      'view_mode' => [
        'teaser', 'rss', 'full',
      ],
    ];
    $permutations = $this->generatePermutations($combinations);
    foreach ($permutations as $combination) {
      $case = [
        $this->getMockNode(TRUE, $combination['comments'], $combination['form_location'], $combination['comment_count']),
        ['view_mode' => $combination['view_mode']],
        $combination['has_access_comments'],
        $combination['history_exists'],
        $combination['has_post_comments'],
        $combination['is_anonymous'],
      ];
      $expected = [];
      // When comments are enabled in teaser mode, and comments exist, and the
      // user has access - we can output the comment count.
      if ($combination['comments'] && $combination['view_mode'] == 'teaser' && $combination['comment_count'] && $combination['has_access_comments']) {
        $expected['comment-comments'] = '1 comment';
        // And if history module exists, we can show a 'new comments' link.
        if ($combination['history_exists']) {
          $expected['comment-new-comments'] = '';
        }
      }
      // All view modes other than RSS.
      if ($combination['view_mode'] != 'rss') {
        // Where commenting is open.
        if ($combination['comments'] == CommentItemInterface::OPEN) {
          // And the user has post-comments permission.
          if ($combination['has_post_comments']) {
            // If the view mode is teaser, or the user can access comments and
            // comments exist or the form is on a separate page.
            if ($combination['view_mode'] == 'teaser' || ($combination['has_access_comments'] && $combination['comment_count']) || $combination['form_location'] == CommentItemInterface::FORM_SEPARATE_PAGE) {
              // There should be an add comment link.
              $expected['comment-add'] = ['title' => 'Add new comment'];
              if ($combination['form_location'] == CommentItemInterface::FORM_BELOW) {
                // On the same page.
                $expected['comment-add']['url'] = Url::fromRoute('node.view');
              }
              else {
                // On a separate page.
                $expected['comment-add']['url'] = Url::fromRoute('comment.reply', ['entity_type' => 'node', 'entity' => 1, 'field_name' => 'comment']);
              }
            }
          }
          elseif ($combination['is_anonymous']) {
            // Anonymous users get the forbidden message if the can't post
            // comments.
            $expected['comment-forbidden'] = "Can't let you do that Dave.";
          }
        }
      }

      $case[] = $expected;
      $cases[] = $case;
    }
    return $cases;
  }

  /**
   * Builds a mock node based on given scenario.
   *
   * @param bool $has_field
   *   TRUE if the node has the 'comment' field.
   * @param int $comment_status
   *   One of CommentItemInterface::OPEN|HIDDEN|CLOSED
   * @param int $form_location
   *   One of CommentItemInterface::FORM_BELOW|FORM_SEPARATE_PAGE
   * @param int $comment_count
   *   Number of comments against the field.
   *
   * @return \Drupal\node\NodeInterface|\PHPUnit\Framework\MockObject\MockObject
   *   Mock node for testing.
   */
  protected function getMockNode($has_field, $comment_status, $form_location, $comment_count) {
    $node = $this->createMock('\Drupal\node\NodeInterface');
    $node->expects($this->any())
      ->method('hasField')
      ->willReturn($has_field);

    if (empty($this->timestamp)) {
      $this->timestamp = time();
    }
    $field_item = (object) [
      'status' => $comment_status,
      'comment_count' => $comment_count,
      'last_comment_timestamp' => $this->timestamp,
    ];
    $node->expects($this->any())
      ->method('get')
      ->with('comment')
      ->willReturn($field_item);

    $field_definition = $this->createMock('\Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition->expects($this->any())
      ->method('getSetting')
      ->with('form_location')
      ->willReturn($form_location);
    $node->expects($this->any())
      ->method('getFieldDefinition')
      ->with('comment')
      ->willReturn($field_definition);

    $node->expects($this->any())
      ->method('language')
      ->willReturn('und');

    $node->expects($this->any())
      ->method('getEntityTypeId')
      ->willReturn('node');

    $node->expects($this->any())
      ->method('id')
      ->willReturn(1);

    $url = Url::fromRoute('node.view');
    $node->expects($this->any())
      ->method('toUrl')
      ->willReturn($url);

    return $node;
  }

}

namespace Drupal\comment;

if (!function_exists('history_read')) {

  function history_read() {
    return 0;
  }

}
