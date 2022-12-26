<?php

namespace NhrDev\NHR_DB\Src\QueryBuilders;

use Exception;
use NhrDev\NHR_DB\Src\Table;
use PDO;

class Update extends SQLQueryBuilder
{

  private string $value_postfix = "_nhr_sql_value";
  private array $pdo_update_parameters = [];


  /**
   * Update query initializer
   * @param array $columns_and_values
   * @param PDO $db_connection
   * @param Table $table
   * @param bool $is_debug_mode_on
   */
  function __construct(array $columns_and_values, PDO $db_connection, Table $table, bool $is_debug_mode_on = false)
  {
    parent::__construct($db_connection, $table, $is_debug_mode_on);
    $this->generate_update_query($columns_and_values);
  }

  /**
   * Refers to SQL `UPDATE` operation
   * @param array $columns_and_values [ 'name' => 'username', 'email' => 'example@gmail.com' ]
   */
  private function generate_update_query(array $columns_and_values)
  {
    $update_columns = join(",", array_map(function ($key) {
      return "$key=:$key" . $this->value_postfix;
    }, array_keys($columns_and_values)));

    foreach ($columns_and_values as $col => $val) {
      $this->pdo_update_parameters[":" . $col . $this->value_postfix] = ((gettype($val) === 'string') ? "$val" : $val);
    }

    $this->sql_query = "UPDATE " . $this->table->get_name() . " SET $update_columns";
  }


  /**
   * This referes to the SQL `AND` operator
   * @param string $column_name
   * @param string $operator
   * @param mixed $value
   * @return Update
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
   * @return Update
   */
  public function or (string $column_name, string $operator, $value = null)
  {
    parent::or($column_name, $operator, $value);
    return $this;
  }


  protected function parse_special_operations()
  {
    if (!empty( $this->conditions_sql_str )) {
      $this->conditions_sql_str = "WHERE " . trim($this->conditions_sql_str);
    }
  }


  /**
   * Executes the SQL query
   * @return int
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

      $result = $this->conn->prepare(trim( $this->sql_query ));
      $result->execute($this->pdo_update_parameters);

      return $result->rowCount();

    } catch (Exception $e) {

      if ($this->is_debug_mode_on) {
        echo $e->getMessage();
        return -1;
      }

      return -1;
    }
  }

}

?>
