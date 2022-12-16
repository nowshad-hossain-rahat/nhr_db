<?php

namespace NhrDev\NHR_DB\Src\QueryBuilders;

use NhrDev\NHR_DB\Src\Table;
use PDO;

class Select extends SQLQueryBuilder
{

  function __construct(PDO $db_connection, Table $table, bool $for_pdo = true, bool $is_debug_mode_on = false)
  {
    parent::__construct($db_connection, $table, $for_pdo, $is_debug_mode_on);
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


  /**
   * This refers to SQL `FETCH NEXT [COUNT] ROWS ONLY`
   * @param int $number_of_rows_to_fetch
   * @return Select
   */
  public function fetch(int $number_of_rows_to_fetch)
  {
    $this->fetch_rows_count = $number_of_rows_to_fetch;
    return $this;
  }

}

?>
