<?php
/**
 * @author Sebastian Gomez (tiochan@gmail.com)
 * For: Politechnical University of Catalonia (UPC), Spain.
 *
 * @package lib
 * @subpackage dbms
 *
 * Class for mysql DBMS functions.
 */

	/*
		Author: Sebastian Gomez, (tiochan@gmail.com)
		For: Politechnical University of Catalonia (UPC), Spain.

		Platform independent DBMS final public functions for MySQL
	*/

    !extension_loaded("mysql") and die("mysql extension not loaded.");

	include_once INC_DIR . "dbms/dbms.class.php";



	class mysql_class extends dbms_class {


		function __construct () {
			$this->prevent_errors=false;
		}

		function __destruct() {
			if($this->dbms_is_connected()) @$this->dbms_close();
		}

		final public function dbms_connect($server, $username, $password, $persistent=false, $new_link=false) {

			if($persistent) {
				$this->sgbd_link= mysql_pconnect($server, $username, $password, $new_link);
			} else {
				$this->sgbd_link= mysql_connect($server, $username, $password, $new_link);
			}

			$this->new_link= $new_link;

			if($this->sgbd_link) {
				$this->connected=true;
				$this->server= $server;
				$this->username= $username;
				$this->password= $password;
				$this->persistent= $persistent;
			} else {
				$this->connected=false;
			}
			return $this->sgbd_link;
		}

		final public function dbms_is_connected() {

			return (isset($this->sgbd_link) and $this->sgbd_link);
		}

		final public function dbms_reconnect() {

			if($this->persistent) {
				$this->sgbd_link= mysql_pconnect($this->server, $this->username, $this->password);
			} else {
				$this->sgbd_link= mysql_connect($this->server, $this->username, $this->password, $this->new_link);
			}

			if($this->sgbd_link) $this->connected=true;
			else $this->connected=false;

			return $this->sgbd_link;
		}

		final public function dbms_select_db($database) {

			if(!$this->dbms_is_connected()) return 0;

			$db_ret= mysql_select_db($database);
			$this->db_name= $database;

			return $db_ret;
		}

		final public function dbms_select_last_db() {

			if(!$this->dbms_is_connected()) return 0;

			if(trim($this->db_name)=="") return 0;

			$db_ret= mysql_select_db($this->db_name);

			return $db_ret;
		}

		final public function dbms_close() {
			if(!$this->dbms_is_connected()) return 0;

			@mysql_close($this->sgbd_link);
			$this->connected=false;
			return 1;
		}


		final public function &dbms_query($sql_query,$seq=null) {

			if(!$this->dbms_is_connected()) return 0;
			$this->res= false;

			$this->log_query($sql_query);

			//$sql_query=$this->dbms_escape_string($sql_query);
			$time_start = getmicrotime();

			$this->res = mysql_query($sql_query, $this->sgbd_link);

			$time_end = getmicrotime();
			$time = round($time_end - $time_start, 3);
			$this->show_queryInfo($sql_query, $time);

			$this->last_was_select = !(stripos($sql_query,"select") === false);

			if(!$this->res) $this->show_queryError($sql_query, $this->dbms_error());
			else $this->show_queryResult("Executed Ok.");

			return $this->res;
		}

		final public function dbms_check_result($res) {

			if(!$this->dbms_is_connected()) return 0;

			return($res and ($this->dbms_num_rows($res) > 0));
		}


		final public function dbms_error() {

			if(!$this->dbms_is_connected()) return 0;

			return mysql_error();
		}


		final public function dbms_data_seek($res, $row) {

			if(!$this->dbms_is_connected()) return 0;

			if(!$res) return 0;
			return mysql_data_seek($res, $row);
		}


		final public function dbms_free_result($res) {

			if(!$this->dbms_is_connected()) return 0;

			if(!$res) return 0;

			return mysql_free_result($res);
		}

		final public function dbms_row_count($table, $where="") {

			if(!$this->dbms_is_connected()) return 0;

			$query= "select count(*) from $table";
			if($where != "") $query .= " where $where";

			if(!$res= $this->dbms_query($query)) return false;
			if(!(list($rows)= $this->dbms_fetch_row($res))) return false;
			$this->dbms_free_result($res);

			return $rows;
		}

		final public function dbms_num_rows($res) {

			if(!$this->dbms_is_connected()) return 0;

			return mysql_num_rows($res);
		}


		final public function dbms_num_fields($res) {

			if(!$this->dbms_is_connected()) return 0;

			return mysql_num_fields($res);
		}

		final public function dbms_field_name($res, $field) {

			if(!$this->dbms_is_connected()) return 0;

			return mysql_field_name($res, $field);
		}

		final public function dbms_field_type($res, $field) {

			if(!$this->dbms_is_connected()) return 0;

			return mysql_field_type($res, $field);
		}

		final public function &dbms_fetch_row($res) {

			if(!$this->dbms_is_connected()) return 0;

			$ret= mysql_fetch_row($res);
			return $ret;
		}

		final public function &dbms_fetch_array($res) {

			if(!$this->dbms_is_connected()) return 0;

			$ret= mysql_fetch_array($res);
			return $ret;
		}

		final public function &dbms_fetch_object($res) {

			if(!$this->dbms_is_connected()) return 0;

			$ret= mysql_fetch_object($res);
			return $ret;
		}

		final public function dbms_affected_rows() {

			if(!$this->dbms_is_connected()) return 0;

			return mysql_affected_rows();
		}

		final public function &dbms_get_tables() {

			if(!$this->dbms_is_connected()) return 0;

			$tables= Array();
			$res= mysql_list_tables($this->db_name);

			$i=0;
			while($row= mysql_fetch_row($res)) {
				if($row[3]!="SYSTEM TABLE")
				$tables[$i++]= $row[2];
			}

			return $tables;
		}

		final public function &dbms_get_cols($table) {

			if(!$this->dbms_is_connected()) return 0;

			$cols= Array();
			$res= mysql_list_fields($this->db_name, $table);

			$i=0;
			while($row= mysql_fetch_row($res)) {
				$cols[$i++]= $row[3];
			}

			return $cols;
		}


		/**
		 * On MySQL tables, must be created with engine INNODB to be transactional.
		 *
		 * @return bool
		 */
		final public function dbms_begin_transaction() {

			if(!$this->dbms_is_connected()) return 0;

			return mysql_query("start transaction; begin", $this->sgbd_link);
		}

		/**
		 * On MySQL tables, must be created with engine INNODB to be transactional.
		 *
		 * @return bool
		 */
		final public function dbms_commit() {

			if(!$this->dbms_is_connected()) return 0;

			return mysql_query("commit", $this->sgbd_link);
		}

		/**
		 * On MySQL tables, must be created with engine INNODB to be transactional.
		 *
		 * @return bool
		 */
		final public function dbms_rollback() {

			if(!$this->dbms_is_connected()) return 0;

			return mysql_query("rollback", $this->sgbd_link);
		}

		final public function &dbms_primary_keys($table) {

			if(!$this->dbms_is_connected()) return 0;

			die("method dbms_primary_keys not defined!");
		}

		final public function dbms_concat($separator, $fields) {

			if(!$this->dbms_is_connected()) return 0;

			$arg_list = func_get_args();

			$pre="";
			$ret= "concat(";
			for($i=1; $i < count($arg_list); $i++) {
				$ret.= $pre . $arg_list[$i];
				$pre=",'$separator',";
			}
			$ret.=")";

			return $ret;
		}

		final public function dbms_to_date($date, $format="") {

			// MySQL: select str_to_date('19/06/2006 23:50:51','%d/%m/%Y %H:%i:%s');
			if($format=="") {
				switch(DATE_FORMAT) {
					case "d-m-y":
						$format= "%d/%m/%Y %H:%i:%S";
						break;
					case "m-d-y":
						$format= "%m/%d/%Y %H:%i:%S";
						break;
					case "y-m-d":
						$format= "%Y/%m/%d %H:%i:%S";
						break;
					default:
						$format= "%d/%m/%Y %H:%i:%S";
						break;
				}
			}
			$ret= "STR_TO_DATE('" . $date . "','" . $format . "')";

			return $ret;
		}

		final public function dbms_date_to_string($date, $format="", $use_time=true) {

			// MySQL: to_char(data_creacio,'DD/MM/YYYY HH24:MI:SS')
			if($format=="") {
				switch(DATE_FORMAT) {
					case "d-m-y":
						$format= $use_time ? "%d/%m/%Y %H:%i:%S" : "%d/%m/%Y";
						break;
					case "m-d-y":
						$format= $use_time ? "%m/%d/%Y %H:%i:%S" : "%m/%d/%Y";
						break;
					case "y-m-d":
						$format= $use_time ? "%Y/%m/%d %H:%i:%S" : "%Y/%m/%d";
						break;
					default:
						$format= $use_time ? "%d/%m/%Y %H:%i:%S" : "%d/%m/%Y";
						break;
				}
			}
			$ret= "date_format(" . $date . ",'" . $format . "')";

			return $ret;
		}

		final public function dbms_limit($query, $numrows, $offset=0) {

			if(!$this->dbms_is_connected()) return 0;

			$ret= $query . " LIMIT " . $numrows;
			if($offset) $ret.= " OFFSET " . $offset;

			return $ret;
		}

		final public function dbms_sequence($sequence_name) {

			if(!$this->dbms_is_connected()) return 0;

			return false;
		}

		final public function dbms_convert_to_bind(&$sql_query, &$bind_vars) {

			die("method dbms_convert_to_bind not defined!");
		}

		final public function dbms_now() {

			if(!$this->dbms_is_connected()) return 0;

			$aux= $this->dbms_query("select now()");
			list($ret) = $this->dbms_fetch_row($aux);

			return $ret;
		}

		final public function dbms_escape_string($string) {

			if(!$this->dbms_is_connected()) return 0;

			$ret= addslashes($string);

			return $ret;
		}

		final public function dbms_add_upper_function($field_name) {

			return "UPPER($field_name)";
		}

		final public function show_queryError($sql_query, $sql_error) {
			if(!$this->prevent_errors) return;
			if(DEBUG) html_showInfo("<table border=1><th colspan=2><font size='+1'>Error in query:</font></th><tr><td><b>Query</b></td><td><font color='gray'>$sql_query</font></td></tr><tr><td><b>Error</b></td><td><font color='red'>$sql_error</font></td></tr></table>");
		}

		final public function dbms_query_append($query, $sentence, $conector="and") {

			if(trim($query) == "") return $sentence;
			if(trim($sentence) == "") return $query;
			return "$query $conector $sentence";
		}

		final public function dbms_parse_search_query($field_name, $field_value) {

			$query_str="";
			$parts=explode(" OR ", $field_value);
			$or_sep="";

			foreach($parts as $val) {

				$subparts=explode(" AND ", $val);
				$and_sep="";
				$sub_query_str="";

				foreach($subparts as $subval) {
					$sub_query_str.= $and_sep . $field_name . " like '" . $subval . "'";
					$and_sep=" AND ";
				}

				$query_str.= $or_sep . "(" . $sub_query_str . ")";
				$or_sep=" OR ";
			}

			return $query_str;
		}

		final public function dbms_create_database($database_name) {

			if(!$this->dbms_is_connected()) return 0;

			$query= "CREATE DATABASE $database_name";
			$ret= $this->dbms_query($query);

			return $ret ? true : false;
		}

		final public function dbms_drop_database($database_name) {

			if(!$this->dbms_is_connected()) return 0;

			$query= "DROP DATABASE $database_name";
			$ret= $this->dbms_query($query);

			return $ret ? true : false;
		}

		final public function dbms_grant_database_access($database_name, $table_name, $user, $passwd, $priv) {

			if(!$this->dbms_is_connected()) return 0;

			if( $priv== "NONE") {
				$query= "REVOKE ALL PRIVILEGES on " . $database_name . "." . $table_name . " from $user";
				$ret= $this->dbms_query($query);
			} else {

				switch($priv) {
					case "RO":
						$access= "SELECT";
						break;
					case "RW":
						$access= "SELECT, INSERT, UPDATE, DELETE";
						break;
					case "ALL":
						$access= "ALL PRIVILEGES";
						break;
					default:
						html_showError("Unknown privilege type: $priv");
						return 0;
				}

				$last_val= $this->prevent_errors;
				$this->prevent_errors=false;
				@$this->dbms_query("REVOKE ALL PRIVILEGES on " . $database_name . "." . $table_name . " from $user");
				$this->prevent_errors= $last_val;

				$query="GRANT $access  ON " . $database_name . "." . $table_name . " TO $user IDENTIFIED BY '$passwd'";
				$ret= $this->dbms_query($query);
			}

			return $ret ? true : false;
		}

		final public function dbms_is_valid_object_name($name) {

			for($i=0; $i < strlen($name); $i++) {
				$ord= ord($name[$i]);

				if( ($ord >= 65 and $ord <= 90) or
					($ord >= 97 and $ord <= 122) or
					($ord >= 48 and $ord <= 57) or
					($name[$i]=="_")) {
				} else {
					return false;
				}
			}

			return true;
		}

		final public function dbms_get_valid_object_name($name) {

			$ret="";

			for($i=0; $i < strlen($name); $i++) {
				$ord= ord($name[$i]);

				if(($ord >= 65 and $ord <= 90) or ($ord >= 97 and $ord <= 122) or ($name[$i]=="_")) {
				} else {
					$ret.=$name[$i];
				}
			}

			return $ret;
		}

		final public function dbms_left($string, $lenght) {
			return "LEFT($string,$lenght)";
		}

		final public static function dbms_get_current_timestamp_query(&$date) {
			return "CURRENT_TIMESTAMP";
		}

		final public static function dbms_date_add_query($date, $days) {
			$interval= $days > 0 ? " + $days" : " - $days";
			return "DATE_ADD($date, INTERVAL $interval DAY)";
		}
	}
