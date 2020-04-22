<?php
class DB {
  protected $link_id;

  public function __construct($host, $user, $pass, $dbname){
     global $usepconnect;
     $this->link_id = @mysql_connect($host,$user,$pass) or die('Connect db error');
     mysql_select_db($dbname);
  }

  public function query($sql) {
    $queryid = mysql_query($sql,$this->link_id);
    return $queryid;
  }

  public function fetch_array($sql) {
    $data = array();
    $queryid = mysql_query($sql,$this->link_id);
    while ($rs = mysql_fetch_array($queryid)) {
      $data[] = $rs;
    }

    return $data;
  }

  public function fetch_row($sql) {
    $queryid = mysql_query($sql,$this->link_id);
    return mysql_fetch_array($queryid);
  }

  public function num_rows($sql) {
    $queryid = mysql_query($sql,$this->link_id);
    return mysql_num_rows($queryid);
  }


  public function affected_rows() {
    return  mysql_affected_rows($this->link_id);
  }

  public function get_insert_id(){
    return mysql_insert_id();
  }

  public function close() {
    @mysql_close($this->link_id);
  }
}
