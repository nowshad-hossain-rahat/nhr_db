<?php
    
    
    class DB {
        
        private $conn = null;
        private $driver, $host, $charset, $db, $user, $pass, $port;

        public const OBJ = PDO::FETCH_OBJ;
        public const ASSOC = PDO::FETCH_ASSOC;
        public const IND = PDO::FETCH_NUM;
                
        
        function __construct(array $info){
            
            $this->driver = ($info["driver"]) ? $info["driver"]:"mysql";
            $this->host = ($info["host"]) ? $info["host"]:"localhost";
            $this->user = $info["user"];
            $this->pass = $info["pass"];
            $this->db = $info["dbname"];
            $this->port = $info["port"];
            $this->charset = $info["charset"];
            
            
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


        # to perform PDO::quote(string)
        public function quote(string $data){ return $this->conn->quote($data); }


        
        # to select or create a new table
        function table(string $table_name){
            
            return new class($table_name,$this->conn) {
                
                private $columns = array(),
                        $col_names = array(),
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
                
                        
                function add(string $name,string $type_and_length,bool $is_primary=false, $is_auto_increment=false, bool $is_not_null=false,bool $is_unique=false){
                    
                    $is_primary = ($is_primary) ? "PRIMARY KEY":"";
                    $is_auto_increment = ($is_auto_increment) ? "AUTO_INCREMENT":"";
                    $is_not_null = ($is_not_null) ? "NOT NULL":"";
                    $is_unique = (!$is_primary && $is_unique) ? "UNIQUE":"";
                    
                    $q = "$name $type_and_length $is_primary $is_auto_increment $is_not_null $is_unique";
                    $this->conn->exec("ALTER TABLE $this->table_name ADD $q");
                    
                    $this->col_names[] = $name;
                    $this->columns[$name] = $q;
                    
                    return $this;
                }
                
                function drop(string $name){
                    $this->conn->exec("ALTER TABLE ".$this->table_name." DROP $name");
                    unset($this->col_names[array_search($name,$this->col_names)]);
                    unset($this->columns[$name]);
                    return $this;
                }
                
                
                // will drop the whole table
                function drop_all(){
                    $this->conn->exec("DROP TABLE ".$this->table_name);
                    $this->columns = array();
                    $this->col_names = array();
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
                        $result = $this->conn->prepare($q);
                        $result->execute($params);
                        return $result->rowCount();
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
                        
                        $q = "DELETE FROM $this->table_name $keys";
                        $result = $this->conn->prepare($q);
                        $result->execute($params);
                        return $result->rowCount();
                    }else{return 0;}
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
                    
                    $q = "UPDATE $this->table_name SET $cols $conds";
                    $result = $this->conn->prepare($q);
                    $result->execute($params);
                    return $result->rowCount() ? $result->rowCount():false;
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
                        if($k!="ORDER_BY" && $k!="LIMIT"){
                            $end = ($v==end($conditions) ) ? "":" && ";
                            $conds = $conds."$k=:$k"."$end";
                            $params[":$k"] = $v;
                        }
                    }
                    

                    $conds = (!empty($conds)) ? "WHERE $conds":"";
                    
                    $q = "SELECT $cols FROM $this->table_name $conds $order_by $limit";
                    $result = $this->conn->prepare($q);
                    count($params) > 0 ? $result->execute($params):$result->execute();

                    return new class($result->fetchAll($return_type)){
                        
                        private $rows;

                        function __construct($rows){
                            $this->rows = $rows;
                        }
                        
                        # to loop through the rows
                        function each($func,bool $reverse=false){
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

                
                }
                



                # to count total number of rows in the table
                function numRows(){

                    $q = "SELECT * FROM ".$this->table_name;
                    $result = $this->conn->prepare($q);
                    $result->execute();
                    return count($result->fetchAll(PDO::FETCH_ASSOC));

                }




                
                # will create the table and all the columns added by 'col' function
                function create(){
                    $q = null;
                    foreach($this->columns as $i=>$v){
                        $end = ($v==end($this->columns)) ? "":",";
                        $q = $q.$v.$end;
                    }
                    
                    $query = "CREATE TABLE IF NOT EXISTS ".$this->table_name." ($q)";
                    
                    $this->conn->exec($query);
                    return true;
                }
                
            };

        }
    
    
    
    }
    
?>