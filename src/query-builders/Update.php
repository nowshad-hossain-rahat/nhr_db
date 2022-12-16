<?php

namespace NhrDev\NHR_DB\Src\QueryBuilders;

use Exception;
use NhrDev\NHR_DB\Src\Table;
use PDO;

class Update extends SQLQueryBuilder
{

  function __construct(PDO $db_connection, Table $table, bool $for_pdo = true, bool $is_debug_mode_on = false)
  {
    parent::__construct($db_connection, $table, $for_pdo, $is_debug_mode_on);
  }

  /**
   * Refers to SQL `UPDATE` operation
   * @param array $columns_and_values [ 'name' => 'username', 'email' => 'example@gmail.com' ]
   * @return Update
   */
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
    parent::where($column_name, $operator, $value);
    return $this;
  }


  /**
   * Executes the SQL query
   * @return int
   */
  public function execute()
  {
    try {
      $this->sql_query = $this->sql_query . " " . trim($this->get_conditions());
      echo $this->sql_query;
      // $result = $this->conn->prepare($this->sql_query);
      // $result->execute($this->parameters_for_pdo);
      // return $result->rowCount();
      return 0;
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
