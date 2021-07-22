/**
 * Attaches behaviors for the Tracker module's History module integration.
 *
 * May only be loaded for authenticated users, with the History module enabled.
 */
(function ($, Drupal, window) {
  function processNodeNewIndicators(placeholders) {
    const newNodeString = Drupal.t('new');
    const updatedNodeString = Drupal.t('updated');

    placeholders.forEach((placeholder) => {
      const timestamp = parseInt(
        placeholder.getAttribute('data-history-node-timestamp'),
        10,
      );
      const nodeID = placeholder.getAttribute('data-history-node-id');
      const lastViewTimestamp = Drupal.history.getLastRead(nodeID);

      if (timestamp > lastViewTimestamp) {
        const message =
          lastViewTimestamp === 0 ? newNodeString : updatedNodeString;
        $(placeholder).append(`<span class="marker">${message}</span>`);
      }
    });
  }

  function processNewRepliesIndicators(placeholders) {
    // Figure out which placeholders need the "x new" replies links.
    const placeholdersToUpdate = {};
    placeholders.forEach((placeholder) => {
      const timestamp = parseInt(
        placeholder.getAttribute('data-history-node-last-comment-timestamp'),
        10,
      );
      const nodeID = placeholder.previousSibling.previousSibling.getAttribute(
        'data-history-node-id',
      );
      const lastViewTimestamp = Drupal.history.getLastRead(nodeID);

      // Queue this placeholder's "X new" replies link to be downloaded from the
      // server.
      if (timestamp > lastViewTimestamp) {
        placeholdersToUpdate[nodeID] = placeholder;
      }
    });

    // Perform an AJAX request to retrieve node view timestamps.
    const nodeIDs = Object.keys(placeholdersToUpdate);
    if (nodeIDs.length === 0) {
      return;
    }
    $.ajax({
      url: Drupal.url('comments/render_new_comments_node_links'),
      type: 'POST',
      data: { 'node_ids[]': nodeIDs },
      dataType: 'json',
      success(results) {
        Object.keys(results || {}).forEach((nodeID) => {
          if (placeholdersToUpdate.hasOwnProperty(nodeID)) {
            const url = results[nodeID].first_new_comment_link;
            const text = Drupal.formatPlural(
              results[nodeID].new_comment_count,
              '1 new',
              '@count new',
            );
            $(placeholdersToUpdate[nodeID]).append(
              `<br /><a href="${url}">${text}</a>`,
            );
          }
        });
      },
    });
  }

  /**
   * Render "new" and "updated" node indicators, as well as "X new" replies links.
   */
  Drupal.behaviors.trackerHistory = {
    attach(context) {
      // Find all "new" comment indicator placeholders newer than 30 days ago that
      // have not already been read after their last comment timestamp.
      const nodeIDs = [];
      const nodeNewPlaceholders = once(
        'history',
        '[data-history-node-timestamp]',
        context,
      ).filter((placeholder) => {
        const nodeTimestamp = parseInt(
          placeholder.getAttribute('data-history-node-timestamp'),
          10,
        );
        const nodeID = placeholder.getAttribute('data-history-node-id');
        if (Drupal.history.needsServerCheck(nodeID, nodeTimestamp)) {
          nodeIDs.push(nodeID);
          return true;
        }

        return false;
      });

      // Find all "new" comment indicator placeholders newer than 30 days ago that
      // have not already been read after their last comment timestamp.
      const newRepliesPlaceholders = once(
        'history',
        '[data-history-node-last-comment-timestamp]',
        context,
      ).filter((placeholder) => {
        const lastCommentTimestamp = parseInt(
          placeholder.getAttribute('data-history-node-last-comment-timestamp'),
          10,
        );
        const nodeTimestamp = parseInt(
          placeholder.previousSibling.previousSibling.getAttribute(
            'data-history-node-timestamp',
          ),
          10,
        );
        // Discard placeholders that have zero comments.
        if (lastCommentTimestamp === nodeTimestamp) {
          return false;
        }
        const nodeID = placeholder.previousSibling.previousSibling.getAttribute(
          'data-history-node-id',
        );
        if (Drupal.history.needsServerCheck(nodeID, lastCommentTimestamp)) {
          if (nodeIDs.indexOf(nodeID) === -1) {
            nodeIDs.push(nodeID);
          }
          return true;
        }

        return false;
      });

      if (
        nodeNewPlaceholders.length === 0 &&
        newRepliesPlaceholders.length === 0
      ) {
        return;
      }

      // Fetch the node read timestamps from the server.
      Drupal.history.fetchTimestamps(nodeIDs, () => {
        processNodeNewIndicators(nodeNewPlaceholders);
        processNewRepliesIndicators(newRepliesPlaceholders);
      });
    },
  };
})(jQuery, Drupal, window);
