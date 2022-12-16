<?php

namespace NhrDev\NHR_DB\Src\QueryBuilders;

use Exception;
use NhrDev\NHR_DB\Src\Table;
use PDO;

class SQLQueryBuilder
{

  protected PDO $conn;
  protected Table $table;
  protected bool $for_pdo = true;
  protected $sql_query = "";
  protected string $conditions_sql_str = "";
  protected array $parameters_for_pdo = [];
  protected string $postfix = "__NHR_SQL_CONDITION__";
  protected int $limit_length = -1;
  protected string $order_by_column = "", $order = "";
  protected int $offset_rows_count = -1;
  protected int $fetch_rows_count = -1;
  protected bool $is_debug_mode_on = false;


  /**
   * SQLQueryBuilder initializer
   * @param bool $for_pdo - Defines whether the sql query will be generated for a PDO based query or not
   */
  function __construct(PDO $db_connection, Table $table, bool $for_pdo = true, bool $is_debug_mode_on = false)
  {
    $this->conn = $db_connection;
    $this->table = $table;
    $this->for_pdo = $for_pdo;
    $this->is_debug_mode_on = $is_debug_mode_on;
  }

  /**
   * This referes to the SQL `AND` operator
   * @param string $column_name
   * @param string $operator
   * @param mixed $value
   * @return SQLQueryBuilder
   */
  public function where(string $column_name, string $operator, $value = null)
  {

    if (!empty($this->conditions_sql_str)) {
      $this->conditions_sql_str .= " AND ";
    }

    if ($column_name && $operator && $value !== null) {

      if (gettype($value) === 'string') {
        $value = "`" . $value . "`";
      }

      if ($this->for_pdo) {
        $this->conditions_sql_str .= trim($column_name) . ":" . trim($column_name) . $this->postfix;
        $this->parameters_for_pdo[":" . trim($column_name) . $this->postfix] = " " . trim($operator) . " " . $value;
      } else {
        if (gettype($value) === 'string') {
          $value = "`" . $value . "`";
        }
        $this->conditions_sql_str .= trim($column_name) . " " . trim($operator) . " " . $value;
      }

    } else if ($column_name && $operator && $value === null) {

      if ($this->for_pdo) {
        $this->conditions_sql_str .= trim($column_name) . ":" . trim($column_name) . $this->postfix;
        $this->parameters_for_pdo[":" . trim($column_name) . $this->postfix] = $operator;
      } else {
        $this->conditions_sql_str .= trim($column_name) . $operator;
      }

    }

    return $this;
  }


  /**
   * This referes to the SQL `OR` operator
   * @param string $column_name
   * @param string $operator
   * @param mixed $value
   * @return SQLQueryBuilder
   */
  public function or(string $column_name, string $operator, $value = null)
  {

    if (!empty($this->conditions_sql_str)) {
      $this->conditions_sql_str .= " OR ";
    }

    if ($column_name && $operator && $value !== null) {

      if (gettype($value) === 'string') {
        $value = "`" . $value . "`";
      }

      if ($this->for_pdo) {
        $this->conditions_sql_str .= trim($column_name) . ":" . trim($column_name) . $this->postfix;
        $this->parameters_for_pdo[":" . trim($column_name) . $this->postfix] = " " . trim($operator) . " " . $value;
      } else {
        $this->conditions_sql_str .= trim($column_name) . " " . trim($operator) . " " . $value;
      }

    } else if ($column_name && $operator && $value === null) {

      if ($this->for_pdo) {
        $this->conditions_sql_str .= trim($column_name) . ":" . trim($column_name) . $this->postfix;
        $this->parameters_for_pdo[":" . trim($column_name) . $this->postfix] = $operator;
      } else {
        $this->conditions_sql_str .= trim($column_name) . $operator;
      }

    }

    return $this;
  }

  protected function parse_special_operations()
  {
    if ($this->limit_length > 0) {
      $this->conditions_sql_str .= " LIMIT $this->limit_length";
    }

    if ($this->order_by_column) {
      $this->conditions_sql_str .= " ORDER BY $this->order_by_column $this->order";
    }

    if ($this->order_by_column && $this->order && $this->offset_rows_count > 0) {
      $this->conditions_sql_str .= " OFFSET $this->offset_rows_count ROWS";
    }

    if ($this->order_by_column && $this->order && $this->offset_rows_count > 0 && $this->fetch_rows_count) {
      $this->conditions_sql_str .= " FETCH NEXT $this->fetch_rows_count ROWS ONLY";
    }

    $this->conditions_sql_str = "WHERE " . trim($this->conditions_sql_str);
  }


  /**
   * Returns the parsed SQL WHERE conditions string
   * @return string
   */
  protected function get_conditions()
  {
    $this->parse_special_operations();
    return $this->conditions_sql_str;
  }


  /**
   * Returns the parameter list generated for PDO based query
   * @return array
   */
  protected function get_params()
  {
    return $this->parameters_for_pdo;
  }

}

?>
