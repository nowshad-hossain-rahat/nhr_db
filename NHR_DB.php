<?php

namespace NhrDev\NHR_DB;

use PDO;
use Exception;
use NhrDev\NHR_DB\SRC\NHR_Foreign_Key;
use NhrDev\NHR_DB\SRC\NHR_Table;


require_once "./vendor/autoload.php";


/**
 * PDO based database helper class, developed to help developers.
 */
class NHR_DB
{

  private ? PDO $conn = null;
  private array $config;
  private string $driver, $host, $charset, $db, $user, $pass, $port;
  private bool $is_debug_mode_on = false;

  public const OBJ = PDO::FETCH_OBJ;
  public const ASSOC = PDO::FETCH_ASSOC;
  public const IND = PDO::FETCH_NUM;

  /**
   * NHR_DB constructor
   * @param array $config - [
   *  ['driver'] => string (mysql),
   *  ['host'] => string (localhost),
   *  ['port'] => int,
   *  ['charset'] => string,
   *  'user' => string,
   *  'pass' => string,
   *  'dbname' => string
   * ]
   */
  function __construct(array $config)
  {

    $this->config = $config;
    $this->driver = isset($config["driver"]) ? $config["driver"] : "mysql";
    $this->host = isset($config["host"]) ? $config["host"] : "localhost";
    $this->user = $config["user"];
    $this->pass = $config["pass"];
    $this->db = $config["dbname"];
    $this->port = isset($config["port"]) ? $config["port"] : "";
    $this->charset = isset($config["charset"]) ? $config["charset"] : "";

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
        echo $e;
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
    $enum_values = '';
    foreach ($values as $val) {
      $comma = (end($values) == $val) ? '' : ', ';
      $enum_values = $enum_values . "'$val'$comma";
    }
    return "ENUM($enum_values)";
  }


  /**
   * Less than (<) SQL condition
   * @param string $column_name
   * @param float $value
   * @return string
   */
  public static function lt(string $column_name, float $value)
  {
    return trim($column_name) . " < $value";
  }

  /**
   * Greater than (>) SQL condition
   * @param string $column_name
   * @param float $value
   * @return string
   */
  public static function gt(string $column_name, float $value)
  {
    return trim($column_name) . " > $value";
  }

  /**
   * Less than equal to (<=) SQL condition
   * @param string $column_name
   * @param float $value
   * @return string
   */
  public static function lteq(string $column_name, float $value)
  {
    return trim($column_name) . " <= $value";
  }

  /**
   * Greater than equal to (>=) SQL condition
   * @param string $column_name
   * @param float $value
   * @return string
   */
  public static function gteq(string $column_name, float $value)
  {
    return trim($column_name) . " >= $value";
  }

  /**
   * Not equal to (<>) SQL condition
   * @param string $column_name
   * @param string $value
   * @return string
   */
  public static function noteq(string $column_name, string $value)
  {
    return trim($column_name) . " <> " . trim($value);
  }

  /**
   * Equal to (=) SQL condition
   * @param string $column_name
   * @param string $value
   * @return string
   */
  public static function eq(string $column_name, string $value)
  {
    return trim($column_name) . " = '" . trim($value) . "'";
  }

  /**
   * Like (LIKE %t%) SQL condition
   * @param string $column_name
   * @param string $value
   * @return string
   */
  public static function like(string $column_name, string $value)
  {
    return trim($column_name) . " LIKE '%" . trim($value) . "%'";
  }

  /**
   * Begins Like (LIKE t%) SQL condition
   * @param string $column_name
   * @param string $value
   * @return string
   */
  public static function bgins_like(string $column_name, string $value)
  {
    return trim($column_name) . " LIKE '" . trim($value) . "%'";
  }

  /**
   * Ends Like (LIKE %t) SQL condition
   * @param string $column_name
   * @param string $value
   * @return string
   */
  public static function ends_like(string $column_name, string $value)
  {
    return trim($column_name) . " LIKE '%" . trim($value) . "'";
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
    $query_str_part = "ORDER BY $order_by OFFSET $rows_to_skip ROWS";
    if ($rows_to_fetch > -1) {
      $query_str_part .= " FETCH NEXT $rows_to_fetch ROWS ONLY";
    }
    return $query_str_part;
  }

  /**
   * To set SQL `OR` conditions
   * @param array $conditions
   * @return string
   */
  public static function or (...$conditions)
  {
    return join(" OR ", $conditions);
  }

  /**
   * To set SQL `AND` conditions
   * @param array $conditions
   * @return string
   */
  public static function and (...$conditions)
  {
    return join(" AND ", $conditions);
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
   * @return NHR_Foreign_Key
   */
  public static function foreign(string $column_name)
  {
    return new NHR_Foreign_Key($column_name);
  }

  # to select or create a new table
  function table(string $table_name)
  {
    return new NHR_Table($this, $this->config, $table_name, $this->conn, $this->is_debug_mode_on);
  }



}

?>
