<?php

define("FORM_TYPE_NAME",        1);
define("FORM_TYPE_ACTION",      2);
define("FORM_TYPE_ID",      8);
define("FORM_TYPE_REGEX",       32);

define("FORM_SELECT_VALUE",     1);
define("FORM_SELECT_OPTION",    2);

define("POST_TYPE_SIMPLE",      1);
define("POST_TYPE_MULTIPART",   2);
define("POST_TYPE_JSON",   3);


define("VIBROWSER_DEFAULT_TIMEOUT",     120);
define("VIBROWSER_CONNECT_TIMEOUT",     120);
define("VIBROWSER_KEEP_ALIVE",          300);
define("VIBROWSER_MAX_REDIRECT_COUNT",  10);

define("PROXY_TYPE_HTTP", CURLPROXY_HTTP);
define("PROXY_TYPE_SOCKS", CURLPROXY_SOCKS5);

/*
 * decodes gzip-encoded content
 *
 * if content isn't encoded, returns it unchanged.
 *
 */


    function gzdecode2($data)
    {
        // Check if data is GZIP'ed
        if (strlen($data) < 18 || strcmp(substr($data,0,2),"\x1f\x8b"))
        {
            return $data;
        }

        // Remove first 10 bytes
        $data = substr($data, 10);

        // Return regular data
        return @gzinflate($data);
    }


/*
 * ViBrowser class
 */

class ViBrowser
{
    var $session;
    var $debug_callback;
    var $last_error;

    var $cache_key;
    var $auto_follow;

    protected $isAJAX = false;

    function __construct()
    {
        $this->session = NULL;
        $this->Reset();
    }

    /**
     * resets browser state.
     */
    function Reset()
    {
        global $db;

        if($this->session)
            $this->session->Close();

        $this->session = new HTTP_Session();
        $this->setConnectTimeout(VIBROWSER_CONNECT_TIMEOUT);
        $this->debug_callback = false;
        $this->last_error = "";
        $this->auto_follow = true;
    }

    function bodyToFile($filename)
    {
        $f = @fopen($filename, "w");
        if($f)
        {
            fwrite($f, $this->getBody());
            fclose($f);
        }
    }

    function lastError()
    {
        return $this->last_error;
    }

    function setConnectTimeout($t)
    {
        $this->session->setConnectTimeout($t);
    }


    function setDebugCallback($func_name)
    {
        $this->debug_callback = $func_name;
        $this->session->setDebugCallback($func_name);
    }

    function setAutoFollow($af = true)
    {
        $this->debug("--------------\nautofolow set to ".($af?'true':'false')."\n--------------");
        $this->auto_follow = $af;
    }

    function debug($str)
    {
        if($this->debug_callback && function_exists($this->debug_callback))
            eval("$this->debug_callback(\$str);");
    }

    /**
     * sets a proxy, format is "host:port"
     */
    function setProxy($proxy, $port=null)
    {
        if($port) $proxy = "$proxy:$port";
        $this->session->SetProxy($proxy);
    }

    function setProxyType($pt)
    {
        $this->session->setProxyType($pt);
    }

    function getProxy()
    {
        return $this->session->getProxy();
    }

    function getProxyType()
    {
        return $this->session->getProxyType();
    }

    function setTimeOut($seconds)
    {
        $this->session->SetTimeout($seconds);
    }

    function setUserAgent($user_agent)
    {
        $this->session->SetUserAgent($user_agent);
    }

    function getURL()
    {
        return $this->session->GetURL();
    }

    /**
     * ViBrowser::getBody()
     *
     * @return string full content
     **/
    function getBody()
    {
        return $this->session->getBody();
    }

    function getHeaders()
    {
        return $this->session->getHeaders();
    }

    function getRedirect()
    {
        return $this->session->getRedirect();
    }

    static function fix_link($link, $url)
    {
        return HTTP_Session::fix_link($link, $url);
    }

    public function setAJAXMode($isAJAX = true)
    {
        $this->session->setAJAXMode($isAJAX);
    }

    public function setAdditionalHeaders($headers)
    {
        $this->session->setAdditionalHeaders($headers);
    }

