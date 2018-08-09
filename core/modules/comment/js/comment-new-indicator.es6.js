/**
 * @file
 * Attaches behaviors for the Comment module's "new" indicator.
 *
 * May only be loaded for authenticated users, with the History module
 * installed.
 */

(function($, Drupal, window) {
  /**
   * Processes the markup for "new comment" indicators.
   *
   * @param {jQuery} $placeholders
   *   The elements that should be processed.
   */
  function processCommentNewIndicators($placeholders) {
    let isFirstNewComment = true;
    const newCommentString = Drupal.t('new');
    let $placeholder;

    $placeholders.each((index, placeholder) => {
      $placeholder = $(placeholder);
      const timestamp = parseInt(
        $placeholder.attr('data-comment-timestamp'),
        10,
      );
      const $node = $placeholder.closest('[data-history-node-id]');
      const nodeID = $node.attr('data-history-node-id');
      const lastViewTimestamp = Drupal.history.getLastRead(nodeID);

      if (timestamp > lastViewTimestamp) {
        // Turn the placeholder into an actual "new" indicator.
        const $comment = $(placeholder)
          .removeClass('hidden')
          .text(newCommentString)
          .closest('.js-comment')
          // Add 'new' class to the comment, so it can be styled.
          .addClass('new');

        // Insert "new" anchor just before the "comment-<cid>" anchor if
        // this is the first new comment in the DOM.
        if (isFirstNewComment) {
          isFirstNewComment = false;
          $comment.prev().before('<a id="new" />');
          // If the URL points to the first new comment, then scroll to that
          // comment.
          if (window.location.hash === '#new') {
            window.scrollTo(
              0,
              $comment.offset().top - Drupal.displace.offsets.top,
            );
          }
        }
      }
    });
  }

  /**
   * Renders "new" comment indicators wherever necessary.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches "new" comment indicators behavior.
   */
  Drupal.behaviors.commentNewIndicator = {
    attach(context) {
      // Collect all "new" comment indicator placeholders (and their
      // corresponding node IDs) newer than 30 days ago that have not already
      // been read after their last comment timestamp.
      const nodeIDs = [];
      const $placeholders = $(context)
        .find('[data-comment-timestamp]')
        .once('history')
        .filter(function() {
          const $placeholder = $(this);
          const commentTimestamp = parseInt(
            $placeholder.attr('data-comment-timestamp'),
            10,
          );
          const nodeID = $placeholder
            .closest('[data-history-node-id]')
            .attr('data-history-node-id');
          if (Drupal.history.needsServerCheck(nodeID, commentTimestamp)) {
            nodeIDs.push(nodeID);
            return true;
          }

          return false;
        });

      if ($placeholders.length === 0) {
        return;
      }

      // Fetch the node read timestamps from the server.
      Drupal.history.fetchTimestamps(nodeIDs, () => {
        processCommentNewIndicators($placeholders);
      });
    },
  };
})(jQuery, Drupal, window);
