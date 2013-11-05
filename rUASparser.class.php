<?php
/**
some improvements for official clas
 */
 

require_once('rlib/UASparser.php');

class rUASparser extends UASparser
{
    public $updateInterval =   286400; // 1 day

    protected $is_bot = null;
    protected $is_mobile = null;

    
    public function __construct($cacheDirectory = null, $updateInterval = null) {
        if(!$cacheDirectory) $cacheDirectory = dirname(__FILE__).'/uas-cache';
        if(!is_dir($cacheDirectory)) @mkdir($cacheDirectory, 0777);
        parent::__construct($cacheDirectory, $updateInterval);
    }

    public function Parse()
    {
        $info = parent::Parse();
        $this->is_bot = !empty($info['typ']) && ($info['typ'] == 'Robot');
        $this->is_mobile = !empty($info['typ']) && ($info['typ'] == 'Mobile Browser' || $info['typ'] == 'Wap Browser');

        return $info;
    }


    public function isBot()
    {
        if($this->is_bot === NULL) $this->Parse();
        return $this->is_bot;
    }

    public function isMobile()
    {
        if($this->is_mobile === NULL) $this->Parse();
        return $this->is_mobile;
    }

}