    function Get($url)
    {
        $redirect_count = 0;
        while($redirect_count < VIBROWSER_MAX_REDIRECT_COUNT)
        {
            $this->session->Get($url);
            if(!$this->auto_follow) break;
            if(!($r = $this->session->getRedirect())) break;
            $this->debug("Following a redirect: $r");
            $url = $r;
            $redirect_count++;
        }

        if($redirect_count == VIBROWSER_MAX_REDIRECT_COUNT)
        {
            $this->last_error = "Max redirect count reached";
            return "";
        }

        $this->last_error = $this->session->lastError();
        return $this->session->getBody();
    }

    function getFile($url, $body="", $headers="")
    {
        $res = $this->session->getFile($url, $body, $headers);
        $this->last_error = $this->session->lastError();
        return $res;
    }

    function getURLToFile($url, $filename)
    {
        $res = $this->session->getURLToFile($url, $filename);
        $this->last_error = $this->session->lastError();
        return $res;
    }

    function Post($url, $data, $type=POST_TYPE_SIMPLE)
    {
        $this->session->Post($url, $data, $type);

        $redirect_count = 0;
        if($this->auto_follow) do
        {
            $url = $this->session->getRedirect();
            if(!$url) break;
            $this->debug("Following a redirect: $url");
            $this->session->Get($url);
        }
        while($redirect_count < VIBROWSER_MAX_REDIRECT_COUNT);

        if($redirect_count == VIBROWSER_MAX_REDIRECT_COUNT)
        {
            $this->last_error = "Max redirect count reached";
            return "";
        }

        $this->last_error = $this->session->lastError();
        return $this->session->getBody();
    }

    function setUseCompression($value = true)
    {
        $this->session->setUseCompression($value);
    }

