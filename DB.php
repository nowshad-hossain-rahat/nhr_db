<?php

    namespace Nowshad;

    use PDO;
    use Exception;


    class DB {

        private $conn = null;
        private $driver, $host, $charset, $db, $user, $pass, $port;

        public const OBJ = PDO::FETCH_OBJ;
        public const ASSOC = PDO::FETCH_ASSOC;
        public const IND = PDO::FETCH_NUM;


        function __construct(array $info){

            $this->driver = isset($info["driver"]) ? $info["driver"]:"mysql";
            $this->host = isset($info["host"]) ? $info["host"]:"localhost";
            $this->user = $info["user"];
            $this->pass = $info["pass"];
            $this->db = $info["dbname"];
            $this->port = isset($info["port"]) ? $info["port"]:"";
            $this->charset = isset($info["charset"]) ? $info["charset"]:"";


            $this->connect();

        }

        # to disconnect
        function disconnect(){
            $this->conn = null;
            return true;
        }



        # to connect if not connected
        function connect(){
            try{
                if($this->conn==null){

                    $port = (empty($this->port)) ? "":"port=$this->port;";
                    $charset = (empty($this->charset)) ? "":"charset=$this->charset;";

                    $this->conn = @new PDO("$this->driver:host=$this->host;$port$charset",$this->user,$this->pass) or die("Error in Connection Building!\nCheck the information you've given on 'DB' setup!");
                    $this->conn->exec("create database if not exists $this->db");
                    $this->conn->exec("use $this->db");

                    return true;
                }
            }catch(Exception $e){}
        }




        # return ture/false based on connectivity
        function is_connected(){ return ($this->conn!=null); }


        # these will return specific data type and length steing of sql
        public static function int(int $l=255){ return "INT($l)"; }
        public static function float(){ return "FLOAT"; }
        public static function bigint(int $l=255){ return "BIGINT($l)"; }
        public static function unsigned_int(int $l=255){ return "INT($l) UNSIGNED"; }
        public static function unsigned_bigint(int $l=255){ return "BIGINT($l) UNSIGNED"; }
        public static function str(int $l=255){ return "VARCHAR($l)"; }
        public static function text(){ return "TEXT"; }
        public static function date(){ return "DATE"; }
        public static function datetime(){ return "DATETIME"; }
        public static function timestamp(){ return "TIMESTAMP"; }
        public static function enum(array $values=[0, 1]){
            $enum_values = '';
            foreach($values as $val){
                $comma = (end($values)==$val) ? '':', ';
                $enum_values = $enum_values."'$val'$comma";
            }
            return "ENUM($enum_values)";
        }


        # these functions are sql query conditions
        public static function lt(string $column_name, float $value){ return trim($column_name)." < ".trim($value); }
        public static function gt(string $column_name, float $value){ return trim($column_name)." > ".trim($value); }
        public static function lteq(string $column_name, float $value){ return trim($column_name)." <= ".trim($value); }
        public static function gteq(string $column_name, float $value){ return trim($column_name)." >= ".trim($value); }
        public static function noteq(string $column_name, $value){ return trim($column_name)." <> ".trim($value); }
        public static function eq(string $column_name, $value){ return trim($column_name)." = '".trim($value)."'"; }
        public static function like(string $column_name, $value){ return trim($column_name)." LIKE '%".trim($value)."%'"; }
        public static function bgins_like(string $column_name, $value){ return trim($column_name)." LIKE '".trim($value)."%'"; }
        public static function ends_like(string $column_name, $value){ return trim($column_name)." LIKE '%".trim($value)."'"; }
        public static function offset(string $order_by, int $rows_to_skip, int $rows_to_fetch = -1){
            $query_str_part = "ORDER BY $order_by OFFSET $rows_to_skip ROWS";
            if( $rows_to_fetch > -1 ){ $query_str_part .= " FETCH NEXT $rows_to_fetch ROWS ONLY"; }
            return $query_str_part;
        }

        # thsese two functions are condition setter
        public static function or(...$conditions){ return join(" OR ", $conditions); }
        public static function and(...$conditions){ return join(" AND ", $conditions); }

        # to perform PDO::quote(string)
        public function quote(string $data){ return $this->conn->quote($data); }


        # to get last inserted id
        public function last_insert_id(){
            return $this->conn->lastInsertId() === -1 ? false : $this->conn->lastInsertId();
        }

        # to create foreign key query string
        public static function foreign(string $column_name){

            return new class($column_name){

                private string $foreign_key_query;
                private string $foreign_key_name;
                private string $to_col;
                private string $on_table;

                function __construct(string $foreign_key_name){
                    $this->foreign_key_name = $foreign_key_name;
                }

                function references(string $column_name){
                    $this->to_col = $column_name;
                    return $this;
                }

                function on(string $table_name){

                    if (!isset($this->to_col) || empty($this->to_col)) { return $this; }
                    $this->on_table = $table_name;
                    $this->foreign_key_query = "FOREIGN KEY ($this->foreign_key_name) REFERENCES $this->on_table($this->to_col)";
                    return $this;
                }

                function on_delete(string $action){

                    if (!isset($this->foreign_key_query) || empty($this->foreign_key_query)) { return $this; }
                    $this->foreign_key_query .= " ON DELETE " . strtoupper($action);
                    return $this;
                }

                function on_update(string $action){

                    if (!isset($this->foreign_key_query) || empty($this->foreign_key_query)) { return $this; }
                    $this->foreign_key_query .= " ON UPDATE " . strtoupper($action);
                    return $this;
                }

                function __get_query(){ return (!isset($this->foreign_key_query) || empty($this->foreign_key_query)) ? false:$this->foreign_key_query; }

            };

        }

        # to select or create a new table
        function table(string $table_name){

            return new class($table_name,$this->conn) {

                private array $columns = array();
                private array $col_names = array();
                private $table_name = null;
                private array $foreign_keys = array();

                function __construct(string $table_name,$conn){
                    $this->table_name = $table_name;
                    $this->conn = $conn;
                }


                # to add columns to the table
                function col(string $name,string $type_and_length,bool $is_primary=false, $is_auto_increment=false, bool $is_not_null=false,bool $is_unique=false, string $default='', string $on_update=''){

                    $is_primary = ($is_primary) ? "PRIMARY KEY":"";
                    $is_auto_increment = ($is_auto_increment) ? "AUTO_INCREMENT":"";
                    $is_not_null = ($is_not_null) ? "NOT NULL":"";
                    $is_unique = (!$is_primary && $is_unique) ? "UNIQUE":"";
                    $default = (!empty($default)) ? "DEFAULT ".strtoupper($default):"";
                    $on_update = (!empty($on_update)) ? "ON UPDATE ".strtoupper($on_update):"";

                    $q = "$name $type_and_length $is_primary $is_auto_increment $is_not_null $is_unique $default $on_update";

                    $this->col_names[] = $name;
                    $this->columns[$name] = $q;

                    return $this;
                }


                # to create an auto incrementable primary key `id` column
                function id(){ return $this->col('id', DB::int(), true, true, true, true); }

                # to create two columns `created_at` and `updated_at`
                function timestamp(){
                    return $this->col('created_at', DB::timestamp(), false, false, true, false, 'current_timestamp', 'current_timestamp')
                                ->col('updated_at', DB::timestamp(), false, false, true, false, 'current_timestamp', 'current_timestamp');
                }

                # to create a varchar column
                function str(string $column_name, int $length = 255){ return $this->col($column_name, DB::str($length)); }

                # to create a text column
                function text(string $column_name){ return $this->col($column_name, DB::text()); }

                # to create a date column
                function date(string $column_name){ return $this->col($column_name, DB::date(), false, false, true); }

                # to create a datetime column
                function datetime(string $column_name){ return $this->col($column_name, DB::datetime(), false, false, true); }

                # to create an enum column
                function enum(string $column_name, array $values = [0, 1]){ return $this->col($column_name, DB::enum($values), false, false, true); }

                # to create a unsigned integer column
                function unsigned_int(string $column_name, int $length = 255){ return $this->col($column_name, DB::unsigned_int($length)); }

                # to create a unsigned big integer column
                function unsigned_bigint(string $column_name, int $length = 255){ return $this->col($column_name, DB::unsigned_bigint($length)); }

                # to create a float column
                function float(string $column_name){ return $this->col($column_name, DB::float()); }

                # to create a big integer column
                function bigint(string $column_name, int $length = 255){ return $this->col($column_name, DB::bigint($length)); }

                # to create foreing keys
                function foreign_keys(...$fks){
                    foreach($fks as $fk){
                        if( $fk->__get_query() ){
                            $this->foreign_keys[] = $fk->__get_query();
                        }
                    }
                    return $this;
                }

                # to alter new columns to the table after it's created
                function add(string $name,string $type_and_length,bool $is_primary=false, $is_auto_increment=false, bool $is_not_null=false,bool $is_unique=false){

                    $is_primary = ($is_primary) ? "PRIMARY KEY":"";
                    $is_auto_increment = ($is_auto_increment) ? "AUTO_INCREMENT":"";
                    $is_not_null = ($is_not_null) ? "NOT NULL":"";
                    $is_unique = (!$is_primary && $is_unique) ? "UNIQUE":"";

                    try{
                        $q = "$name $type_and_length $is_primary $is_auto_increment $is_not_null $is_unique";
                        $this->conn->exec("ALTER TABLE $this->table_name ADD $q");

                        $this->col_names[] = $name;
                        $this->columns[$name] = $q;
                    }catch(Exception $e){}

                    return $this;
                }


                // to drop the selected column of the table
                function drop(string $name){
                    try{
                        $this->conn->exec("ALTER TABLE ".$this->table_name." DROP $name");
                        unset($this->col_names[array_search($name,$this->col_names)]);
                        unset($this->columns[$name]);
                    }catch(Exception $e){}
                    return $this;
                }


                // to drop the whole table
                function drop_all(){
                    try{
                        $this->conn->exec("DROP TABLE ".$this->table_name);
                        $this->columns = array();
                        $this->col_names = array();
                    }catch(Exception $e){}
                    return $this;
                }



                # to insert rows into the table
                function insert(array $data){
                    if(count($data)>0){
                        $cols = "";$keys = "";$params = array();

                        foreach($data as $k=>$v){
                            $end = ($v==end($data)) ? "":",";
                            $cols = $cols.$k.$end;
                            $keys = $keys.":$k".$end;
                            $params[":$k"] = $v;
                        }

                        $q = "INSERT INTO ".$this->table_name." ($cols) VALUES ($keys)";

                        try{
                            $result = $this->conn->prepare($q);
                            $result->execute($params);
                            return $result->rowCount();
                        }catch(Exception $e){
                            return 0;
                        }

                        return 0;

                    }else{return 0;}
                }


                # to perform delete operations
                function delete(array $data){
                    if(count($data)>0){
                        $keys = "WHERE ";$params = array();
                        foreach($data as $k=>$v){
                            $end = ($v==end($data)) ? "":" && ";
                            $keys = $keys."$k=:$k".$end;
                            $params[":$k"] = $v;
                        }

                        try{
                            $q = "DELETE FROM $this->table_name $keys";
                            $result = $this->conn->prepare($q);
                            $result->execute($params);
                            return $result->rowCount();
                        }catch(Exception $e){
                            return 0;
                        }

                        return 0;

                    }else{ return 0; }
                }


                # to update the table
                function update(array $data,array $conditions){
                    $conds = "WHERE ";
                    $cols = "";
                    $params = array();

                    foreach($conditions as $k=>$v){
                        $end = ($v==end($conditions)) ? "":" && ";
                        $conds = $conds.$k."=".":$k"."_CONDITION$end";
                        $params[":$k"."_CONDITION"] = $v;
                    }

                    foreach($data as $col=>$val){
                        $end = ($val==end($data)) ? "":",";
                        $cols = $cols."$col=:$col $end";
                        $params[":$col"] = $val;
                    }

                    try{
                        $q = "UPDATE $this->table_name SET $cols $conds";
                        $result = $this->conn->prepare($q);
                        $result->execute($params);
                        return $result->rowCount();
                    }catch(Exception $e){
                        return -1;
                    }

                    return -1;

                }



                # to fetch data from the table
                function fetch($columns, array $conditions=[], $return_type = DB::ASSOC){

                    $conds = "";
                    $params = array();
                    $order_by = (!empty($conditions["ORDER_BY"])) ? "ORDER BY ".$conditions['ORDER_BY']:"";
                    $limit = (!empty($conditions["LIMIT"])) ? "LIMIT ".$conditions['LIMIT']:"";
                    $cols = '*';



                    # parsing the filtered columns
                    if( gettype( $columns ) == 'string' ){
                        $cols = '*';
                    }else if( gettype( $columns ) == 'array' ){
                        $cols = '';
                        foreach($columns as $column){
                            $comma = (end($columns) == $column) ? '':',';
                            $cols = $cols.$column.$comma;
                        }
                    }


                    # parsing the query conditions
                    foreach($conditions as $k=>$v){
                        unset($conditions["ORDER_BY"]);
                        unset($conditions["LIMIT"]);
                        $end = ($v==end($conditions) ) ? "":" AND ";
                        if( $k === 'or' ){ $conds .= $v.$end; }
                        else if( $k === 'and' ){ $conds .= $v.$end; }
                        else if( $k != 'LIMIT' && $k != 'ORDER_BY' ){
                            if( preg_match("/[0-9]+/", $k) ){
                                if( preg_match("/(OFFSET|ORDER BY)/", $v) && preg_match("/(ORDER BY)/", $conds) ){ continue; }
                                else if( preg_match("/(LIMIT)/", $v) && preg_match("/(OFFSET)/", $conds) ){ continue; }
                                $conds = $conds."$v"."$end";
                            }else{
                                $conds = $conds."$k=:$k"."$end";
                                $params[":$k"] = $v;
                            }
                        }
                    }

                    # checking if `WHERE` need to be added
                    if( !empty($conds) && preg_match("/[=<>]|(LIKE)/", $conds) ){ $conds = "WHERE $conds"; }

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

                    try{

                        # creating the sql query string
                        $q = "SELECT $cols FROM $this->table_name $conds";

                        # validating the query string
                        if( !preg_match("/(OFFSET)/", $q) ){ $q .= $order_by . ' ' . $limit; }
                        # preparing the sql statement
                        $result = $this->conn->prepare($q);

                        # executing the sql statement
                        count($params) > 0 ? $result->execute($params):$result->execute();

                        return new class($result->fetchAll($return_type)){

                            private $rows;

                            function __construct($rows){
                                $this->rows = $rows;
                            }

                            # to loop through the rows
                            function each(callable $func,bool $reverse=false){
                                $rows = ($reverse) ? array_reverse($this->rows):$this->rows;
                                foreach($rows as $ind=>$row){
                                    $func($row,$ind);
                                }
                                return $this;
                            }

                            # to return the first row
                            function first(){
                                return (count($this->rows) > 0) ? $this->rows[0]:false;
                            }

                            # to return the last row
                            function last(){
                                return (count($this->rows) > 0) ? end($this->rows):false;
                            }

                            # to return a row by index
                            function get(int $index){
                                if($index < 0){ return false; }
                                return (count($this->rows) > $index) ? $this->rows[$index]:false;
                            }

                            # to return all the rows
                            function all(){ return $this->rows; }

                            # to return all reverse
                            function reverse(){ return array_reverse($this->rows); }

                            # to check if there is no rows
                            function is_empty(){ return count($this->rows) === 0; }

                        };

                    }catch(Exception $e){
                        return false;
                    }

                    return false;

                }




                # to fetch data from the table using custom sql query
                function fetch_custom(string $query, array $params, $return_type = DB::ASSOC){

                    try{

                        $result = $this->conn->prepare($query);
                        count($params) > 0 ? $result->execute($params):$result->execute();

                        return new class($result->fetchAll($return_type)){

                            private $rows;

                            function __construct($rows){
                                $this->rows = $rows;
                            }

                            # to loop through the rows
                            function each(callable $func,bool $reverse=false){
                                $rows = ($reverse) ? array_reverse($this->rows):$this->rows;
                                foreach($rows as $ind=>$row){
                                    $func($row,$ind);
                                }
                                return $this;
                            }

                            # to return the first row
                            function first(){
                                return (count($this->rows) > 0) ? $this->rows[0]:false;
                            }

                            # to return the last row
                            function last(){
                                return (count($this->rows) > 0) ? end($this->rows):false;
                            }

                            # to return a row by index
                            function get(int $index){
                                if($index < 0){ return false; }
                                return (count($this->rows) > $index) ? $this->rows[$index]:false;
                            }

                            # to return all the rows
                            function all(){ return $this->rows; }

                            # to return all reverse
                            function reverse(){ return array_reverse($this->rows); }

                        };

                    }catch(Exception $e){
                        return false;
                    }

                    return false;

                }




                # to count total number of rows in the table
                function num_rows(){

                    try{
                        $q = "SELECT COUNT(*) FROM ".$this->table_name;
                        $result = $this->conn->prepare($q);
                        $result->execute();
                        return intval($result->fetchAll(PDO::FETCH_ASSOC)[0]['COUNT(*)']);
                    }catch(Exception $e){
                        return -1;
                    }

                    return -1;

                }





                # will create the table and all the columns added by 'col' function
                function create(){

                    $q = null;

                    foreach($this->columns as $i=>$v){
                        $end = ($v==end($this->columns)) ? "":",";
                        $q = $q.$v.$end;
                    }

                    if (count($this->foreign_keys) > 0) {
                        $q .= ', ';
                        foreach ($this->foreign_keys as $i => $v) {
                            $end = ($v == end($this->foreign_keys)) ? "" : ",";
                            $q = $q . $v . $end;
                        }
                    }

                    $query = "CREATE TABLE IF NOT EXISTS ".$this->table_name." ($q)";

                    try{
                        if( $this->conn ){
                            $this->conn->connect();
                            return $this->conn->exec($query) === false;
                        }else{
                            return false;
                        }
                    }
                    catch(Exception $e){ return false; }

                    return false;

                }

            };

        }



    }

?>
