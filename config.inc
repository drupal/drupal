<?PHP

#
# MySQL settings:
#
$dbhost = "zind.net";
$dbuname = "dries";
$dbpass = "Abc123";
$dbname = "dries";

#
# Name of the site
#
$sitename = "drop.org";

#
# Contact information:
#  The contact information will be used to send out automated mails
#  to users, account holders or visitors.
$contact_email = "droppies@zind.net";
$contact_signature = "Kind regards,\n\n-- the drop.org crew\nhttp://www.drop.org/";

#
# Notify information:
#  The notify information will be used to send out automated mails
#  for internal purpose.
#
$notify_email   = $contact_email;
$notify_subject = "submission: ";
$notify_message = "New submission: '$subject'\n\n$story";
$notify_from    = "droppies@zind.net";

#
# Comment meta reasons:
#   The comment meta reasons are the various meta reasons used to
#   moderate comments.  The array should always be 'balanced': that
#   the number of good reasons should equal the number of bad reason
#   and those should be ordered from bad to good.
#
$comments_meta_reasons = array('Off topic', 'Redundant', 'Insightfull', 'As is', 'Interesting', 'Informative', 'Funny');

#
# Categories:
#
$categories = array('Announcements', 'Coding', 'Geeking', 'Drop.org', 'Gaming', 'Girls', 'Graphics', 'Hardware', 'Humor', 'Internet', 'Music', 'Movies', 'Politics', 'Science', 'Software', 'Space', 'Webdesign', 'Quickies');

#
# Notify:
#   Set to '1' to receive an e-mail when news has been submitted
#   through submit.php
#
$notify = 1; 

# 
# Allowed HTML tags:
#
$allowableHTML   = array('B','/B','I','/I','P .*','P','/P','A .*','/A','LI','OL','/OL','UL','/UL','EM','/EM','BR','STRONG','/STRONG','BLOCKQUOTE','/BLOCKQUOTE','HR','DIV .*','DIV','/DIV','TT','/TT');

#
# Name of the 'anonymous' user account:
#
$anonymous = "Anonymous Chicken";

# 
# Debug flag:
#   Set to '1' if you are using Windows so the engine won't try
#   to send out mails and such.  When using Unix or Linux, set
#   to '0'
$system = 1;

?>