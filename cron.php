<?

include "includes/theme.inc";
include "includes/cron.inc";

$result = db_query("SELECT * FROM cron");

while ($cron = db_fetch_object($result)) {
  if (time() - $cron->timestamp > $cron->scheduled) cron_execute($cron);
}

?>
