<?php
	/*
	* Class: simpleRSS
	* Purpose: to build RSS (V0.91+) channel files from provided textual inputs
	* Author: Alexey Kulikov - alex@pvl.at, alex@inses.biz
	* Further Notes: 
	* 	1) RSS 0.92 specs are to be found here - http://backend.userland.com/rss092, http://backend.userland.com/rss
	* 		 Note: you can enforse RSS2.0 by adding additional elements
	* 	2) All date-times in RSS conform to the Date and Time Specification of RFC 822.
	* 
	* Example Usage:
	* <code>
	* 	$myRSS = new simpleRSS(	"Alex Says",
	* 							"http://www.pvl.at",
	* 							"Fall on your knees and pray the big lord",
	* 							array("ttl"=>60,"lastBuildDate"=>"Tue, 25 Mar 2003 13:13:31 GMT"));
	* 
	* 	$myRSS->addItem("Hello","http://www.pvl.at","Hell is here");
	* 	$myRSS->addItem("Goodbye","http://www.pvl.at","Heaven is here");
	* 
	* 	$myRSS->create("myFeed.xml");
	* </code>
	**/
	class simpleRSS{
		//required channel data
		var $version = "2.0";	//RSS Version for output
		var $title;				//title of the channel
		var $link;				//link to the channel
		var $description;		//description of the channel 
		
		//optional Channel Data
		var $optional = array();
		
		//items
		//@access private
		var $items = array();
		
		
		/**
		 * simpleRSS::simpleRSS() - constructor
		 * 
		 * @param $title		(string)
		 * @param $link			(string)
		 * @param $description	(string)
		 * @param $optional 	(array)
		 * @return void
		 * @access public
		 */
		function simpleRSS($title,$link,$description,$optional=""){
			$this->title = $title;
			$this->link = $link;
			$this->description = $description;
			
			if(is_array($optional)){
				$this->optional = $optional;
			}
			
			//flush data
			$this->items = array();
		}
		
		
		/**
		 * simpleRSS::addItem() - adds an Item to end of the feeder
		 * 
		 * @param $title				(string)
		 * @param $link					(string)
		 * @param $description			(string)
		 * @param $optional				(array)
		 * @return (void)
		 * @access public
		 */
		function addItem($title,$link,$description,$optional=""){
			$item = array(
							"title"			=> 	$title,
							"link"			=>	$link,
							"description"	=>	$description
						);
			
			//RSS2.0 upgrade if needed						
			if(is_array($optional)){
				$item = array_merge($item,$optional);
			}
			
			$this->items[] = $item;
		}
		
		
		/**
		 * simpleRSS::create() - creates an RSS XML file at the specified location
		 * 
		 * @param $fileName	(string)
		 * @return (bool)
		 * @access public
		 */
		function create($fileName,$path=null,$charset=CHARSET){
			$channel = array(
								"title"			=>	strip_tags($this->title),
								"link"			=>	$this->link,
								"description"	=>	strip_tags($this->description)
							);
			
			if(is_array($this->optional)){
				$channel = array_merge($channel, $this->optional);
			}
			
			//prepare output
			$out = "<?xml version=\"1.0\" encoding=\"".$charset."\"?>\n<rss version=\"2.0\" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\">\n<channel>\n" . 
					$this->_parse($channel) . 
					$this->_parse($this->items) . 
					"\n</channel>" .
					"\n</rss>";
		
			//create RSS feed file
			$new_file = @fopen($path.$fileName, "w");
			if($new_file){
				fputs($new_file, $out);
				fclose($new_file);
				//rename($path.$tempFile,$path.$fileName);
				@chmod($path.$fileName,0777);
				return true;
			}else{
				return false;
			}
		}
		
		function printXML($charset=CHARSET){
			$channel = array(
								"title"			=>	strip_tags($this->title),
								"link"			=>	$this->link,
								"description"	=>	strip_tags($this->description)
							);
			
			if(is_array($this->optional)){
				$channel = array_merge($channel, $this->optional);
			}
			
			//prepare output
			echo "<?xml version=\"1.0\" encoding=\"".$charset."\"?>\n<rss version=\"2.0\" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\">\n<channel>\n" . 
					$this->_parse($channel) . 
					$this->_parse($this->items) . 
					"\n</channel>" .
					"\n</rss>";
		}		
		
		
		/**
		 * simpleRSS::getItemCount() - return number of items in object
		 * 
		 * @return (int)
		 * @access public
		 */
		function getItemCount(){
			return count($this->items);
		}
		
		
		/**
		 * simpleRSS::getItems() - return the items of the feeder
		 * 
		 * @return (array)
		 * @access public
		 */
		function getItems(){
			return $this->items;
		}
		
		########## end of public access ##########
		
		/**
		 * simpleRSS::_parse() - recursive function to create XML from associative arrays
		 * 
		 * @param $toParse
		 * @return (string)
		 * @access private
		 */
		function _parse($toParse){
		    $out = '';
			while(list($key,$val) = each($toParse)){
				//fix integer keys
				if(is_int($key)){
					$key = "item";
				}
				
				//check if this is an enclosure
				if($key == 'enclosure'){
					$out .= "<enclosure url=\"" . $val['url'] . "\" type=\"" . $val['type'] . "\" length=\"" . $val['length'] . "\" />";
					continue; // go to next element
				}else{
					//open tag
					$out .= "<" . $key . ">";
				}
				
				//check for subtags
				if(is_array($val)){	//yes
					$out .= $this->_parse($val);
				}else{	//no
					if(false && $key != "title"){
						$out .= str_replace("&amp;","&",htmlspecialchars($val));
					}else{
						$out .= htmlspecialchars($val);
					}
					//$out .= htmlentities($val);
				}
				
				//close tag
				$out .= "</" . $key . ">\n";
			}
			return $out; 
		}
	}
?>