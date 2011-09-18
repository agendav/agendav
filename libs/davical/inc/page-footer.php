</body><?php
  if ( isset($c->scripts) && is_array($c->scripts) ) {
    foreach ( $c->scripts AS $script ) {
      echo "<script language=\"JavaScript\" src=\"$script\"></script>\n";
    }
  }
  if ( isset($c->dbg['statistics']) && $c->dbg['statistics'] ) {
    $script_time = microtime(true) - $c->script_start_time;
    @dbg_error_log("statistics", "Method: %s, Status: %d, Script: %5.3lfs, Queries: %5.3lfs, URL: %s",
                        $_SERVER['REQUEST_METHOD'], 200, $script_time, $c->total_query_time, $_SERVER['REQUEST_URI']);
  }
?>
</html>