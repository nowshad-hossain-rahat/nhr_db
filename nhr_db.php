<?php
    
    // this is the base class to manage SQL Databases
    
    class DB {
        
        protected $conn = null,
                $driver,$host,
                $port,$charset,
                $db,$user,$pass;
        public const
                OBJ = PDO::FETCH_OBJ,
                ASSOC = PDO::FETCH_ASSOC,
                IND = PDO::FETCH_NUM;
                
        
        function __construct(array $info){
            
            $this->driver = $info["driver"];
            $this->host = $info["host"];
            $this->user = $info["user"];
            $this->pass = $info["pass"];
            $this->db = $info["dbname"];
            $this->port = $info["port"];
            $this->charset = $info["charset"];
            
            
            $this->connect();
            
        }
        
        // to disconnect
        function disconnect(){
            $this->conn = null;
            return true;
        }
        
        // to connect if not connected
        function connect(){
            try{
                if($this->conn==null){
                    
                    $port = (empty($this->port)) ? "":"port=$this->port;";
                    $charset = (empty($this->charset)) ? "":"charset=$this->charset;";
                    
                    $this->conn = new PDO("$this->driver:host=$this->host;$port$charset",$this->user,$this->pass) or die("Error in Connection Building!\nCheck the information you've given on 'DB' setup!");
                    $this->conn->exec("create database if not exists $this->db");
                    $this->conn->exec("use $this->db");
                    
                    return true;
                }
            }catch(Exception $e){} 
        }
        
        // return ture/false based on connectivity
        function is_connected(){ return ($this->conn!=null); }
        
        
        function new_table(string $table_name){
            return new class($table_name,$this->conn) {
                
                private $columns = array(),
                        $col_names = array(),
                        $table_name = null;
                
                function __construct(string $table_name,$conn){
                    $this->table_name = $table_name;
                    $this->conn = $conn;
                }
                
                
                # thia will return specific data type and length steing of sql
                function int(int $l){ return "integer($l)"; }
                function str(int $l){ return "varchar($l)"; }
                function txt(){ return "text"; }
                function dat(){ return "date"; }
                function datime(){ return "datetime"; }
                function bool(){ return "enum('0','1')"; }
                
                
                function col(string $name,string $type_and_length,bool $is_primary=false,bool $is_not_null=false,bool $is_unique=false){
                    
                    $is_primary = ($is_primary) ? "primary key auto_increment":"";
                    $is_not_null = ($is_not_null) ? "not null":"";
                    $is_unique = (!$is_primary && $is_unique) ? "unique":"";
                    
                    $q = "$name $type_and_length $is_primary $is_not_null $is_unique";
                    
                    $this->col_names[] = $name;
                    $this->columns[$name] = $q;
                    
                    return $this;
                }
                
                        
                function add(string $name,string $type_and_length,bool $is_primary=false,bool $is_not_null=false,bool $is_unique=false){
                    
                    $is_primary = ($is_primary) ? "primary key auto_increment":"";
                    $is_not_null = ($is_not_null) ? "not null":"";
                    $is_unique = (!$is_primary && $is_unique) ? "unique":"";
                    
                    $q = "$name $type_and_length $is_primary $is_not_null $is_unique";
                    $this->conn->exec("alter table $this->table_name add $q");
                    
                    $this->col_names[] = $name;
                    $this->columns[$name] = $q;
                    
                    return $this;
                }
                
                function drop(string $name){
                    $this->conn->exec("alter table $this->table_name drop $name");
                    unset($this->col_names[array_search($name,$this->col_names)]);
                    unset($this->columns[$name]);
                    return $this;
                }
                
                
                // will drop the whole table
                function drop_all(){
                    $this->conn->exec("drop table $this->table_name");
                    $this->columns = array();
                    $this->col_names = array();
                    return $this;
                }
                
                
                function insert(array $data){
                    if(count($data)>0){
                        $cols = "";$keys = "";$params = array();
                        
                        foreach($data as $k=>$v){
                            $end = ($v==end($data)) ? "":",";
                            $cols = $cols.$k.$end;
                            $keys = $keys.":$k".$end;
                            $params[":$k"] = $v;
                        }
                        
                        $q = "insert into $this->table_name ($cols) values ($keys)";
                        $result = $this->conn->prepare($q);
                        $result->execute($params);
                        return $result->rowCount();
                    }else{return 0;}
                }
                
                
                function delete(array $data){
                    if(count($data)>0){
                        $keys = "where ";$params = array();
                        foreach($data as $k=>$v){
                            $end = ($v==end($data)) ? "":" && ";
                            $keys = $keys."$k=:$k".$end;
                            $params[":$k"] = $v;
                        }
                        
                        $q = "delete from $this->table_name $keys";
                        $result = $this->conn->prepare($q);
                        $result->execute($params);
                        return $result->rowCount();
                    }else{return 0;}
                }
                
                
                function update(array $conditions,array $data){
                    $conds = "where ";
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
                    
                    $q = "update $this->table_name set $cols $conds";
                    $result = $this->conn->prepare($q);
                    $result->execute($params);
                    return $result->rowCount();
                    
                }
                
                
                function fetch(array $conditions=[],$return_type = DB::ASSOC){
                    
                    $conds = null;
                    $params = array();
                    $order_by = (!empty($conditions["ORDER_BY"])) ? "order by ".$conditions['ORDER_BY']:"";
                    $limit = (!empty($conditions["LIMIT"])) ? "limit ".$conditions['LIMIT']:"";
                    
                    foreach($conditions as $k=>$v){
                        unset($conditions["ORDER_BY"]);
                        unset($conditions["LIMIT"]);
                        if($k!="ORDER_BY" && $k!="LIMIT"){
                            $end = ($v==end($conditions) ) ? "":" && ";
                            $conds = $conds."$k=:$k"."$end";
                            $params[":$k"] = $v;
                        }
                    }
                    
                    $conds = (!empty($conds)) ? "where $conds":"";
                    
                    $q = "select * from $this->table_name $conds $order_by $limit";
                    $result = $this->conn->prepare($q);
                    $result->execute($params);
                    $fetched_data = $result->fetchAll($return_type);
                    
                    return new class($fetched_data){
                        private $rows;
                        function __construct($rows){
                            $this->rows = $rows;
                        }
                        
                        function each($func,bool $reverse=false){
                            $rows = ($reverse) ? array_reverse($this->rows):$this->rows;
                            foreach($rows as $ind=>$row){
                                $func($row,$ind);
                            }
                            return $this;
                        }
                        
                        function first(){return $this->rows[0];}
                        
                        function last(){return end($this->rows);}
                        
                        function get(int $index){return $this->rows[$index];}
                        
                    };
                    
                
                }
                
                
                // will create the table and all the columns added by 'col' function
                function create(){
                    $q = null;
                    foreach($this->columns as $i=>$v){
                        $end = ($v==end($this->columns)) ? "":",";
                        $q = $q.$v.$end;
                    }
                    
                    $query = "create table if not exists $this->table_name ($q)";
                    
                    $this->conn->exec($query);
                    return true;
                }
                
            };
        }
    
    
    
    }
    
?>