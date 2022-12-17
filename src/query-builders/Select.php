<?php

namespace NhrDev\NHR_DB\Src\QueryBuilders;

use Exception;
use NhrDev\NHR_DB\DB;
use NhrDev\NHR_DB\Src\Result;
use NhrDev\NHR_DB\Src\Table;
use PDO;

class Select extends SQLQueryBuilder
{

  private int $fetch_mode;


  /**
   * Select query builder initializer
   * @param array $column_names
   * @param PDO $db_connection
   * @param Table $table
   * @param bool $is_debug_mode_on
   * @param int $fetch_mode DB::ASSOC|DB::OBJ|DB::IND
   */
  function __construct(array $column_names, PDO $db_connection, Table $table, bool $is_debug_mode_on = false, int $fetch_mode = DB::ASSOC)
  {
    parent::__construct($db_connection, $table, $is_debug_mode_on);
    $this->fetch_mode = $fetch_mode;
    $this->generate_select_query($column_names);
  }

  /**
   * Refers to SQL `SELECT` operation
   * @param array $columns_and_values [ 'username', 'email', ... ]
   */
  private function generate_select_query(array $column_names)
  {
    $selected_columns = (count($column_names) > 0) ? join(",", $column_names) : '*';
    $this->sql_query = "SELECT $selected_columns FROM " . $this->table->get_name();
  }

  /**
   * This referes to the SQL `AND` operator
   * @param string $column_name
   * @param string $operator
   * @param mixed $value
   * @return Select
   */
  public function where(string $column_name, string $operator, $value = null)
  {
    parent::where($column_name, $operator, $value);
    return $this;
  }


  /**
   * This referes to the SQL `OR` operator
   * @param string $column_name
   * @param string $operator
   * @param mixed $value
   * @return Select
   */
  public function or (string $column_name, string $operator, $value = null)
  {
    parent::or ($column_name, $operator, $value);
    return $this;
  }

  /**
   * This referes to the SQL `LIMIT` operator
   * @param int $length
   * @return Select
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
   * @return Select
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
   * @return Select
   */
  public function offset(int $number_of_rows_to_skip)
  {
    $this->offset_rows_count = $number_of_rows_to_skip;
    return $this;
  }

  protected function parse_special_operations()
  {

    if (!empty($this->conditions_sql_str)) {
      $this->conditions_sql_str = "WHERE " . trim($this->conditions_sql_str);
    }

    if ($this->order_by_column) {
      $this->conditions_sql_str .= " ORDER BY $this->order_by_column $this->order";
    }

    if ($this->limit_length > 0) {
      $this->conditions_sql_str .= " LIMIT $this->limit_length";
    }

    if ($this->order_by_column && $this->order && $this->offset_rows_count > 0) {
      $this->conditions_sql_str .= " OFFSET $this->offset_rows_count";
    }

  }


  /**
   * Executes the SQL query
   * @return Result|bool
   */
  public function execute()
  {
    try {

      $this->parse_special_operations();

      // placing the conditional values into the sql query
      foreach ($this->pdo_condition_parameters as $placeholder => $value) {
        $this->conditions_sql_str = str_replace(
          $placeholder,
          $value,
          $this->conditions_sql_str
        );
      }

      $this->sql_query = "$this->sql_query $this->conditions_sql_str";

      $result = $this->conn->prepare(trim($this->sql_query));
      $result->execute();

      return new Result($result->fetchAll($this->fetch_mode));

    } catch (Exception $e) {

      if ($this->is_debug_mode_on) {
        echo $e->getMessage();
        return false;
      }

      return false;
    }
  }

}

?>
