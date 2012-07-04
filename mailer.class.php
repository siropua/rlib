<?php

/**
 * 
 *
 * @version $Id: mailer.class.php,v 1.1 2007/11/18 12:40:44 steel Exp $
 * @copyright 2006 
 **/

 
define("DEBUG_OUTPUT", 1);

define("HLS_NO", 0);
define("HLS_FETCHNEXT", 1);

/** 
 * Main Mailer Object
 *
 **/
class RMailer{

	var $conn_str;
	var $mbox;
	var $connected;
	var $headList_status = HLS_NO;
	var $headList_pos = 0;
	var $headCnt = 0;
	var $headersIDs = array();
	

	
	/**
     * Constructor
     * @access protected
     */
	function RMailer($connection, $login, $pass)
	{
		$this->debug("Create connection to $connection...");
		$this->mbox=imap_open($connection, $login, $pass);
		// error connecting
		if(!$this->mbox)
		{
			trigger_error(imap_last_error());
			return false;
		}
		$this->debug("CONNECTED OK!");
		$this->connected=true;
		$this->headCnt=imap_num_msg($this->mbox);
	}
	
	/**
	 *
	 * @access public
	 * @return void 
	 **/
	function fetchNext($del=0)
	{
		if(!$this->is_connected() || !$this->headCnt)return FALSE;
		if( ++$this->headList_pos > $this->headCnt)return FALSE;
		$header=trim(imap_fetchheader($this->mbox, $this->headList_pos));

		$header=$this->parseHeader($header);
		$header['msg number']=$this->headList_pos;
		$header['body']=$this->getBody($this->headList_pos);
		if($del){
		    imap_delete($this->mbox, $this->headList_pos);
		    imap_expunge($this->mbox); 
		}
		return $header;
	}
	function getBody($id_msg){
		return imap_body ( $this->mbox, $id_msg); 
	}
	
	/**
	 *
	 * @access public
	 * @return array 
	 **/
	function getHeadersIDs()
	{
		$this->debug("Getting headers...");
		if(!$this->is_connected())return FALSE;
		//imap_fetch_overview($this->mbox,"1");
		
		//return imap_sort($this->mbox, SORTARRIVAL, SE_UID);
	}
	
	/**
	 *
	 * @access public
	 * @return void 
	 **/
	function is_connected()
	{
		return $this->connected && $this->mbox;
	}
	
	
	/**
	 * Disconnect from server
	 * @access public
	 * @return void 
	 **/
	function disconnect()
	{
		if(!$this->is_connected()) return;
		$this->debug("Disconnecting...");
		imap_close($this->mbox);
		$this->connected = false;
		$this->mbox = 0;
	}
	
	/**
	 *
	 * @access public
	 * @return void 
	 **/
	function debug($str)
	{
		if(!DEBUG_OUTPUT)return;
		echo($str."\n");
	}
	
	
	/**
	 *
	 * @access public
	 * @return void 
	 **/
	function parseHeader($header)
	{
//		return iconv_mime_decode_headers($header);
		$h_ret=array();
		$header=explode("\r\n", $header);
		$prev_match="";
		foreach($header as $n=>$v)
		{
			if(preg_match("/^([a-z_.0-9\\-]+): +(.+)$/i", $v, $match))
			{
				$match[1]=strtolower($match[1]);
				$h_ret[$match[1]]=$match[2];
				$prev_match=$match[1];
			}else
			{
				if($prev_match)
				{
					$h_ret[$prev_match].="\n".$v;
				}
			}
		}
		
		return $h_ret;		
	}
	
	
}

/** 
 * Mail Header Class
 *
 **/
class RMailHeader{
	
	var $head_a = array();
	var $fields = array();
	
	/**
     * Constructor
     * @access protected
     */
	function RMailHeader($header="")
	{
		if($header)$this->preParse($header);
	}
	
	/**
	 *
	 * @access public
	 * @return void 
	 **/
	function preParse($header)
	{
		$head_a=explode("\r\n", $header);
	}
	
	/**
	 *
	 * @access public
	 * @return void 
	 **/
	function getField($field_name, $default="")
	{
		if(array_key_exists($field_name, $this->fields))
			return $this->fields;
		else
			return parseField($field_name, $default);
	}
	
	/**
	 *
	 * @access public
	 * @return void 
	 **/
	function parseField($field_name, $default=""){
		foreach($this->head_a as $n=>$v)
		{
			if(substr($v, 0, strlen($field_name)+1))
			{
			
			}
		}
	}
	
	
}
?>