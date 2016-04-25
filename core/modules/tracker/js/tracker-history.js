/**
 * Attaches behaviors for the Tracker module's History module integration.
 *
 * May only be loaded for authenticated users, with the History module enabled.
 */
(function ($, Drupal, window) {

  'use strict';

  /**
   * Render "new" and "updated" node indicators, as well as "X new" replies links.
   */
  Drupal.behaviors.trackerHistory = {
    attach: function (context) {
      // Find all "new" comment indicator placeholders newer than 30 days ago that
      // have not already been read after their last comment timestamp.
      var nodeIDs = [];
      var $nodeNewPlaceholders = $(context)
        .find('[data-history-node-timestamp]')
        .once('history')
        .filter(function () {
          var nodeTimestamp = parseInt(this.getAttribute('data-history-node-timestamp'), 10);
          var nodeID = this.getAttribute('data-history-node-id');
          if (Drupal.history.needsServerCheck(nodeID, nodeTimestamp)) {
            nodeIDs.push(nodeID);
            return true;
          }
          else {
            return false;
          }
        });

      // Find all "new" comment indicator placeholders newer than 30 days ago that
      // have not already been read after their last comment timestamp.
      var $newRepliesPlaceholders = $(context)
        .find('[data-history-node-last-comment-timestamp]')
        .once('history')
        .filter(function () {
          var lastCommentTimestamp = parseInt(this.getAttribute('data-history-node-last-comment-timestamp'), 10);
          var nodeTimestamp = parseInt(this.previousSibling.previousSibling.getAttribute('data-history-node-timestamp'), 10);
          // Discard placeholders that have zero comments.
          if (lastCommentTimestamp === nodeTimestamp) {
            return false;
          }
          var nodeID = this.previousSibling.previousSibling.getAttribute('data-history-node-id');
          if (Drupal.history.needsServerCheck(nodeID, lastCommentTimestamp)) {
            if (nodeIDs.indexOf(nodeID) === -1) {
              nodeIDs.push(nodeID);
            }
            return true;
          }
          else {
            return false;
          }
        });

      if ($nodeNewPlaceholders.length === 0 && $newRepliesPlaceholders.length === 0) {
        return;
      }

      // Fetch the node read timestamps from the server.
      Drupal.history.fetchTimestamps(nodeIDs, function () {
        processNodeNewIndicators($nodeNewPlaceholders);
        processNewRepliesIndicators($newRepliesPlaceholders);
      });
    }
  };

  function processNodeNewIndicators($placeholders) {
    var newNodeString = Drupal.t('new');
    var updatedNodeString = Drupal.t('updated');

    $placeholders.each(function (index, placeholder) {
      var timestamp = parseInt(placeholder.getAttribute('data-history-node-timestamp'), 10);
      var nodeID = placeholder.getAttribute('data-history-node-id');
      var lastViewTimestamp = Drupal.history.getLastRead(nodeID);

      if (timestamp > lastViewTimestamp) {
        var message = (lastViewTimestamp === 0) ? newNodeString : updatedNodeString;
        $(placeholder).append('<span class="marker">' + message + '</span>');
      }
    });
  }

  function processNewRepliesIndicators($placeholders) {
    // Figure out which placeholders need the "x new" replies links.
    var placeholdersToUpdate = {};
    $placeholders.each(function (index, placeholder) {
      var timestamp = parseInt(placeholder.getAttribute('data-history-node-last-comment-timestamp'), 10);
      var nodeID = placeholder.previousSibling.previousSibling.getAttribute('data-history-node-id');
      var lastViewTimestamp = Drupal.history.getLastRead(nodeID);

      // Queue this placeholder's "X new" replies link to be downloaded from the
      // server.
      if (timestamp > lastViewTimestamp) {
        placeholdersToUpdate[nodeID] = placeholder;
      }
    });

    // Perform an AJAX request to retrieve node view timestamps.
    var nodeIDs = Object.keys(placeholdersToUpdate);
    if (nodeIDs.length === 0) {
      return;
    }
    $.ajax({
      url: Drupal.url('comments/render_new_comments_node_links'),
      type: 'POST',
      data: {'node_ids[]': nodeIDs},
      dataType: 'json',
      success: function (results) {
        for (var nodeID in results) {
          if (results.hasOwnProperty(nodeID) && placeholdersToUpdate.hasOwnProperty(nodeID)) {
            var url = results[nodeID].first_new_comment_link;
            var text = Drupal.formatPlural(results[nodeID].new_comment_count, '1 new', '@count new');
            $(placeholdersToUpdate[nodeID]).append('<br /><a href="' + url + '">' + text + '</a>');
          }
        }
      }
    });
  }

})(jQuery, Drupal, window);
