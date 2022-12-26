<?php

namespace NhrDev\NHR_DB\Src\QueryBuilders;

use Exception;
use NhrDev\NHR_DB\Src\Table;
use PDO;

class SQLQueryBuilder
{

  protected PDO $conn;
  protected Table $table;

  protected bool $is_debug_mode_on = false;

  protected string $sql_query = "";
  protected string $conditions_sql_str = "";
  protected string $condition_postfix = "_nhr_sql_condition";
  protected string $order_by_column = "", $order = "";

  protected array $pdo_condition_parameters = [];

  protected int $limit_length = -1;
  protected int $offset_rows_count = -1;
  protected int $fetch_rows_count = -1;


  /**
   * Summary of __construct
   * @param PDO $db_connection
   * @param Table $table
   * @param bool $is_debug_mode_on
   */
  function __construct(PDO $db_connection, Table $table, bool $is_debug_mode_on = false)
  {
    $this->conn = $db_connection;
    $this->table = $table;
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

      $this->conditions_sql_str .= trim($column_name) . ":" . trim($column_name) . $this->condition_postfix;
      $this->pdo_condition_parameters[":" . trim($column_name) . $this->condition_postfix] = " " . trim($operator) . " $value";

    } else if ($column_name && $operator && $value === null) {

      $this->conditions_sql_str .= trim($column_name) . ":" . trim($column_name) . $this->condition_postfix;
      $this->pdo_condition_parameters[":" . trim($column_name) . $this->condition_postfix] = $operator;

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
  public function or (string $column_name, string $operator, $value = null)
  {

    if (!empty($this->conditions_sql_str)) {
      $this->conditions_sql_str .= " OR ";
    }

    if ($column_name && $operator && $value !== null) {

      $this->conditions_sql_str .= trim($column_name) . ":" . trim($column_name) . $this->condition_postfix;
      $this->pdo_condition_parameters[":" . trim($column_name) . $this->condition_postfix] = " " . trim($operator) . " $value";

    } else if ($column_name && $operator && $value === null) {

      $this->conditions_sql_str .= trim($column_name) . ":" . trim($column_name) . $this->condition_postfix;
      $this->pdo_condition_parameters[":" . trim($column_name) . $this->condition_postfix] = $operator;

    }

    return $this;
  }

  protected function parse_special_operations()
  {
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
  protected function get_condition_params()
  {
    return $this->pdo_condition_parameters;
  }

}

?>
