<?php

namespace NhrDev\NHR_DB;

use PDO;
use Exception;
use NhrDev\NHR_DB\Src\Foreign_Key;
use NhrDev\NHR_DB\Src\Table;

/**
 * PDO based database helper class, developed to help developers.
 */
class DB
{

  private ? PDO $conn = null;
  private string $driver = "mysql";
  private string $host, $charset, $db, $user, $pass, $port;
  private bool $is_debug_mode_on = false;

  public const OBJ = PDO::FETCH_OBJ;
  public const ASSOC = PDO::FETCH_ASSOC;
  public const IND = PDO::FETCH_NUM;

  /**
   * DB constructor
   * @param string $db_user
   * @param string $db_password
   * @param string $db_name
   * @param string $host_name
   * @param int $port
   * @param string $charset
   * @throws Exception
   */
  function __construct(string $db_user, string $db_password, string $db_name, string $host_name = 'localhost', int $port = -1, string $charset = '')
  {

    $this->host = $host_name ? $host_name : "localhost";
    $this->user = $db_user;
    $this->pass = $db_password;
    $this->db = $db_name;
    $this->port = $port !== -1 ? $port : "";
    $this->charset = $charset ? $charset : "";

    $this->connect();

  }

  /**
   * To change debug mode
   * @param bool $debug
   */
  function set_debug(bool $debug)
  {
    $this->is_debug_mode_on = $debug;
    return $this;
  }

  /**
   * To disconnect from database server
   * @return bool
   */
  function disconnect()
  {
    $this->conn = null;
    return true;
  }



  /**
   * To connect to the database if not connected
   * @return bool
   */
  function connect()
  {
    try {
      if ($this->conn == null) {

        $port = (empty($this->port)) ? "" : "port=$this->port;";
        $charset = (empty($this->charset)) ? "" : "charset=$this->charset;";

        $this->conn = @new PDO("$this->driver:host=$this->host;dbname=$this->db;$port$charset", $this->user, $this->pass) or die("Error in Connection Building!\nCheck the information you've given on 'DB' setup!");

        return true;
      } else {
        return false;
      }
    } catch (Exception $e) {
      if ($this->is_debug_mode_on) {
        echo $e->getMessage();
      }
      return false;
    }
  }




  /**
   * Returns ture or false based on connectivity
   * @return bool
   */
  function is_connected()
  {
    return ($this->conn !== null) ? true : false;
  }


  /**
   * INT data type for SQL column
   * @param int $l
   * @return string
   */
  public static function int(int $l = 255)
  {
    return "INT($l)";
  }

  /**
   * FLOAT data type for SQL column
   * @return string
   */
  public static function float()
  {
    return "FLOAT";
  }

  /**
   * BIGINT data type for SQL column
   * @param int $l
   * @return string
   */
  public static function bigint(int $l = 255)
  {
    return "BIGINT($l)";
  }

  /**
   * INT UNSIGNED data type for SQL column
   * @param int $l
   * @return string
   */
  public static function unsigned_int(int $l = 255)
  {
    return "INT($l) UNSIGNED";
  }

  /**
   * BIGINT UNSIGNED data type for SQL column
   * @param int $l
   * @return string
   */
  public static function unsigned_bigint(int $l = 255)
  {
    return "BIGINT($l) UNSIGNED";
  }

  /**
   * VARCHAR data type for SQL column
   * @param int $l
   * @return string
   */
  public static function str(int $l = 255)
  {
    return "VARCHAR($l)";
  }

  /**
   * TEXT data type for SQL column
   * @return string
   */
  public static function text()
  {
    return "TEXT";
  }

  /**
   * DATE data type for SQL column
   * @return string
   */
  public static function date()
  {
    return "DATE";
  }

  /**
   * DATETIME data type for SQL column
   * @return string
   */
  public static function datetime()
  {
    return "DATETIME";
  }

