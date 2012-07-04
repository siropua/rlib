<?php
/*

  SafeHTML Parser.
  v1.2.0.
  21 October 2004.

  ---------

  http://pixel-apes.com/safehtml

  Copyright (c) 2004, Roman Ivanov <mailto:thingol@mail.ru>
  All rights reserved.

  For LICENSE see license.txt

=============================================================== (kukutz@npj)
*/

require_once(dirname(__FILE__).'/HTMLSax.php');

class safehtml {
  var $xhtml = "";
  var $Counter;
  var $Stack = array();
  var $dcStack = array();
  var $Protopreg = array();
  var $csspreg = array();
  var $dcCounter;
  var $listScope = 0; // are we inside a list?
  var $liStack = array();

  // single tags ("<tag />")
  var $Singles = array("br", "area", "hr", "img", "input", "wbr");

  // dangerous tags
  var $Deletes = array("base", "basefont", "head", "html", "body", "applet", "object", "iframe", "frame", "frameset", "script", "layer", "ilayer", "embed", "bgsound", "link", "meta", "style", "title", "blink", "plaintext");

  // all content inside this tags will be also removed
  var $DeleteContent = array("script", "style", "title", "xml", );

  // dangerous protocols
  var $BlackProtocols = array("javascript", "vbscript", "about", "wysiwyg", "data", "view-source", "ms-its", "mhtml", "shell", "lynxexec", "lynxcgi", "hcp", "ms-help", "help", "disk", "vnd.ms.radio", "opera", "res", "resource", "chrome", "mocha", "livescript", );

  // pass only these protocols
  var $WhiteProtocols = array("http", "https", "ftp", "telnet", "news", "nntp", "gopher", "mailto", "file", "webcal", );

  // white or black-listing of protocols?
  var $ProtocolFiltering = "white"; //or "black"

  // attributes that can contains protocols
  var $ProtocolAttributes = array("src", "href", "action", "lowsrc", "dynsrc", "background", "codebase", );

  // dangerous CSS keywords
  var $CSS = array("absolute", "fixed", "expression", "moz-binding", "content", "behavior", "include-source", );

  // tags that can have no "closing tag"
  var $noClose = array();

  // paragraph should be closed when this tags opened
  var $closeParagraph = array("p", "div", "h1", "h2", "h3", "h4", "h5", "h6", "ul", "ol", "dl", "dt", 
                          "dd", "blockquote", "address", "pre", "listing", "plaintext", "xmp", "menu", 
                          "dir", "isindex", "hr", "multicol", "center", "marquee", "table", );

  // table tags
  var $tableTags = array("tbody", "thead", "tfoot", "tr", "td", "th", );

  // list tags
  var $listTags = array("ul", "ol", "dir", "menu", );

  // dangerous attributes
  var $Attributes = array("dynsrc", "id", "name", );

  // constructor
  function safehtml() {

    //making regular expressions based on Proto & CSS arrays
    foreach ($this->BlackProtocols as $proto)
    {
     $preg = "/[\s\x01-\x1F]*";
     for ($i=0;$i<strlen($proto);$i++)
       $preg .= $proto{$i}."[\s\x01-\x1F]*";
     $preg .= ":/i";
     $this->Protopreg[] = $preg;
    }

    foreach ($this->CSS as $css)
     $this->csspreg[] = "/".$css."/i";
  }

  // Handles the writing of attributes - called from $this->openHandler()
  function writeAttrs ($attrs) {
    if (is_array($attrs)) {
      foreach ($attrs as $name => $value) {

        $name = strtolower($name);

        if (strpos($name, "on")===0) continue;
        if (strpos($name, "data")===0) continue;
        if (in_array($name, $this->Attributes)) continue;
        if (!preg_match("/^[a-z0-9]+$/i", $name)) continue;

        if ($value === TRUE || is_null($value)) $value = $name;

        if ($name == "style") 
        {
         $value = str_replace("\\", "", $value);
         $value = str_replace("&amp;", "&", $value);
         $value = str_replace("&", "&amp;", $value);
         foreach ($this->csspreg as $css)
         {
          if (preg_match($css, $value)) continue 2;
         }
         foreach ($this->Protopreg as $proto)
         {
          if (preg_match($proto, $value)) continue 2;
         }
        }

        $tempval = preg_replace( '/&#(\d+);/me' , "chr('\\1')" , $value ); //"'

        if (in_array($name, $this->ProtocolAttributes) && strpos($tempval, ":")!==false)
        if ($this->ProtocolFiltering=="black")
         foreach ($this->Protopreg as $proto)
         {
          if (preg_match($proto, $tempval)) continue 2;
         }
        else
        {
         $_tempval = explode(":", $tempval);
         $proto = $_tempval[0];
         if (!in_array($proto, $this->WhiteProtocols)) continue;
        }

        if (strpos($value, "\"")!==false) $q = "'";
        else $q = '"';
        $this->xhtml.=' '.$name.'='.$q.$value.$q;
      }
    }
  }

