<?php

namespace NhrDev\NHR_DB\SRC;

/**
 * A helper class for the fetched data
 */
class NHR_Result
{

  private array $rows;

  function __construct($rows)
  {
    $this->rows = $rows ? $rows : [];
  }

  /**
   * Loop through the rows
   * @param callable $func
   * @param bool $reverse
   * @return NHR_Result
   */
  function each(callable $func, bool $reverse = false)
  {
    $rows = ($reverse) ? array_reverse($this->rows) : $this->rows;
    foreach ($rows as $ind => $row) {
      $func($row, $ind);
    }
    return $this;
  }

  /**
   * Returns the first row of the table
   * @return object|array|bool If first row exists then returns an `object` or `array` otherwise `false`
   */
  function first()
  {
    return (count($this->rows) > 0) ? $this->rows[0] : false;
  }

  /**
   * Returns the last row of the table
   * @return object|array|false If last row exists then returns an `object` or `array` otherwise `false`
   */
  function last()
  {
    return (count($this->rows) > 0) ? end($this->rows) : false;
  }

  /**
   * Returns a row by index
   * @param int $index
   * @return object|array|bool If the row with that specific index exists then returns an `object` or `array` otherwise `false`
   */
  function get(int $index)
  {
    if ($index < 0) {
      return false;
    }
    return (count($this->rows) > $index) ? $this->rows[$index] : false;
  }

  /**
   * Returns all the rows
   * @return array
   */
  function all()
  {
    return $this->rows;
  }

  /**
   * Returns all the rows in reversed order
   * @return array
   */
  function reverse()
  {
    return array_reverse($this->rows);
  }

  /**
   * Returns the number of rows fetched
   * @return int
   */
  function num_rows()
  {
    return count($this->rows) ? count($this->rows) : 0;
  }

  /**
   * Checks if there's no rows fetched or not
   * @return bool
   */
  function is_empty()
  {
    return count($this->rows) === 0;
  }

}

?>
