<?php

namespace NhrDev\NHR_DB\Src;

class Foreign_Key
{

  private string $foreign_key_query;
  private string $foreign_key_name;
  private string $to_col;
  private string $on_table;

  function __construct(string $foreign_key_name)
  {
    $this->foreign_key_name = $foreign_key_name;
  }

  function references(string $column_name)
  {
    $this->to_col = $column_name;
    return $this;
  }

  function on(string $table_name)
  {

    if (!isset($this->to_col) || empty($this->to_col)) {
      return $this;
    }

    $this->on_table = $table_name;
    $this->foreign_key_query = "FOREIGN KEY ($this->foreign_key_name) REFERENCES $this->on_table($this->to_col)";

    return $this;
  }

  function on_delete(string $action)
  {

    if (!isset($this->foreign_key_query) || empty($this->foreign_key_query)) {
      return $this;
    }

    $this->foreign_key_query .= " ON DELETE " . strtoupper($action);

    return $this;
  }

  function on_update(string $action)
  {

    if (!isset($this->foreign_key_query) || empty($this->foreign_key_query)) {
      return $this;
    }

    $this->foreign_key_query .= " ON UPDATE " . strtoupper($action);

    return $this;
  }

  function __get_query()
  {
    return (!isset($this->foreign_key_query) || empty($this->foreign_key_query)) ? false : $this->foreign_key_query;
  }

}

?>
