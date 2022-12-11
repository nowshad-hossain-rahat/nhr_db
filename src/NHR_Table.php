<?php

namespace NhrDev\NHR_DB\Src;

use PDO;
use Exception;
use NhrDev\NHR_DB\NHR_DB;

class NHR_Table
{

  private NHR_DB $nhr_db;
  private string $db_name, $db_user, $db_password;
  private PDO $conn;
  private array $columns = array();
  private ?array $fetched_columns = null;
  private array $col_names = array();
  private string $name;
  private array $foreign_keys = array();
  private bool $is_debug_mode_on = false;

  function __construct(NHR_DB $nhr_db, string $db_name, string $db_user, string $db_password, string $name, PDO $conn, bool $debug)
  {
    $this->nhr_db = $nhr_db;
    $this->db_name = $db_name;
    $this->db_user = $db_user;
    $this->db_password = $db_password;
    $this->name = $name;
    $this->conn = $conn;
    $this->is_debug_mode_on = $debug;
  }

  /**
   * Returns the table name;
   * @return string
   */
  function get_name()
  {
    return $this->name;
  }


  /**
   * Returns the column names
   * @return array
   */

  function get_columns()
  {
    $result = $this->conn->prepare("DESC $this->name");
    $result->execute();
    return $result->fetchAll(PDO::FETCH_OBJ);
  }

  /**
   * Checks if table exists
   * @return bool
   */
  function exists()
  {
    $result = $this->conn->prepare("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:dbname AND TABLE_NAME=:name");
    $result->execute(['dbname' => $this->db_name, 'name' => $this->name]);
    return $result->fetch(PDO::FETCH_OBJ) ? true : false;
  }

  /**
   * Checks if a column exists in this table or not
   * @param string $column_name
   * @return bool
   */
  function exists_column(string $column_name)
  {
    if ($this->fetched_columns === null) {
      $result = $this->conn->prepare("DESC $this->name");
      $result->execute();
      $this->fetched_columns = $result->fetchAll(PDO::FETCH_OBJ);
      foreach ($this->fetched_columns as $col) {
        if ($col->Field === $column_name)
          return true;
      }
    } else {
      foreach ($this->fetched_columns as $col) {
        if ($col->Field === $column_name)
          return true;
      }
    }
    return false;
  }

  /**
   * Add a column to database table
   * @param string $name
   * @param string $type_and_length
   * @param bool $is_primary
   * @param mixed $is_auto_increment
   * @param bool $is_not_null
   * @param bool $is_unique
   * @param string $default
   * @param string $on_update
   * @return NHR_Table
   */
  function col(string $name, string $type_and_length, bool $is_primary = false, bool $is_auto_increment = false, bool $is_not_null = false, bool $is_unique = false, string $default = '', string $on_update = '')
  {

    $is_primary = ($is_primary) ? "PRIMARY KEY" : "";
    $is_auto_increment = ($is_auto_increment) ? "AUTO_INCREMENT" : "";
    $is_not_null = ($is_not_null) ? "NOT NULL" : "";
    $is_unique = (!$is_primary && $is_unique) ? "UNIQUE" : "";
    $default = (!empty($default)) ? "DEFAULT " . strtoupper($default) : "";
    $on_update = (!empty($on_update)) ? "ON UPDATE " . strtoupper($on_update) : "";

    $q = "$name $type_and_length $is_primary $is_auto_increment $is_not_null $is_unique $default $on_update";

    $this->col_names[] = $name;
    $this->columns[$name] = $q;

    return $this;
  }


  /**
   * Add an auto incrementable primary key `id` column
   * @return NHR_Table
   */
  function id()
  {
    return $this->col('id', NHR_DB::int(), true, true, true, true);
  }

  /**
   * Add two columns `created_at` and `updated_at`
   * @return NHR_Table
   */
  function timestamp()
  {
    return $this->col('created_at', NHR_DB::timestamp(), false, false, true, false, 'current_timestamp', 'current_timestamp')
      ->col('updated_at', NHR_DB::timestamp(), false, false, true, false, 'current_timestamp', 'current_timestamp');
  }

