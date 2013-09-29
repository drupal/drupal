README
======

This module contains tools for developers using access control modules
to restrict access to some nodes.  It is intended to help catch some
common mistakes and provide feedback to confirm that restricted nodes
are in fact visible only to the intended users.

Provides a summary page which queries the node_access table and
reports common mistakes such as the presence of Drupal's default entry
which grants all users read access to all nodes.  Also reports the
presence of nodes not represented in node_access table.  This may
occur when an access control module is installed after nodes have
already been created.

Provides a block which shows all node_access entries for the nodes
shown on a given page.  This gives developers a quick check to see
that grants are provided as they should be.  This block auto-enables
to the footer region. You may move it as desired.

If Views module is installed, allows browsing of nodes by realm,
including those nodes not in the node_access table (NULL realm).

WISHLIST
========

Things I'd like to see but haven't had time to do:

* Automatically solve common problems.  I.e. delete the "all" realm
  entry, and automatically save all nodes not in the node_access table.

* Nicer feedback indicating whether nodes are visible to the public or
  not.  I.e. use color coding or icons.

* Summary does not differentiate between view grants and other types
  of grants.  I personally use node_access only for view grants so I'm
  not sure exactly what else it should show.

AUTHOR
======

Dave Cohen AKA yogadex on drupal.org
