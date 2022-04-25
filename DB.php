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
        public static function bigint(int $l=255){ return "BIG($l)"; }
        public static function unsigned_int(int $l=255){ return "INT($l) UNSIGNED"; }
        public static function str(int $l=255){ return "VARCHAR($l)"; }
        public static function text(){ return "TEXT"; }
        public static function date(){ return "DATE"; }
        public static function datetime(){ return "DATETIME"; }
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
        public static function like(string $column_name, $value){ return trim($column_name)." LIKE ".trim($value); }

        # thsese two functions are condition setter
        public static function or(...$conditions){ return join(" OR ", $conditions); }
        public static function and(...$conditions){ return join(" AND ", $conditions); }

        # to perform PDO::quote(string)
        public function quote(string $data){ return $this->conn->quote($data); }


        # to get last inserted id
        function last_insert_id(){
            return $this->conn->lastInsertId() === -1 ? false : $this->conn->lastInsertId();
        }


        # to select or create a new table
        function table(string $table_name){

            return new class($table_name,$this->conn) {

                private $columns = array(),
                        $col_names = array(),
                        $foreign_keys = array(),
                        $table_name = null;

                function __construct(string $table_name,$conn){
                    $this->table_name = $table_name;
                    $this->conn = $conn;
                }


                # to add columns to the table
                function col(string $name,string $type_and_length,bool $is_primary=false, $is_auto_increment=false, bool $is_not_null=false,bool $is_unique=false){

                    $is_primary = ($is_primary) ? "PRIMARY KEY":"";
                    $is_auto_increment = ($is_auto_increment) ? "AUTO_INCREMENT":"";
                    $is_not_null = ($is_not_null) ? "NOT NULL":"";
                    $is_unique = (!$is_primary && $is_unique) ? "UNIQUE":"";

                    $q = "$name $type_and_length $is_primary $is_auto_increment $is_not_null $is_unique";

                    $this->col_names[] = $name;
                    $this->columns[$name] = $q;

                    return $this;
                }


                # to create an auto incrementable primary key `id` column
                function id(){ return $this->col('id', DB::int(), true, true, true, true); }

                # to create two columns `created_at` and `updated_at`
                function timestamp(){ return $this->col('created_at', DB::datetime())->col('updated_at', DB::datetime()); }

                # to create a varchar column
                function str(string $column_name, int $length = 255){ return $this->col($column_name, DB::str($length)); }

                # to create a text column
                function text(string $column_name){ return $this->col($column_name, DB::text()); }

                # to create a date column
                function date(string $column_name){ return $this->col($column_name, DB::date()); }

                # to create a datetime column
                function datetime(string $column_name){ return $this->col($column_name, DB::datetime()); }

                # to create an enum column
                function enum(string $column_name, array $values = [0, 1]){ return $this->col($column_name, DB::enum($values)); }

                # to create a unsigned integer column
                function unsigned_int(string $column_name, int $length = 255){ return $this->col($column_name, DB::unsigned_int($length)); }

                # to create a float column
                function float(string $column_name){ return $this->col($column_name, DB::float()); }

                # to create a big integer column
                function bigint(string $column_name, int $length = 255){ return $this->col($column_name, DB::bigint($length)); }

                # to create foreign key
                function foreign(string $foreign_key_name){

                    return new class($this, $foreign_key_name){

                        private $parent_table;
                        private string $foreign_key_query;
                        private string $foreign_key_name;
                        private string $to_col;
                        private string $on_table;

                        function __construct($parent_table, string $foreign_key_name){
                            $this->parent_table = $parent_table;
                            $this->foreign_key_name = $foreign_key_name;
                        }

                        function references(string $column_name){ $this->to_col = $column_name; return $this; }

                        function on(string $table_name){

                            if( !isset($this->to_col) ){ return $this; }
                            $this->on_table = $table_name;
                            $this->foreign_key_query = "FOREIGN KEY ($this->foreign_key_name) REFERENCES $this->on_table($this->to_col)";
                            $this->parent_table->foreign_keys[$this->foreign_key_name] = $this->foreign_key_query;

                        }

                        function on_delete(string $action){

                            if( !isset($this->foreign_key_query) ){ return $this; }
                            $this->parent_table->foreign_keys[$this->foreign_key_name] = $this->foreign_key_query . " ON DELETE ".strtoupper($action);

                        }

                        function on_update(string $action){

                            if( !isset($this->foreign_key_query) ){ return $this; }
                            $this->parent_table->foreign_keys[$this->foreign_key_name] = $this->foreign_key_query . " ON UPDATE ".strtoupper($action);

                        }

                        function create(){ return $this->parent_table->create(); }

                    };

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

                    $conds = null;
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
                        else{
                            $conds = $conds."$k=:$k"."$end";
                            $params[":$k"] = $v;
                        }
                    }


                    $conds = (!empty($conds)) ? "WHERE $conds":"";

                    try{

                        $q = "SELECT $cols FROM $this->table_name $conds $order_by $limit";
                        $result = $this->conn->prepare($q);
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
                function numRows(){

                    try{
                        $q = "SELECT * FROM ".$this->table_name;
                        $result = $this->conn->prepare($q);
                        $result->execute();
                        return count($result->fetchAll(PDO::FETCH_ASSOC));
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

                    foreach($this->foreign_keys as $i=>$v){
                        $end = ($v==end($this->foreign_keys)) ? "":",";
                        $q = $q.$v.$end;
                    }

                    $query = "CREATE TABLE IF NOT EXISTS ".$this->table_name." ($q)";

                    try{ return $this->conn->exec($query) === false; }
                    catch(Exception $e){ return false; }

                    return false;

                }

            };

        }



    }

?>