  /**
   * Add a varchar column
   * @param string $column_name
   * @param int $length
   * @return NHR_Table
   */
  function str(string $column_name, int $length = 255)
  {
    return $this->col($column_name, NHR_DB::str($length));
  }

  /**
   * Add a text column
   * @param string $column_name
   * @return NHR_Table
   */
  function text(string $column_name)
  {
    return $this->col($column_name, NHR_DB::text());
  }

  /**
   * Add a date column
   * @param string $column_name
   * @return NHR_Table
   */
  function date(string $column_name)
  {
    return $this->col($column_name, NHR_DB::date(), false, false, true);
  }

  /**
   * Add a datetime oclumn
   * @param string $column_name
   * @return NHR_Table
   */
  function datetime(string $column_name)
  {
    return $this->col($column_name, NHR_DB::datetime(), false, false, true);
  }

  /**
   * Add an enum column with the given values
   * @param string $column_name
   * @param array $values [0, 1, 2, 3, 4, ...] | [ 'true', 'false' ]
   * @return NHR_Table
   */
  function enum (string $column_name, array $values = [0, 1])
  {
    return $this->col($column_name, NHR_DB::enum ($values), false, false, true);
  }

  /**
   * Add an unsigned int colulmn
   * @param string $column_name
   * @param int $length
   * @return NHR_Table
   */
  function unsigned_int(string $column_name, int $length = 255)
  {
    return $this->col($column_name, NHR_DB::unsigned_int($length));
  }

  /**
   * Add an unsigned bigint column
   * @param string $column_name
   * @param int $length
   * @return NHR_Table
   */
  function unsigned_bigint(string $column_name, int $length = 255)
  {
    return $this->col($column_name, NHR_DB::unsigned_bigint($length));
  }

  /**
   * Add a float column
   * @param string $column_name
   * @return NHR_Table
   */
  function float(string $column_name)
  {
    return $this->col($column_name, NHR_DB::float());
  }

  /**
   * Add a bigint column
   * @param string $column_name
   * @param int $length
   * @return NHR_Table
   */
  function bigint(string $column_name, int $length = 255)
  {
    return $this->col($column_name, NHR_DB::bigint($length));
  }

  /**
   * Add foreign keys
   * @param array $foreign_keys
   * @return NHR_Table
   */
  function foreign_keys(...$foreign_keys)
  {
    foreach ($foreign_keys as $fk) {
      if ($fk->__get_query()) {
        $this->foreign_keys[] = $fk->__get_query();
      }
    }
    return $this;
  }

  /**
   * Add a new column to the table after the table is created
   * @param string $name
   * @param string $type_and_length
   * @param bool $is_primary
   * @param mixed $is_auto_increment
   * @param bool $is_not_null
   * @param bool $is_unique
   * @return NHR_Table
   */
  function add(string $name, string $type_and_length, bool $is_primary = false, $is_auto_increment = false, bool $is_not_null = false, bool $is_unique = false)
  {

    $is_primary = ($is_primary) ? "PRIMARY KEY" : "";
    $is_auto_increment = ($is_auto_increment) ? "AUTO_INCREMENT" : "";
    $is_not_null = ($is_not_null) ? "NOT NULL" : "";
    $is_unique = (!$is_primary && $is_unique) ? "UNIQUE" : "";

    try {
      $q = "$name $type_and_length $is_primary $is_auto_increment $is_not_null $is_unique";
      $this->conn->exec("ALTER TABLE $this->name ADD $q");

      $this->col_names[] = $name;
      $this->columns[$name] = $q;
    } catch (Exception $e) {
      if ($this->is_debug_mode_on) {
        echo $e;
      }
    }

    return $this;
  }


  /**
   * Drop selected column of the table
   * @param string $name
   * @return bool
   */
  function drop(string $name)
  {
    try {
      $dropped = $this->conn->exec("ALTER TABLE " . $this->name . " DROP $name") === 0;
      unset($this->col_names[array_search($name, $this->col_names)]);
      unset($this->columns[$name]);
      return $dropped;
    } catch (Exception $e) {
      if ($this->is_debug_mode_on) {
        echo $e;
      }
      return false;
    }
  }


