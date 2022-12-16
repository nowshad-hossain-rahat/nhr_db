<?php

namespace NhrDev\NHR_DB\Src;

use PDO;

class SQLQueryBuilder
{

  private PDO $conn;
  private Table $table;
  private bool $for_pdo;
  private $sql_query = "";
  private string $conditions_sql_str = "";
  private array $parameters_for_pdo = [];
  private string $postfix = "__NHR_SQL_CONDITION__";
  private int $limit_length = -1;
  private string $order_by_column, $order;
  private int $offset_rows_count = -1;
  private int $fetch_rows_count = -1;


  /**
   * SQLQueryBuilder initializer
   * @param bool $for_pdo - Defines whether the sql query will be generated for a PDO based query or not
   */
  function __construct(PDO $db_connection, Table $table, bool $for_pdo = true)
  {
    $this->conn = $db_connection;
    $this->table = $table;
    $this->for_pdo = $for_pdo;
  }

  public function update(array $columns_and_values)
  {
    $this->parameters_for_pdo = [];

    $cols = join(",", array_map(function ($key) {
      return "$key=:$key";
    }, array_keys($columns_and_values)));

    foreach ($columns_and_values as $col => $val) {
      $this->parameters_for_pdo[":$col"] = $val;
    }

    $this->sql_query = "UPDATE " . $this->table->get_name() . " SET $cols";

    return $this;
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


  /**
   * This referes to the SQL `LIMIT` operator
   * @param int $length
   * @return SQLQueryBuilder
   */
  public function limit(int $length)
  {
    $this->limit_length = $length;
    return $this;
  }


  /**
   * This refers to the SQL `ORDER BY` operator
   * @param string $column_name
   * @param string $order Possible values `ASC` | `DESC`
   * @return SQLQueryBuilder
   */
  public function order_by(string $column_name, string $order = 'ASC')
  {
    $this->order_by_column = $column_name;
    $this->order = $order === 'ASC' ? $order : 'DESC';
    return $this;
  }

  /**
   * This refers to the SQL `OFFSET [COUNT] ROWS`
   * @param int $number_of_rows_to_skip
   * @return SQLQueryBuilder
   */
  public function offset(int $number_of_rows_to_skip)
  {
    $this->offset_rows_count = $number_of_rows_to_skip;
    return $this;
  }


  public function fetch(int $number_of_rows_to_fetch)
  {
    $this->fetch_rows_count = $number_of_rows_to_fetch;
    return $this;
  }

  private function parse_special_operations()
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
  private function get_conditions()
  {
    $this->parse_special_operations();
    return $this->conditions_sql_str;
  }


  /**
   * Returns the parameter list generated for PDO based query
   * @return array
   */
  private function get_params()
  {
    return $this->parameters_for_pdo;
  }

  public function execute()
  {
    $this->sql_query = $this->sql_query . " " . trim($this->get_conditions());
    echo $this->sql_query;
    // $result = $this->conn->prepare($this->sql_query);
    // $result->execute($this->parameters_for_pdo);
    // return $result->rowCount();
  }

}

?>