    /**
     * to be obsolete soon
     */
    function getForm($url, $form_param, $form_type=FORM_TYPE_NAME)
    {
        $form = array("action" => "", "fields" => array());

        if($url)
        {
            $body = $this->Get($url);
        }
        else
        {
            $url = $this->session->GetURL();
            $body = $this->session->getBody();
        }


        /*
         * fetching entire form body
         */

        if($form_type & FORM_TYPE_REGEX)
        {
            $regex = $form_param;
        }
        else
        {
            $regex = str_replace('/', '\/', preg_quote($form_param));
        }

        if($form_type & FORM_TYPE_ACTION)
        {
            $regex = '/<form[^>]+action=["\']?'.$regex.'["\']?/is';
        }
        elseif($form_type & FORM_TYPE_NAME)
        {
            $regex = '/<form[^>]+name=["\']?'.$regex.'["\' >]/is';
        }elseif($form_type & FORM_TYPE_ID)
        {
            $regex = '/<form[^>]+id=["\']?'.$regex.'["\' >]/is';
        }elseif($form_type & FORM_TYPE_REGEX){
            $regex = '/'.$regex.'/is';
        }else
        {
            $this->debug("Error: Invalid form type: $form_type");
            return false;
        }

        if(!preg_match($regex, $body, $r, PREG_OFFSET_CAPTURE))
        {
            $this->debug("Error: Form not found");
            return false;
        }

        $offset = $r[0][1];

        if(!strpos($body, '/form')) $body .= '</form>';

        if(!preg_match("/<\/form>/i", $body, $r, PREG_OFFSET_CAPTURE, $offset))
        {
            $this->debug("Error: Form doesn't have a closing tag");
            return false;
        }

        $offset2 = $r[0][1];

        $form["data"] = substr($body, $offset, $offset2-$offset+strlen("</form>"));

        /*
         * parsing form action
         */
        if(preg_match('/<form[^>]+action=["\']([^"\' ]+)["\']/is', $form["data"], $r))
        {
            $action = $r[1];
        }
        elseif(preg_match('/<form[^>]+action=([^ ]+)/is', $form["data"], $r))
        {
            $action = $r[1];
        }
        else
        {
            $action = $url;
        }

        /*
         * validate action
         */
        $form["action"] = $this->fix_link($action, $url);

        /*
         * parsing form fields
         */

        // inputs
        preg_match_all('/<input[^>]+>/i', $form["data"], $inputs);
        foreach($inputs[0] as $str)
        {
            $type = "edit";
            $name = "";
            $value = "";
            if(preg_match('/type="([^"]+)"/i', $str, $r))
                $type = $r[1];
            elseif(preg_match('/type=\'([^\']+)\'/i', $str, $r))
            $type = $r[1];
            elseif(preg_match('/type=([^ >]+)/', $str, $r))
            $type = $r[1];

            if(preg_match('/name="([^"]+)"/i', $str, $r))
                $name = $r[1];
            elseif(preg_match('/name=\'([^\']+)\'/i', $str, $r))
            $name = $r[1];
            elseif(preg_match('/name=([^ >]+)/', $str, $r))
            $name = $r[1];

            if(preg_match('/value="([^"]*)"/i', $str, $r))
                $value = $r[1];
            elseif(preg_match('/value=\'([^\']*)\'/i', $str, $r))
            $value = $r[1];
            elseif(preg_match('/value=([^ >]*)/', $str, $r))
            $value = $r[1];

            if($type == "radio")
            {
                if(!is_array(@$form["fields"][$name]["value"]))
                    $form["fields"][$name]["value"] = array();
                $form["fields"][$name]["type"] = $type;
                $form["fields"][$name]["value"][] = $value;
                if(preg_match('/ checked/', $str))
                    $form["fields"][$name]["default"] = $value;
            }
            else
            {
                $form["fields"][$name] = array("type" => $type,
                                               "value" => $value);
            }

            if($type == "checkbox" && preg_match('/ checked/', $str))
            {
                if(isset($form["fields"][$name]["value"]))
                    $form["fields"][$name]["default"] = $form["fields"][$name]["value"];
                else
                    $form["fields"][$name]["default"] = "on";
            }

            if($type == "checkbox" && !preg_match('/ checked/', $str))
            {
                $form["fields"][$name]["oncheck"] = $form["fields"][$name]["value"];
                $form["fields"][$name]["value"] = "";
            }


        }

        // selects
        preg_match_all('/<select[^>]+name="([^"]+)"[^>]*>/i', $form["data"],
                       $selects, PREG_OFFSET_CAPTURE);
        foreach($selects[0] as $index => $select)
        {
            $offset = $select[1];
            if(!preg_match_all('/<\/select>/i', $form["data"], $r,
                               PREG_OFFSET_CAPTURE, $offset))
                break;
            $offset2 = $r[0][0][1];
            $select = substr($form["data"], $offset,
                             $offset2-$offset+strlen("</select>"));
            $name = $selects[1][$index][0];
            $form["fields"][$name]["type"] = "select";
            $form["fields"][$name]["value"] = "";
            if(!preg_match_all('/<option[^>]+value=["\']?([^">]*)["\']?[^>]*>([^<]*)/i',
                               $select, $r))
                continue;
            $form["fields"][$name]["value"] = $r[1];
            $form["fields"][$name]["options"] = $r[2];

            foreach($r[0] as $index2 => $option)
            {
                if(preg_match('/ selected/', $option))
                    $form["fields"][$name]["default"] = $r[1][$index2];
            }
        }

        // text areas
        preg_match_all('/<textarea[^>]+name="([^"]+)"[^>]*>/i', $form["data"],
                       $textareas, PREG_OFFSET_CAPTURE);
        foreach($textareas[0] as $index => $textarea)
        {
            $offset = $textarea[1];
            if(!preg_match_all('/<\/textarea>/i', $form["data"], $r,
                               PREG_OFFSET_CAPTURE, $offset))
                break;
            $offset2 = $r[0][0][1];
            $textarea = substr($form["data"],
                               $offset + strlen($textarea[0]),
                               $offset2-$offset-strlen($textarea[0]));
            $name = $textareas[1][$index][0];
            $form["fields"][$name]["type"] = "textarea";
            $form["fields"][$name]["value"] = $textarea;
        }

        return $form;
    }

    function FillFormField(&$form, $field, $value, $select_type=FORM_SELECT_OPTION)
    {
        if( $form["fields"][$field]["type"] == "select" &&
                $select_type == FORM_SELECT_OPTION)
        {
            $values = $form["fields"][$field]["value"];
            $options = $form["fields"][$field]["options"];
            $form["fields"][$field]["value"] = "";
            foreach($options as $i => $option)
            {
                if($value == $option)
                {
                    $form["fields"][$field]["value"] = $values[$i];
                    break;
                }
            }
        }
        else
        {
            $form["fields"][$field]["value"] = $value;
        }

    }