  /**
   * Drops the complete table
   * @return bool
   */
  function drop_all()
  {
    try {
      $this->columns = array();
      $this->col_names = array();
      return $this->conn->exec("DROP TABLE $this->name") === 0;
    } catch (Exception $e) {
      if ($this->is_debug_mode_on) {
        echo $e;
      }
      return false;
    }
  }



  /**
   * Insert rows into the database table
   * @param array $cols_and_values [ 'column_name' => 'value', ... ]
   * @return int
   */
  function insert(array $cols_and_values)
  {
    if (count($cols_and_values) > 0) {
      $cols = "";
      $keys = "";
      $params = array();

      foreach ($cols_and_values as $k => $v) {
        $end = ($v == end($cols_and_values)) ? "" : ",";
        $cols = $cols . $k . $end;
        $keys = $keys . ":$k" . $end;
        $params[":$k"] = $v;
      }

      $q = "INSERT INTO " . $this->name . " ($cols) VALUES ($keys)";

      try {
        $result = $this->conn->prepare($q);
        $result->execute($params);
        return $result->rowCount();
      } catch (Exception $e) {
        if ($this->is_debug_mode_on) {
          echo $e;
        }
        return 0;
      }

    } else {
      return 0;
    }
  }


  /**
   * Performs SQL delete operation
   * @param array $where
   * @return int
   */
  function delete(array $where)
  {
    if (count($where) > 0) {
      $keys = "WHERE ";
      $params = array();
      foreach ($where as $k => $v) {
        $end = ($v == end($where)) ? "" : " && ";
        $keys = $keys . "$k=:$k" . $end;
        $params[":$k"] = $v;
      }

      try {
        $q = "DELETE FROM $this->name $keys";
        $result = $this->conn->prepare($q);
        $result->execute($params);
        return $result->rowCount();
      } catch (Exception $e) {
        if ($this->is_debug_mode_on) {
          echo $e;
        }
        return 0;
      }

    } else {
      return 0;
    }
  }


  /**
   * Updates the database table
   * @param array $cols_and_values
   * @param array $where
   * @return int
   */
  function update(array $cols_and_values, array $where)
  {
    $conds = "WHERE ";
    $cols = "";
    $params = array();

    foreach ($where as $k => $v) {
      $end = ($v == end($where)) ? "" : " && ";
      $conds = $conds . $k . "=" . ":$k" . "_CONDITION$end";
      $params[":$k" . "_CONDITION"] = $v;
    }

    foreach ($cols_and_values as $col => $val) {
      $end = ($val == end($cols_and_values)) ? "" : ",";
      $cols = $cols . "$col=:$col $end";
      $params[":$col"] = $val;
    }

    try {
      $q = "UPDATE $this->name SET $cols $conds";
      $result = $this->conn->prepare($q);
      $result->execute($params);
      return $result->rowCount();
    } catch (Exception $e) {
      if ($this->is_debug_mode_on) {
        echo $e;
      }
      return -1;
    }

  }