  // Opening tag handler
  function openHandler(& $parser,$name,$attrs) {

    $name = strtolower($name);

    if (in_array($name, $this->DeleteContent)) 
    {
     array_push($this->dcStack, $name);
     $this->dcCounter[$name]++;
    }
    if (count($this->dcStack)!=0) return true;

    if (in_array($name, $this->Deletes)) return true;
    
    if (!preg_match("/^[a-z0-9]+$/i", $name)) 
    {
      if (preg_match("!(?:\@|://)!i", $name))
        $this->xhtml.="&lt;".$name."&gt;";
      return true;
    }

    if (in_array($name, $this->Singles))
    {
      $this->xhtml.="<".$name;
      $this->writeAttrs($attrs);
      $this->xhtml.=" />";
      return true;
    }

    // TABLES: cannot open table elements when we are not inside table
    if ($this->Counter["table"]<=0 && in_array($name, $this->tableTags)) return true;

    // PARAGRAPHS: close paragraph when closeParagraph tags opening
    if (in_array($name, $this->closeParagraph) && in_array("p", $this->Stack))
    {
      $this->closeHandler($parser, "p");
    }

    // LISTS: we should close <li> if <li> of the same level opening
    if ($name=="li" && count($this->liStack) && $this->listScope==$this->liStack[count($this->liStack)-1])
    {
      $this->closeHandler($parser, "li");
    }

    // LISTS: we want to know on what nesting level of lists we are
    if (in_array($name, $this->listTags)) $this->listScope++;
    if ($name=="li") array_push($this->liStack, $this->listScope);
        
    $this->xhtml.="<".$name;
    $this->writeAttrs($attrs);
    $this->xhtml.=">";
    array_push($this->Stack,$name);
    $this->Counter[$name]++;
  }

  // Closing tag handler
  function closeHandler(& $parser,$name) {

    $name = strtolower($name);

    if ($this->dcCounter[$name]>0 && in_array($name, $this->DeleteContent))
    {
     while ($name!=($tag=array_pop($this->dcStack)))
     {
      $this->dcCounter[$tag]--;
     }
    $this->dcCounter[$name]--;
    }

    if (count($this->dcStack)!=0) return true;

    if ($this->Counter[$name]>0)
    {
     while ($name!=($tag=array_pop($this->Stack)))
       $this->closeTag($tag);

     $this->closeTag($name);
    }
  }

  // Close tag 
  function closeTag($tag) {
    if (!in_array($tag, $this->noClose))
      $this->xhtml.="</".$tag.">";

    $this->Counter[$tag]--;
    if (in_array($tag, $this->listTags)) $this->listScope--;
    if ($tag=="li") array_pop($this->liStack);
  }

  // Character data handler
  function dataHandler(& $parser,$data) {
    if (count($this->dcStack)==0)
      $this->xhtml.=$data;
  }

  // Escape handler
  function escapeHandler(& $parser,$data) {
  }

  // Return the XHTML document
  function getXHTML () {
    while ($tag=array_pop($this->Stack))
      $this->closeTag($tag);
    
    return $this->xhtml;
  }

  function clear() {
   $this->xhtml = "";
  }

  function parse($doc) {

   // Save all "<" symbols
   $doc = preg_replace("/<(?=[^a-zA-Z\/\!\?\%])/", "&lt;", $doc);

   // Opera6 bug workaround
   $doc = str_replace("\xC0\xBC", "&lt;", $doc);

   // Instantiate the parser
   $parser= new XML_HTMLSax();

   // Register the handler with the parser
   $parser->set_object($this);

   // Set the handlers
   $parser->set_element_handler('openHandler','closeHandler');
   $parser->set_data_handler('dataHandler');

   // $parser->set_pi_handler('escapeHandler');
   $parser->set_escape_handler('escapeHandler');

   $parser->parse($doc);

   return $this->getXHTML();

  }

}

?>