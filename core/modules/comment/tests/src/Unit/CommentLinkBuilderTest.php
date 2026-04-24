<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Unit;

use Drupal\comment\CommentingStatus;
use Drupal\comment\CommentLinkBuilder;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\FormLocation;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\Tests\Traits\Core\GeneratePermutationsTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\comment\CommentLinkBuilder.
 */
#[CoversClass(CommentLinkBuilder::class)]
#[Group('comment')]
class CommentLinkBuilderTest extends UnitTestCase {

  use GeneratePermutationsTrait;

  /**
   * Comment manager mock.
   *
   * @var \Drupal\comment\CommentManagerInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $commentManager;

  /**
   * String translation mock.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * Current user proxy mock.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $currentUser;

  /**
   * Timestamp used in test.
   *
   * @var int
   */
  protected $timestamp;

  /**
   * The comment link builder.
   *
   * @var \Drupal\comment\CommentLinkBuilderInterface
   */
  protected $commentLinkBuilder;

  /**
   * Prepares mocks for the test.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->commentManager = $this->createStub(CommentManagerInterface::class);
    $this->stringTranslation = $this->getStringTranslationStub();
    $this->currentUser = $this->createStub(AccountProxyInterface::class);
    $this->commentLinkBuilder = new CommentLinkBuilder($this->currentUser, $this->commentManager, $this->stringTranslation);
    $this->commentManager
      ->method('getFields')
      ->willReturn([
        'comment' => [],
      ]);
    $this->commentManager
      ->method('forbiddenMessage')
      ->willReturn("Can't let you do that Dave.");
    $this->stringTranslation
      ->method('formatPlural')
      ->willReturnArgument(1);
  }

  /**
   * Tests the buildCommentedEntityLinks method.
   *
   * @param array $node_args
   *   Arguments for the mock node.
   * @param array $context
   *   Context for the links.
   * @param bool $has_access_comments
   *   TRUE if the user has 'access comments' permission.
   * @param bool $has_post_comments
   *   TRUE if the use has 'post comments' permission.
   * @param bool $is_anonymous
   *   TRUE if the user is anonymous.
   * @param array $expected
   *   Array of expected links keyed by link ID. Can be either string (link
   *   title) or array of link properties.
   *
   * @legacy-covers ::buildCommentedEntityLinks
   */
  #[DataProvider('getLinkCombinations')]
  public function testCommentLinkBuilder(array $node_args, $context, $has_access_comments, $has_post_comments, $is_anonymous, $expected): void {
    $node = $this->getMockNode(...$node_args);
    $this->currentUser
      ->method('hasPermission')
      ->willReturnMap([
        ['access comments', $has_access_comments],
        ['post comments', $has_post_comments],
      ]);
    $this->currentUser
      ->method('isAuthenticated')
      ->willReturn(!$is_anonymous);
    $this->currentUser
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
  public static function getLinkCombinations() {
    $cases = [];
    // No links should be created if the entity doesn't have the field.
    $cases[] = [
      [FALSE, CommentingStatus::Open, FormLocation::Below, 1],
      ['view_mode' => 'teaser'],
      TRUE,
      TRUE,
      TRUE,
      [],
    ];
    foreach (['search_result', 'search_index', 'print'] as $view_mode) {
      // Nothing should be output in these view modes.
      $cases[] = [
        [TRUE, CommentingStatus::Open, FormLocation::Below, 1],
        ['view_mode' => $view_mode],
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
      'has_post_comments'   => [0, 1],
      'form_location' => FormLocation::cases(),
      'comments' => CommentingStatus::cases(),
      'view_mode' => [
        'teaser', 'rss', 'full',
      ],
    ];
    $permutations = static::generatePermutations($combinations);
    foreach ($permutations as $combination) {
      $case = [
        [TRUE, $combination['comments'], $combination['form_location'], $combination['comment_count']],
        ['view_mode' => $combination['view_mode']],
        $combination['has_access_comments'],
        $combination['has_post_comments'],
        $combination['is_anonymous'],
      ];
      $expected = [];
      // When comments are enabled in teaser mode, and comments exist, and the
      // user has access - we can output the comment count.
      if ($combination['comments'] !== CommentingStatus::Hidden && $combination['view_mode'] == 'teaser' && $combination['comment_count'] && $combination['has_access_comments']) {
        $expected['comment-comments'] = '1 comment';
      }
      // All view modes other than RSS.
      if ($combination['view_mode'] != 'rss') {
        // Where commenting is open.
        if ($combination['comments'] == CommentingStatus::Open) {
          // And the user has post-comments permission.
          if ($combination['has_post_comments']) {
            // If the view mode is teaser, or the user can access comments and
            // comments exist or the form is on a separate page.
            if ($combination['view_mode'] == 'teaser' || ($combination['has_access_comments'] && $combination['comment_count']) || $combination['form_location'] == FormLocation::SeparatePage) {
              // There should be an add comment link.
              $expected['comment-add'] = ['title' => 'Add new comment'];
              if ($combination['form_location'] == FormLocation::Below) {
                // On the same page.
                $expected['comment-add']['url'] = Url::fromRoute('node.view');
              }
              else {
                // On a separate page.
                $expected['comment-add']['url'] = Url::fromRoute(
                  'comment.reply',
                  [
                    'entity_type' => 'node',
                    'entity' => 1,
                    'field_name' => 'comment',
                  ]);
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
   * Builds a stub node based on given scenario.
   *
   * @param bool $has_field
   *   TRUE if the node has the 'comment' field.
   * @param \Drupal\comment\CommentingStatus $comment_status
   *   One of the CommentingStatus enum cases.
   * @param \Drupal\comment\FormLocation $form_location
   *   One of the FormLocation enum cases.
   * @param int $comment_count
   *   Number of comments against the field.
   *
   * @return \Drupal\node\NodeInterface|\PHPUnit\Framework\MockObject\Stub
   *   Stub node for testing.
   */
  protected function getMockNode($has_field, $comment_status, $form_location, $comment_count) {
    $node = $this->createStub(NodeInterface::class);
    $node
      ->method('hasField')
      ->willReturn($has_field);

    if (empty($this->timestamp)) {
      $this->timestamp = time();
    }
    $field_item = (object) [
      'status' => $comment_status->value,
      'comment_count' => $comment_count,
      'last_comment_timestamp' => $this->timestamp,
    ];
    $node
      ->method('get')
      ->willReturn($field_item);

    $field_definition = $this->createStub(FieldDefinitionInterface::class);
    $field_definition
      ->method('getSetting')
      ->willReturn($form_location->value);
    $node
      ->method('getFieldDefinition')
      ->willReturn($field_definition);

    $node
      ->method('language')
      ->willReturn('und');

    $node
      ->method('getEntityTypeId')
      ->willReturn('node');

    $node
      ->method('id')
      ->willReturn(1);

    $url = Url::fromRoute('node.view');
    $node
      ->method('toUrl')
      ->willReturn($url);

    return $node;
  }

}