  /**
   * Fetches rows from the database table
   * @param string|array $columns Default is `'*'` - this means (all) | Or you can specify columns in an array
   * @param array $conditions
   * @param NHR_Result|bool $return_type
   * @return bool|object
   */
  function fetch($columns = '*', array $conditions = [], $return_type = NHR_DB::ASSOC)
  {

    $conds = "";
    $params = array();
    $order_by = (!empty($conditions["ORDER_BY"])) ? "ORDER BY " . $conditions['ORDER_BY'] : "";
    $limit = (!empty($conditions["LIMIT"])) ? "LIMIT " . $conditions['LIMIT'] : "";
    $cols = '*';



    # parsing the filtered columns
    if (gettype($columns) == 'string') {
      $cols = '*';
    } else if (gettype($columns) == 'array') {
      $cols = '';
      foreach ($columns as $column) {
        $comma = (end($columns) == $column) ? '' : ',';
        $cols = $cols . $column . $comma;
      }
    }


    # parsing the query conditions
    foreach ($conditions as $k => $v) {
      unset($conditions["ORDER_BY"]);
      unset($conditions["LIMIT"]);
      $end = ($v == end($conditions)) ? "" : " AND ";
      if ($k === 'or') {
        $conds .= $v . $end;
      } else if ($k === 'and') {
        $conds .= $v . $end;
      } else if ($k != 'LIMIT' && $k != 'ORDER_BY') {
        if (preg_match("/[0-9]+/", $k)) {
          if (preg_match("/(OFFSET|ORDER BY)/", $v) && preg_match("/(ORDER BY)/", $conds)) {
            continue;
          } else if (preg_match("/(LIMIT)/", $v) && preg_match("/(OFFSET)/", $conds)) {
            continue;
          }
          $conds = $conds . "$v" . "$end";
        } else {
          $conds = $conds . "$k=:$k" . "$end";
          $params[":$k"] = $v;
        }
      }
    }

    # checking if `WHERE` need to be added
    if (!empty($conds) && preg_match("/[=<>]|(LIKE)/", $conds)) {
      $conds = "WHERE $conds";
    }

    # removing misplaced 'AND' and 'OR'
    $conds = str_replace(
      "AND ORDER",
      "ORDER",
      str_replace(
        "OR ORDER",
        "ORDER",
        $conds
      )
    );

    try {

      # creating the sql query string
      $q = trim("SELECT $cols FROM $this->name $conds");

      # validating the query string
      if (!preg_match("/(OFFSET)/", $q)) {
        $q .= $order_by . ' ' . $limit;
      }

      # removing the useless trailing `AND` and `OR`
      if (substr($q, -3) === 'AND') {
        substr_replace($q, '', -3);
      }
      if (substr($q, -2) === 'OR') {
        substr_replace($q, '', -2);
      }

      # preparing the sql statement
      $result = $this->conn->prepare($q);

      # executing the sql statement
      count($params) > 0 ? $result->execute($params) : $result->execute();

      return new NHR_Result($result->fetchAll($return_type));

    } catch (Exception $e) {
      if ($this->is_debug_mode_on) {
        echo $e;
      }
      return false;
    }

  }




  /**
   * Fetch data from the table using custom sql query
   * @param string $query Place `#{this_table}` into the query string to use the current table name
   * @param array $params
   * @param NHR_Result|bool $return_type
   * @return bool|object
   */
  function fetch_custom(string $query, array $params, $return_type = NHR_DB::ASSOC)
  {

    try {

      $query = str_replace('#{this_table}', $this->name, $query);
      $result = $this->conn->prepare($query);
      count($params) > 0 ? $result->execute($params) : $result->execute();

      return new NHR_Result($result->fetchAll($return_type));

    } catch (Exception $e) {
      if ($this->is_debug_mode_on) {
        echo $e;
      }
      return false;
    }

  }




  /**
   * Counts the total number of rows in the table
   * @return int
   */
  function num_rows()
  {

    try {

      $q = "SELECT COUNT(*) FROM " . $this->name;
      $result = $this->conn->prepare($q);

      if ($result->execute()) {

        $all = $result->fetchAll(PDO::FETCH_ASSOC);

        if (count($all) > 0) {
          if ($all[0]) {
            return (int) $all[0]['COUNT(*)'];
          } else {
            return -1;
          }
        } else {
          return -1;
        }

      } else {
        return -1;
      }

    } catch (Exception $e) {
      if ($this->is_debug_mode_on) {
        echo $e;
      }
      return -1;
    }

  }





  /**
   * Creates a table with the columns added by calling the `NHR_Table->col(..)` function
   * @return bool
   */
  function create()
  {

    $q = null;

    foreach ($this->columns as $i => $v) {
      $end = ($v == end($this->columns)) ? "" : ",";
      $q = $q . $v . $end;
    }

    if (count($this->foreign_keys) > 0) {
      $q .= ', ';
      foreach ($this->foreign_keys as $i => $v) {
        $end = ($v == end($this->foreign_keys)) ? "" : ",";
        $q = $q . $v . $end;
      }
    }

    $query = "CREATE TABLE IF NOT EXISTS " . $this->name . " ($q)";

    try {
      if ($this->conn) {
        return $this->conn->exec($query) === false;
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

}

?>
