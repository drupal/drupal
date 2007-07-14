// $Id$

if (Drupal.jsEnabled) {
  $(function() {
    // Automatically selects the right radio button in the filter section of
    // the admin content list.
    $('#node-admin-filter select').change(function() {
        $('#node-admin-filter input:radio[@value="'+ this.id.substr(5) +'"]')
          .attr('checked', true);
    });
  });
}
