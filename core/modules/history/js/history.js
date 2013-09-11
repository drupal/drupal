/**
 * JavaScript API for the History module, with client-side caching.
 *
 * May only be loaded for authenticated users, with the History module enabled.
 */
(function ($, Drupal, drupalSettings, storage) {

"use strict";

var currentUserID = parseInt(drupalSettings.user.uid, 10);

// Any comment that is older than 30 days is automatically considered read,
// so for these we don't need to perform a request at all!
var thirtyDaysAgo = Math.round(new Date().getTime() / 1000) - 30 * 24 * 60 * 60;

Drupal.history = {

  /**
   * Fetch "last read" timestamps for the given nodes.
   *
   * @param Array nodeIDs
   *   An array of node IDs.
   * @param Function callback
   *   A callback that is called after the requested timestamps were fetched.
   */
  fetchTimestamps: function (nodeIDs, callback) {
    $.ajax({
      url: Drupal.url('history/get_node_read_timestamps'),
      type: 'POST',
      data: { 'node_ids[]' : nodeIDs },
      dataType: 'json',
      success: function (results) {
        for (var nodeID in results) {
          if (results.hasOwnProperty(nodeID)) {
            storage.setItem('Drupal.history.' + currentUserID + '.' + nodeID, results[nodeID]);
          }
        }
        callback();
      }
    });
  },

  /**
   * Get the last read timestamp for the given node.
   *
   * @param Number|String nodeID
   *   A node ID.
   *
   * @return Number
   *   A UNIX timestamp.
   */
  getLastRead: function (nodeID) {
    return parseInt(storage.getItem('Drupal.history.' + currentUserID + '.' + nodeID) || 0, 10);
  },

  /**
   * Marks a node as read, store the last read timestamp in client-side storage.
   *
   * @param Number|String nodeID
   *   A node ID.
   */
  markAsRead: function (nodeID) {
    $.ajax({
      url: Drupal.url('history/' + nodeID + '/read'),
      type: 'POST',
      dataType: 'json',
      success: function (timestamp) {
        storage.setItem('Drupal.history.' + currentUserID + '.' + nodeID, timestamp);
      }
    });
  },

  /**
   * Determines whether a server check is necessary.
   *
   * Any content that is >30 days old never gets a "new" or "updated" indicator.
   * Any content that was published before the oldest known reading also never
   * gets a "new" or "updated" indicator, because it must've been read already.
   *
   * @param Number|String nodeID
   *   A node ID.
   * @param Number contentTimestamp
   *   The time at which some content (e.g. a comment) was published.
   *
   * @return Boolean
   *   Whether a server check is necessary for the given node and its timestamp.
   */
  needsServerCheck: function (nodeID, contentTimestamp) {
    // First check if the content is older than 30 days, then we can bail early.
    if (contentTimestamp < thirtyDaysAgo) {
      return false;
    }
    var minLastReadTimestamp = parseInt(storage.getItem('Drupal.history.' + currentUserID + '.' + nodeID) || 0, 10);
    return contentTimestamp > minLastReadTimestamp;
  }
};

})(jQuery, Drupal, drupalSettings, window.localStorage);