    function PostForm($form, $method="post", $type=POST_TYPE_SIMPLE)
    {
        $data = array();
        foreach($form["fields"] as $name => $field)
        {
            /*if( $field["type"] == "button" ||
                    $field["type"] == "submit" ||
                    $field["type"] == "image")
                continue;*/
            
            //if(!isset($field['value'])) continue;

            if(is_array($field["value"]))
            {
                if(isset($field["default"]))
                {
                    $value = $field["default"];
                }
                else
                {
                    $value = $field["value"][0];
                }
            }
            else
            {
                $value = $field["value"];
            }

            if($field["type"] == "file")
                $value = "@".$value;

            $data[$name] = $value;
        }

        if($method == "post")
        {
            $this->Post($form["action"], $data, $type);
        }
        else
        {
            $query = "";
            foreach($data as $var => $value)
            {
                if(!$query)
                    $query .= "?";
                else
                    $query .= "&";
                $query .= "$var=".urlencode($value);
            }
            $this->Get($form["action"].$query);
        }
        return $this->session->getBody();
    }

    function setURL($url)
    {
        $this->session->setURL($url);
    }

    function GetLinksByTitle($title, $nocase=true)
    {
        $res = array();
        $regex = str_replace('/', '\/', preg_quote($title));
        $regex = '/<a([^>]+)href="([^"]+)"([^>]*)>\s*'.$regex.'\s*<\/a>/s'.($nocase?"i":"");
        if(preg_match_all($regex, $this->session->getBody(), $r))
        {
            foreach($r[2] as $link)
            {
                $res[] = $this->fix_link($link, $this->session->GetUrl());
            }
        }
        return array_unique($res);
    }

    /*
     * returns an array of links to arbitrary files/scripts
     * if $only_ext is set, only files/scripts with that extension returned.
     * $only_ext may contain several extensions, e.g. "jpg|zip|gif"
     */
    function GetAllLinksWithContent($only_ext = "")
    {
        $links = array();
        if($only_ext)
            $only_ext = explode("|", $only_ext);
        if(preg_match_all("/<a[^>]+href=['\"]([^'\"]+)['\"][^>]*>(.*)<\/a>/Uis",
                          $this->session->getBody(),
                          $matches))
        {
            foreach($matches[1] as $n => $href)
            {
                // check extension
                $href = $this->fix_link($href, $this->session->GetUrl());
                if($only_ext)
                {
                    $p_i = pathinfo($href);
                    if(!in_array($p_i['extension'], $only_ext))
                        continue;
                }

                if(@$links[$href])
                    $links[$href].= $matches[2][$n];
                else
                    $links[$href] = $matches[2][$n];

            }
        }
        return $links;
    }

    /*
     * returns all links in document body which match
     * the given regex. regex is case-insensitive if $nocase is true
     */
    function GetLinksByRegex($regex, $nocase = true)
    {
        $res = array();
        $regex = '/<a([^>]+)href="([^"]*)'.$regex.'([^"]*)"([^>]*)>/'.($nocase?"i":"");
        if(preg_match_all($regex, $this->session->getBody(), $r))
        {
            foreach($r[0] as $val)
            {
                preg_match('/href="([^"]+)"/', $val, $r2);
                $res[] = $this->fix_link($r2[1], $this->session->GetUrl());
            }
        }
        return array_unique($res);
    }

    function exportCookies()
    {
        return $this->session->exportCookies();
    }

    function importCookies($str)
    {
        $this->session->importCookies($str);
    }

    function Close()
    {
        $this->session->Close();
    }
}

class HTTP_Session
{
    var $id;
    var $cookies;
    var $url;
    var $domain;
    var $proxy;
    var $proxy_type;
    var $redirect;
    var $headers;
    var $body;
    var $user_agent;
    var $timeout;
    var $connect_timeout;
    var $keep_alive;
    var $use_compression;
    var $debug_callback;
    var $last_error;
    var $server_time;
    protected $addHeaders = array();

