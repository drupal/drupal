/**
 * Attaches behaviors for the Comment module's "X new comments" link.
 *
 * May only be loaded for authenticated users, with the History module enabled.
 */
(function ($, Drupal) {

  "use strict";

  /**
   * Render "X new comments" links wherever necessary.
   */
  Drupal.behaviors.nodeNewCommentsLink = {
    attach: function (context) {
      // Collect all "X new comments" node link placeholders (and their
      // corresponding node IDs) newer than 30 days ago that have not already been
      // read after their last comment timestamp.
      var nodeIDs = [];
      var $placeholders = $(context)
        .find('[data-history-node-last-comment-timestamp]')
        .once('history')
        .filter(function () {
          var $placeholder = $(this);
          var lastCommentTimestamp = parseInt($placeholder.attr('data-history-node-last-comment-timestamp'), 10);
          var nodeID = $placeholder.closest('[data-history-node-id]').attr('data-history-node-id');
          if (Drupal.history.needsServerCheck(nodeID, lastCommentTimestamp)) {
            nodeIDs.push(nodeID);
            // Hide this placeholder link until it is certain we'll need it.
            hide($placeholder);
            return true;
          }
          else {
            // Remove this placeholder link from the DOM because we won't need it.
            remove($placeholder);
            return false;
          }
        });

      if ($placeholders.length === 0) {
        return;
      }

      // Perform an AJAX request to retrieve node read timestamps.
      Drupal.history.fetchTimestamps(nodeIDs, function () {
        processNodeNewCommentLinks($placeholders);
      });
    }
  };

  function hide($placeholder) {
    return $placeholder
      // Find the parent <li>.
      .closest('.comment-new-comments')
      // Find the preceding <li>, if any, and give it the 'last' class.
      .prev().addClass('last')
      // Go back to the parent <li> and hide it.
      .end().hide();
  }

  function remove($placeholder) {
    hide($placeholder).remove();
  }

  function show($placeholder) {
    return $placeholder
      // Find the parent <li>.
      .closest('.comment-new-comments')
      // Find the preceding <li>, if any, and remove its 'last' class, if any.
      .prev().removeClass('last')
      // Go back to the parent <li> and show it.
      .end().show();
  }

  function processNodeNewCommentLinks($placeholders) {
    // Figure out which placeholders need the "x new comments" links.
    var $placeholdersToUpdate = {};
    var fieldName = 'comment';
    var $placeholder;
    $placeholders.each(function (index, placeholder) {
      $placeholder = $(placeholder);
      var timestamp = parseInt($placeholder.attr('data-history-node-last-comment-timestamp'), 10);
      fieldName = $placeholder.attr('data-history-node-field-name');
      var nodeID = $placeholder.closest('[data-history-node-id]').attr('data-history-node-id');
      var lastViewTimestamp = Drupal.history.getLastRead(nodeID);

      // Queue this placeholder's "X new comments" link to be downloaded from the
      // server.
      if (timestamp > lastViewTimestamp) {
        $placeholdersToUpdate[nodeID] = $placeholder;
      }
      // No "X new comments" link necessary; remove it from the DOM.
      else {
        remove($placeholder);
      }
    });

    // Perform an AJAX request to retrieve node view timestamps.
    var nodeIDs = Object.keys($placeholdersToUpdate);
    if (nodeIDs.length === 0) {
      return;
    }

    // Render the "X new comments" links. Either use the data embedded in the page
    // or perform an AJAX request to retrieve the same data.
    function render(results) {
      for (var nodeID in results) {
        if (results.hasOwnProperty(nodeID) && $placeholdersToUpdate.hasOwnProperty(nodeID)) {
          $placeholdersToUpdate[nodeID]
            .attr('href', results[nodeID].first_new_comment_link)
            .text(Drupal.formatPlural(results[nodeID].new_comment_count, '1 new comment', '@count new comments'))
            .removeClass('hidden');
          show($placeholdersToUpdate[nodeID]);
        }
      }
    }

    if (drupalSettings.comment && drupalSettings.comment.newCommentsLinks) {
      render(drupalSettings.comment.newCommentsLinks.node[fieldName]);
    }
    else {
      $.ajax({
        url: Drupal.url('comments/render_new_comments_node_links'),
        type: 'POST',
        data: { 'node_ids[]': nodeIDs, 'field_name': fieldName },
        dataType: 'json',
        success: render
      });
    }
  }

})(jQuery, Drupal);