  /**
   * TIMESTAMP data type for SQL column
   * @param int $l
   * @return string
   */
  public static function timestamp()
  {
    return "TIMESTAMP";
  }

  /**
   * ENUM data type for SQL column
   * @param array $values - [0, 1, 2, 3, 4, ...] | [ 'true', 'false' ]
   * @return string
   */
  public static function enum (array $values = [0, 1])
  {
    $enum_values = join(",", array_map(function ($value) {
      return "'" . htmlspecialchars($value) . "'";
    }, $values));
    return "ENUM($enum_values)";
  }


  /**
   * Less than (<) SQL condition
   * @param float $value
   * @return string
   */
  public static function lt(float $value)
  {
    return " < $value";
  }

  /**
   * Greater than (>) SQL condition
   * @param float $value
   * @return string
   */
  public static function gt(float $value)
  {
    return " > $value";
  }

  /**
   * Less than equal to (<=) SQL condition
   * @param float $value
   * @return string
   */
  public static function lteq(float $value)
  {
    return " <= $value";
  }

  /**
   * Greater than equal to (>=) SQL condition
   * @param float $value
   * @return string
   */
  public static function gteq(float $value)
  {
    return " >= $value";
  }

  /**
   * Not equal to (<>) SQL condition
   * @param string $value
   * @return string
   */
  public static function noteq(string $value)
  {
    return " <> " . htmlspecialchars( trim($value) );
  }

  /**
   * Equal to (=) SQL condition
   * @param string $value
   * @return string
   */
  public static function eq(string $value)
  {
    return " = '" . htmlspecialchars(trim( $value )) . "'";
  }

  /**
   * BETWEEN first_value AND second_value SQL condition
   * @param float $first_value
   * @param float $second_value
   * @return string
   */
  public static function between(float $first_value, float $second_value)
  {
    return " BETWEEN $first_value AND $second_value";
  }

  /**
   * Like (LIKE %t%) SQL condition
   * @param string $value
   * @return string
   */
  public static function like(string $value)
  {
    return " LIKE '%" . htmlspecialchars(trim($value)) . "%'";
  }

  /**
   * Begins Like (LIKE t%) SQL condition
   * @param string $value
   * @return string
   */
  public static function begins_like(string $value)
  {
    return " LIKE '" . htmlspecialchars( trim($value) ) . "%'";
  }

  /**
   * Ends Like (LIKE %t) SQL condition
   * @param string $value
   * @return string
   */
  public static function ends_like(string $value)
  {
    return " LIKE '%" . htmlspecialchars( trim($value) ) . "'";
  }

  /**
   * SQL offset query part builder
   * @param string $order_by
   * @param int $rows_to_skip
   * @param int $rows_to_fetch
   * @return string
   */
  public static function offset(string $order_by, int $rows_to_skip, int $rows_to_fetch = -1)
  {
    $query_str_part = "ORDER BY " . trim( $order_by ) . " OFFSET $rows_to_skip ROWS";
    if ($rows_to_fetch > -1) {
      $query_str_part .= " FETCH NEXT $rows_to_fetch ROWS ONLY";
    }
    return $query_str_part;
  }

  /**
   * Alternative of PDO::quote(string)
   * @param string $text
   * @return bool|string
   */
  public function quote(string $text)
  {
    return $this->conn->quote($text);
  }


  /**
   * Returns ID of the last inserted row
   * @return bool|string
   */
  public function last_insert_id()
  {
    return $this->conn->lastInsertId() === -1 ? false : $this->conn->lastInsertId();
  }

  /**
   * Foreign key query part builder
   * @param string $column_name
   * @return Foreign_Key
   */
  public static function foreign(string $column_name)
  {
    return new Foreign_Key($column_name);
  }

  # to select or create a new table
  function table(string $table_name)
  {
    return new Table($this, $this->db, $this->user, $this->pass, $table_name, $this->conn, $this->is_debug_mode_on);
  }



}

?>