    protected $isAJAX = false;

    function HTTP_Session()
    {
        $this->id = md5(uniqid(mt_rand(0, 1000)).microtime());
        $this->url = "";
        $this->domain = "";
        $this->proxy = "";
        $this->proxy_type = PROXY_TYPE_HTTP;
        $this->timeout = VIBROWSER_DEFAULT_TIMEOUT;
        $this->cookies = array();

        $this->keep_alive = VIBROWSER_KEEP_ALIVE;
        $this->use_compression = true;
        $this->debug_callback = false;
        $this->last_error = "";
        $this->server_time = array();
    }

    public function setAdditionalHeaders($headers)
    {
        $this->addHeaders = $headers;
    }

    function setDebugCallback($func_name)
    {
        $this->debug_callback = $func_name;
    }

    function serverTime($domain=null)
    {
	if(!$domain) $domain = $this->domain;
	if(!isset($this->server_time[$domain])) return time();
	return $this->server_time[$domain];
    }

    function setURL($url)
    {
        $this->url = $url;
    }

    function debug($str)
    {
        if($this->debug_callback && function_exists($this->debug_callback))
            eval("$this->debug_callback(\$str);");
    }

    function setConnectTimeout($t)
    {
        $this->connect_timeout = $t;
    }

    function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }

    function setProxyType($pt)
    {
        $this->proxy_type = $pt;
    }

    function getProxy()
    {
        return $this->proxy;
    }

    function getProxyType()
    {
        return $this->proxy_type;
    }

    function setUserAgent($user_agent)
    {
        $this->user_agent = $user_agent;
    }

    function getBody()
    {
        return $this->body;
    }

    function getHeaders()
    {
        return $this->headers;
    }

    function getRedirect()
    {
        return $this->redirect;
    }

    function setTimeout($seconds)
    {
        $this->timeout = $seconds;
    }

    function getUrl()
    {
        return $this->url;
    }

    function cookieString($domain = "")
    {
        $res = array();
        if(!$domain)
            $domain = $this->domain;

        $this->debug("\ngetting cookie string for domain $domain\n".
            $this->ExportCookies());

        foreach($this->cookies as $domain => $cookies)
        {
            if(!_vibrowser_tailmatch($this->domain, $domain))
            {
                $this->debug("!!! skipped: domain tailmatch: $this->domain, $domain");
                continue;
            }

            foreach($cookies as $name => $cookie)
            {
                if( is_array($old_cookie = @$res[$name]) &&
                    ($old_cookie['expires'] && $cookie['expires'] < $old_cookie['expires'] ||
                    (!$old_cookie['expires'] && $cookie['expires'] < $this->serverTime())) )
                {
                    $this->debug("!!! skipped: found newer value $name ".$cookie['expires']);
                    continue;
                }
                else
                {
		            $this->debug("!!! replaced $name=".$old_cookie['value']." expires ".(int)$old_cookie['expires'].
			        " with ".$cookie['value']." expires ".(int)$cookie['expires']);
		        }
                $this->debug("!!! added cookie: $name=".$cookie['value']);
                $res[$name] = $cookie;
            }
        }

        $res_str = "";
        foreach($res as $name => $cookie)
        {
            if($res_str)
                $res_str .= "; ";
            $res_str .= "$name=".$cookie['value'];
        }
        return $res_str;
    }

    /*
     * Imports session cookies from a netscape cookie file format string
     */
    function importCookies($str)
    {
        $str = explode("\n", $str);
        foreach($str as $cookie)
        {
            $cookie = trim($cookie);
            if(!$cookie)
                continue;
            if(preg_match('/^\s*#/', $cookie))
                continue;
            @list($domain, $bool, $path, $secure, $expires, $name,
                 $value) = @explode("\t", $cookie);
            $this->setSessionCookie($name, $value, $expires, $path, $domain, $secure);
        }
    }

    /*
     * Exports session cookies to a netscape cookie file format string
     */
    function exportCookies()
    {
        $res = "";

        foreach($this->cookies as $domain => $cookies)
        {
            foreach($cookies as $name => $cookie)
            {
                if(preg_match('/^\./', $domain))
                    $bool = "TRUE";
                else
                    $bool = "FALSE";
                $secure = $cookie['secure']?"TRUE":"FALSE";
                $res .= implode("\t", array($domain, $bool, $cookie['path'],
                                            $secure, $cookie['expires'], $name, $cookie['value']))."\r\n";
            }
        }
        return $res;
    }

    function setUseCompression($value = true)
    {
        $this->use_compression = $value;
    }

    /**
        Устанавливает режим запросов X-Requested-With:XMLHttpRequest
    **/
    public function setAJAXMode($isAJAX = true)
    {
        $this->isAJAX = (bool)$isAJAX;
    }

    /*
     * helper function for Get() and GetFile()
     */

    function do_get($url, &$headers, &$body)
    {
        global $headers, $body, $body_started, $headers_started;

        $this->last_error = "";

        $this->debug("---------------------------------------\n".
                     "GET $url");

        $ch = curl_init();
        $u = parse_url($url);
        $host = $u['host'];
        $this->domain = $host;
        $req_headers = array("Host: $host");
        if($this->user_agent)
            $req_headers[] = "User-Agent: $this->user_agent";
        $req_headers[] = "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,image/jpeg,*/*;q=0.5";
        $req_headers[] = "Accept-Language: en-us,en;q=0.5";
        if($this->use_compression)
            $req_headers[] = "Accept-Encoding: gzip,deflate";
        $req_headers[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
        $req_headers[] = "Keep-Alive: $this->keep_alive";

        $cookie_string = $this->CookieString();
        if($cookie_string)
            $req_headers[] = "Cookie: $cookie_string";

        $req_headers[] = "Connection: keep-alive";
        if($this->url)
            $req_headers[] = "Referer: $this->url";

        if($this->isAJAX)
            $req_headers[] = "X-Requested-With: XMLHttpRequest";

        if($this->debug_callback)
        {
            foreach($req_headers as $str) $this->debug($str);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $req_headers);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, '_vibrowser_read_header');
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, '_vibrowser_read_body');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if($this->proxy)
        {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
            if($this->proxy_type == PROXY_TYPE_SOCKS)
            {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }
            else
            {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            }
        }

        $body_started = false;
        $headers_started = false;
        $headers = "";
        $body = "";
        $this->redirect = "";

        curl_exec($ch);
        $body = gzdecode2($body);
        $this->debug("\n$headers");
        if(curl_errno($ch))
        {
            $this->debug("Error: ".curl_error($ch));
            $this->last_error = curl_error($ch);
        }

        curl_close ($ch);

        if(preg_match("/[\r\n]location: ([^\n]+)/i", $headers, $r))
        {
            $this->redirect = $this->fix_link(trim($r[1]), $url);
        }

	if(preg_match("/[\r\n]Date: ([^\n]+)/i", $headers, $r))
	{
	    $server_time = strtotime($r[1]);
	    $this->debug("Server time: ".gmdate("H:i:s D M d Y", $server_time));
	    $this->server_time[$this->domain] = $server_time;
	}
    else
    {
        $this->server_time[$this->domain] = time();
    }

        $this->CheckHeadersForCookies($headers);

    }

    function Get($url)
    {
        global $headers, $body, $body_started, $headers_started;

        $this->do_get($url, $body, $headers);

        $this->headers = $headers;
        $this->body = $body;
        $this->url = $url;

        return $this->body;
    }

    /*
     * acts just like Get(), but does not alter $this->headers, $this->body and $this->url
     *
     * needed to retrieve images from the web page, not altering referer and so on
     */
    function GetFile($url, $body="", $headers="")
    {
        global $headers, $body, $body_started, $headers_started;
        $this->do_get($url, $body, $headers);
        return $body;
    }

    function GetURLToFile($url, $filename)
    {
        global $headers, $body, $body_started, $headers_started;
        $this->do_get($url, $body, $headers);
        $f = @fopen($filename, "w");
        if($f)
        {
            fwrite($f, $body);
            fclose($f);
        }
        return $body;
    }

    function lastError()
    {
        return $this->last_error;
    }

    function checkHeadersForCookies($headers)
    {
        if(preg_match_all("/set-cookie:\s+([^=\r\n]+)\s*=\s*([^;\r\n]*);? *([^\r\n]*)/i", $headers, $r))
        {
            foreach($r[0] as $i => $match)
            {
                $name = rtrim($r[1][$i]);
                $value = ltrim($r[2][$i]);
                $path = "/";
                $domain = "";
                $path = "/";
                $expires = 0;
                $secure = false;
                $rest = trim($r[3][$i]);
                if(preg_match('/path=([^;]+)/i', $rest, $r2))
                    $path = $r2[1];
                if(preg_match('/domain=([^;]+)/i', $rest, $r2))
                    $domain = $r2[1];
                if(preg_match('/expires=([^;]+)/i', $rest, $r2))
                    $expires = strtotime($r2[1]);
                if(preg_match('/; secure$/i', $rest, $r2))
                    $secure = true;

                // adding cookie
                $this->setSessionCookie($name, $value, $expires, $path, $domain, $secure);
            }
        }

    }

    function setSessionCookie($name, $value="", $expires=0, $path="", $domain="", $secure=false)
    {
        if(!$domain)
            $domain = $this->domain;
        if(!isset($this->cookies[$domain]))
            $this->cookies[$domain] = array();
        $cookie = array("value" => $value,
                        "path" => $path,
                        "expires" => $expires,
                        "secure" => $secure);
        $old_cookie = @$this->cookies[$domain][$name];

        if($name == 'MYUSERINFO' && !$value)
        {
            $this->debug("+++ myuserinfo skipped");
            return;
        }
        if(is_array($old_cookie) &&
            $old_cookie['expires'] > $cookie['expires'] && $cookie['expires'])
        {
            $this->debug("!!! cookie skipped: $name=$value");
            return;
        }
        $this->cookies[$domain][$name] = $cookie;
    }

    /*
     * makes a POST request to the server using script $url and associative
     * array $data. POST is made in a simple form a=...&b=... if
     * $type==POST_TYPE_SIMPLE and in multipart-form-data if $type=POST_TYPE_MULTIPART
     *
     */

    function Post($url, $data, $type=POST_TYPE_SIMPLE)
    {
        global $headers, $body, $body_started;

        $this->last_error = "";

        $this->debug("---------------------------------------\n".
                     "POST: $url");

        $ch = curl_init();
        $u = parse_url($url);
        $host = $u['host'];
        $this->domain = $host;
        $req_headers = array("Host: $host", 'Origin: http://'.$host);
        if($this->user_agent)
            $req_headers[] = "User-Agent: $this->user_agent";
//        $req_headers[] = "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
        $req_headers[] = "Accept: */*";
        $req_headers[] = "Accept-Language: ru-ru,ru;q=0.8,en-us;q=0.5,en;q=0.3";
        if($this->use_compression)
            $req_headers[] = "Accept-Encoding: gzip,deflate,sdch";
        // $req_headers[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
        // $req_headers[] = "Keep-Alive: $this->keep_alive";

        $req_headers[] = "Connection: keep-alive";
        if($this->url)
            $req_headers[] = "Referer: $this->url";

        $cookie_string = $this->CookieString();
        if($cookie_string)
            $req_headers[] = "Cookie: $cookie_string";

        if($this->isAJAX)
            $req_headers[] = "X-Requested-With: XMLHttpRequest";        

        if(is_array($this->addHeaders) && !empty($this->addHeaders))
            foreach ($this->addHeaders as $hdr) {
                $req_headers[] = $hdr;
            }


        if(is_array($data) && ($type == POST_TYPE_SIMPLE || $type == POST_TYPE_JSON))
        {

            //$req_headers[] = 'Expect:';
            
            $post_data = "";
            foreach($data as $var => $value)
            {
                if($post_data)
                    $post_data .= "&";
                if(is_array($value)){
                    foreach($value as $v){
                        $post_data .= urlencode($var).'='.urlencode($v).'&';
                    }
                    $post_data = rtrim($post_data, '&');
                }else
                    $post_data .= urlencode($var)."=".urlencode($value);
            }
            
            $post_data = http_build_query($data);
            //echo "'$post_data'";
            if($type == POST_TYPE_JSON){
                $req_headers[] = "Content-Type: application/json; charset=utf-8";
               
            }else{
                $req_headers[] = "Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
            }
            $req_headers[] = "Content-Length: ".strlen($post_data);

        }
        else
        {
            $post_data = $data;
            if(!is_array($data))
            {
                if($type == POST_TYPE_JSON){
                    $req_headers[] = "Content-Type: application/json; charset=utf-8";
                }else{
                    $req_headers[] = "Content-Type: application/x-www-form-urlencoded";
                }
                $req_headers[] = "Content-Length: ".strlen($post_data);
            }
        }

        if($this->debug_callback)
        {
            
            foreach($req_headers as $str) $this->debug($str);
            if(is_array($post_data))
            {
                $this->debug("\tmultipart-form-data:");
                foreach($post_data as $var => $val) $this->debug("$var=$val");
            }
            else
                $this->debug("\t$post_data");
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $req_headers);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, '_vibrowser_read_header');
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, '_vibrowser_read_body');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if($this->proxy)
        {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
            if($this->proxy_type == PROXY_TYPE_SOCKS)
            {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }
            else
            {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            }
        }

        $body_started = false;
        $headers_started = false;
        $headers = "";
        $body = "";
        $this->redirect = "";

        curl_exec($ch);
        if (curl_errno($ch))
        {
            $this->debug("Error: ".curl_error($ch));
            $this->last_error = curl_error($ch);
        }
        $this->headers = $headers;
        $this->debug("\n$headers");
        $this->body = gzdecode2($body);

        curl_close ($ch);

        if(preg_match("/[\r\n]location: ([^\n]+)/i", $this->headers, $r))
        {
            $this->redirect = $this->fix_link(trim($r[1]), $url);
        }

        $this->CheckHeadersForCookies($headers);

        $this->url = $url;
        return $this->body;
    }

    /**
     * transforms relative links to full URL with http|https:// etc
     */

    static function fix_link($link, $url)
    {
        $res = $link;

        if(!preg_match('/^http[s]?:\/\//', $res))
        {
            $url_parts = parse_url($url);
            $new_url= $url_parts['scheme']."://".
                      (@$url_parts['user']?$url_parts['user'].":".$url_parts['pass']."@":"").
                      $url_parts['host'];
            if(substr($res, 0, 1) == "/")
            {
                $new_url .= $res;
            }
            else
            {
		if(substr($url_parts['path'], -1) == '/')
			$url_parts['path'] .= 'index.html';

                $dir = str_replace("\\", "/", dirname($url_parts['path']));
                $dir = rtrim($dir, "/");
                $new_url .= $dir."/".$res;
            }
            $res = $new_url;
        }
        return $res;
    }

    function Close()
    {
    }
}

