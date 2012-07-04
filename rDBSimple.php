<?php

// for pear-compatibility

require_once('rlib/dbsimple/Generic.php');

class rDBSimple extends DbSimple_Generic{

}

class dbException extends Exception{
    protected $info ;
    function __construct($info){
        $this->info = $info;
        parent::__construct(print_r($info, 1), 0);
    }
    
    public function getInfo(){
        return $this->info;
    }
}
    
    
function stdDBErrorHandler($message, $info){	
    if (!error_reporting()){
        return;
    }else{
        throw new dbException($info);
    }
    exit;
}