/*
 * callback functions for header and body
 * simple split on first double \r\n doesn't always work
 * because of double headers like HTTP 100, so we need
 * something more complicated here.
 */
function _vibrowser_read_header($ch, $string)
{
    global $headers, $body_started, $last_header;
    $length = strlen($string);
    if(trim($string) === '')
    {
        $body_started = true;
    }
    if(!$body_started)
    {
        $headers .= $string;
    }
    $last_header = $string;
    return $length;
}

function _vibrowser_read_body($ch, $string)
{
    global $body, $body_started, $last_header;
    $length = strlen($string);
    if(trim($last_header) === '' && preg_match('/^HTTP\/[0-9]+\.[0-9]+/', $string))
    {
        $body_started = false;
        return $length;
    }
    if($body_started)
        $body .= $string;
    return $length;
}

/**
 * tailmatch for domain names
 * tailmatch('test.somedomain.com', '.somedomain.com') returns true
 * tailmatch('test.somedomain.com', 'somedomain.com') returns true
 *
 * BUT!
 *
 * tailmatch('test.somedomain.com', 'omedomain.com') returns false
 */
function _vibrowser_tailmatch($big, $little)
{
    $start = strlen($big) - strlen($little);
    if($start < 0)
    {
        return false;
    }

    if(!$big || !$little){
        return $big == $little;
    }

    $tail = substr($big, $start, strlen($little));
    if($tail != $little)
    {
        return false;
    }

    if(($start != 0) && ($little[0] != '.') && ($big[$start - 1] != '.'))
    {
        return false;
    }

    return true;
}
