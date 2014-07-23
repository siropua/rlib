<?php

/**
* ������ � �������������.
*
* ������ �������������:
*
*<code>
* 	<?
* $path2image = "/tmp/tmp.image"; // ������� �����������
*
* // ������ ������. ������ ���������� ����� �������������� ������ ������������ ������
* $imager = new Imager($path2image, array(
* ERR_CANT_COPY=>"�� ����� ����������� �����������",
* ERR_CANT_CRT_DIR=>"�� ����� ������� ������� ������",
* ERR_CANT_DTR_IMG_SIZE=>"�� ����� ���������� ������� �����������",
* ERR_IMG_NOT_VALID=>"����� ����������"
* ));
*
* $dir = "/www/host/destination_dir/";
* $filename = "��������.jpg"; // ��� ����� ������������� ����������������� � ��������, ���������� ����� ����������� � ����������� � �����
*
* // ��������� ����������� � ���������� ����
* $result = $imager->saveResized($dir.$filename, PHOTO_MAX_WIDTH, PHOTO_MAX_HEIGHT);
* if(!$result['error'])
* {
* // ���� ��� �����������, ����� ������� � ���������.
* // ��������� �������� ���������� ��� ����, ����� ������������ ��� ����������� � ���������������� ������� ��������, ��� ��������
* $thumb_result = $imager->saveResized($dir."thumb_".$filename, THUMB_WIDTH, THUMB_HEIGHT, $result['destination']);
* }else $thumb_result = $result;
*
* // ��������� ��� �� ������
* if(!$result['error'] && !$thumb_result['error'])
* {
*
* // ��� ��! �������� ���������! �� ���� � ������� ����� � $result['destination'];
* // �.�. �������� ��� ����� ������ �������� ����� basename($result['destination'])
* // ����� �������� width, height
* }else
* {
* // ������! Ÿ ����� ����� � $result['error']
* }
* 	?>
*</code>
*
* ������: ����� ������������ ImageMagick, ���������� ������� ��������� IMAGEMAGICK_PATH �� �������� �������
*
* @version $Id: Imager.php,v 1.7 2008/02/20 13:34:26 steel Exp $
* @copyright 2006
* @autor Steel Ice
* @package rLib
*/


require_once('rlib/rURLs.class.php');


define('IMT_NONE', 0);
define('IMT_GIF', 1);
define('IMT_JPG', 2);
define('IMT_PNG', 3);

define('RANDTYPE_MD5', 1);
define('RANDTYPE_UNIQID', 2);

define('FNM_REPLACE', 1);
define('FNM_RENUM', 2);
// ******* ERRORS *************
define('ERR_CANT_DTR_IMG_SIZE', 1);
define('ERR_IMG_NOT_VALID', 2);
define('ERR_CANT_CRT_IMG_STREAM', 3);
define('ERR_IMG_NOT_FOUND', 4);
define('ERR_CANT_CRT_DIR', 5);
define('ERR_CANT_RESIZE', 6);
define('ERR_CANT_OUTPUT', 7);
define('ERR_CANT_COPY', 8);
define('ERR_CANT_OVERWRITE', 9);
// ***** /ERRS *****************
define('DEF_JPG_QUALITY', 85);

/**
* ������ � �������������
*
* @package rLib
* @author Steel Ice
* @copyright Copyright (c) 2007
* @version $Id: Imager.php,v 1.7 2008/02/20 13:34:26 steel Exp $
* @access public
*/
class Imager {

   /** @var string ���� � �������� */
    var $_path = "";

    /** @var int ������������ ������ ��������.
	* 	120kb �� ��������� �������� */
    var $_sizeLimit = 120000; //

	/** @var int �������� �� ������ */
    var $_xLimit = 600;
    /** @var int �������� �� ������ */
    var $_yLimit = 700; //
    /** @var int ��� �������, ��� ��������� ����� �����
    * 	��������� ��������:
    * 	FNM_REPLACE - �������� �����
    * 	FNM_RENUM - ���������������� (dup.jpg => dup1.jpg)
    */
    var $_ifFileExists = FNM_RENUM;
	/** @var int �������� jpg-������ � ���������� */
    var $_jpgQuality = DEF_JPG_QUALITY; // �������� ��������� JPG �� ���������

	/**
	* @var object GD object of imahe
	*/
    var $_gdImg = 0;

    var $_imgW = 0;
    var $_imgH = 0;
    var $_imgFilesize = 0;
    var $_imgType = IMT_NONE;
    var $_fileName = "";

	/** @var string �������� ������ � ��������� ������� */
    var $_lastError = "";
    /** @var int �������� ��� ��������� ������ */
    var $_lastErrorCode = 0;

    var $_extensions = array(IMT_GIF => "gif", IMT_JPG => "jpg", IMT_PNG => "png", IMT_NONE => "");

	/** @var bool If IMAGEMAGICK_PATH constant defined, but u don't want to use it, turn this var to true */
    var $_useIM = true;
    /** @var char Quote for imageMagick command line */
    var $_IMQuote = "'";
    /** Default language. U can change this array to your locales */
    var $lang = array(
        ERR_CANT_DTR_IMG_SIZE => "Can't determine image size",
        ERR_IMG_NOT_VALID => "Image not valid",
        ERR_CANT_CRT_IMG_STREAM => "Can't create image stream",
        ERR_IMG_NOT_FOUND => "Image file not found",
        ERR_CANT_CRT_DIR => "Can't create destination dir",
        ERR_CANT_RESIZE => "Can't resize",
        ERR_CANT_OUTPUT => "Can't write output format",
        ERR_CANT_COPY => "Can't copy to desctination folder",
        ERR_CANT_OVERWRITE => "Can't overwrite destination file"
        );
        
    var $waterText = false;

    /**
    * ������������
    *
    * @param string $path2image ���� � �������� ��������.
    * 							�����, � ��������, �� ��������
    * @param array $lang ������ � ��������� ��������. � �������� �������� ������
    *
    * @access public
    * @return void
    */
    function Imager($path2image = '', $lang = array())
    {
        if (count((array)$this->lang))$this->lang += $lang;
        $this->_useIM = defined('IMAGEMAGICK_PATH');
        if ($this->_useIM && getenv("WINDIR"))
            $this->_IMQuote = '"';
        $this->setImage($path2image);
    }

    /**
    * ������������� ��������� ������������� ImageMagick
    *
    * @access public
    * @return void
    */
    function TurnOffIM()
    {
        $this->_useIM = false;
    }
    
    function setWaterText($text){
        $this->waterText = $text;
    }

    /**
    * �������������� GD-�����
    *
    * @access public
    * @return bool � ������ ������ ��������� FALSE
    */
    function _prepareGD()
    {
        if ($this->_imgType == IMT_NONE) {
            $this->_lastErrorCode = ERR_IMG_NOT_VALID;
            trigger_error($this->_lastError = $this->lang[ERR_IMG_NOT_VALID], E_USER_NOTICE);
            return false;
        }
        $this->_gdImg = 0;
        switch ($this->_imgType) {
            case IMT_GIF:
                @$this->_gdImg = imagecreatefromgif($this->_path);
                break;
            case IMT_JPG:
                $this->_gdImg = imagecreatefromjpeg($this->_path);
                break;
            case IMT_PNG:
                $this->_gdImg = imagecreatefrompng($this->_path);
                break;
        }
        if (!$this->_gdImg) {
            // can't create image stream
            trigger_error($this->_lastError = $this->lang[ERR_CANT_CRT_IMG_STREAM], E_USER_NOTICE);
            return false;
        }
        return true;
    }


    /**
     * ������������� ��������
     *
     * @param string $path ���� � ����� ��������
     * @return bool ���� �������� �� ������� ��� ����� ��������������� ������ ��������� FALSE
     **/
    function setImage($path)
    {
        $this->_lastError = '';
        if (!file_exists($path)) {
            $this->_lastError = $this->lang[ERR_IMG_NOT_FOUND];
            //trigger_error($this->_lastError . " ($path)", E_USER_NOTICE);
            // ������� ����������, ���� ����� �� � ��� ������
            $this->_imgFilesize = $this->_imgH = $this->_imgW = $this->_gdImg = 0;
            $this->_imgType = IMT_NONE;
            return false;
        }
        @$size = getimagesize($path);
        if (!$size) {
            //trigger_error($this->_lastError = $this->lang[ERR_CANT_DTR_IMG_SIZE], E_USER_NOTICE);
            // ������� ����������, ���� ����� �� � ��� ������
            $this->_imgFilesize = $this->_imgH = $this->_imgW = $this->_gdImg = 0;
            $this->_imgType = IMT_NONE;
            return false;
        }
        list($this->_imgW, $this->_imgH, $this->_imgType) = $size;
        $this->_imgFilesize = filesize($path);
        $this->_path = $path;
        return true;
    }


    /**
     * ����������, ����� �� �������� ������
     *
     * @return bool
     **/
    function need2resize($newW = 0, $newH = 0)
    {
        
        if($newW)
            $this->_xLimit = (int)$newW;
        if($newH)
            $this->_yLimit = (int)$newH;
        
        return $this->_imgW > $this->_xLimit || $this->_imgH > $this->_yLimit || $this->_imgFilesize > $this->_sizeLimit;
    }




    /**
     * ��������� ������� �� ����������, ��� ����� ��������� �����������
     *
     * @return array ������� ������������ �������:
     * 					0 - ����� ������ ������
     * 					1 - ����� ������ ������
     * 					2 - ratio: ���������, �� ������� ���������� ������
     **/
    function getResizedDims()
    {
        if ($this->need2resize()) {
            $ratio = $this->_imgW > $this->_xLimit ?
            ($this->_xLimit / $this->_imgW) : 1;
            $new_w = (int)($this->_imgW * $ratio);
            $new_h = (int)($this->_imgH * $ratio);
            // echo($new_w.'x'.$new_h.'='.$this->_imgW.'x'.$this->_imgH);
            if ($new_h > $this->_yLimit) {
                $ratio = $this->_yLimit / $this->_imgH;
                $new_w = (int)($this->_imgW * $ratio);
                $new_h = (int)($this->_imgH * $ratio);
            }
            return array($new_w, $new_h, $ratio);
        } else {
            return array($this->_imgW, $this->_imgH, 1);
        }
    }

    /**
    *	�������� ������ �� ����������, ��� ����� �������� �����������
    *
    * @access private
    * @return array  ���������� ������, ��� ����� �������� �����������.
    * 	 ������� �������:
    * 		0 - �������� �� � � ���������
    * 		1 - �������� �� Y � ���������
    * 		2 - ������ � ���������
    * 		3 - ������ � ���������
    * 		4 - �������������� ������
    * 		5 - �������������� ������
    * 		������ ������ � ������  �������� ����� ����� $_xLimit � $_yLimit ��������������.

    */
    function getCroppedDims()
    {
        // ��������� �������� ��������
        if (!$this->need2resize())
            return array(0, 0, $this->_imgW, $this->_imgH, $this->_imgW, $this->_imgH);
        // ���� �����. �����
        $mul = min($this->_imgW / $this->_xLimit, $this->_imgH / $this->_yLimit);
        $crop_w = (int)ceil($this->_xLimit * $mul);
        $crop_h = (int)ceil($this->_yLimit * $mul);
        $shift_x = floor(($this->_imgW - $crop_w) / 2);
        $shift_y = floor(($this->_imgH - $crop_h) / 2);
        return array($shift_x, $shift_y, $crop_w, $crop_h, $this->_xLimit, $this->_yLimit);
    }

    function getCroppedDimsIM()
    {
        // ��������� �������� ��������
        if (!$this->need2resize())
            return array(0, 0, $this->_imgW, $this->_imgH, $this->_imgW, $this->_imgH);
        // ���� �����. �����
        $ratio = ($this->_imgW > $this->_imgH)? $this->_yLimit / $this->_imgH : $this->_xLimit / $this->_imgW;
        $crop_h = (int)($this->_imgH * $ratio);
        $crop_w = (int)($this->_imgW * $ratio);
        $shift_x = (int)(($crop_w - $this->_xLimit) / 2);
        $shift_y = (int)(($crop_h - $this->_yLimit) / 2);

        return array($shift_x, $shift_y, $crop_w, $crop_h, $this->_xLimit, $this->_yLimit);
    }

    /**
    * ���������  ��������������� (�� �������������) ��������
    *
    * @access public
    * @return array
    */
    function saveResized($destination, $width = 0, $height = 0, $source = "", $quality = DEF_JPG_QUALITY, $forceResample = false)
    {
        if ($width || $height)
            $this->setLimits($width, $height);
        if ($source)
            if (!$this->setImage($source)) {
                return array("error" => $this->_lastError);
            }

            if ($this->_lastError)
                return array("error" => $this->_lastError);

            if (!$destination = $this->prepareDestination($destination))
                return array('error' => $this->_lastError);

            if ($this->need2resize() || $forceResample) {
                if (!$this->_useIM)if (!$this->_prepareGD()) {
                        return array("error" => $this->_lastError);
                    }
                    list($width, $height) = $this->getResizedDims();
                    if ($this->_useIM) {
                        
                        $command = IMAGEMAGICK_PATH . "convert " . " -" . ($width * $height < 100000?"thumbnail":"resize") . " " . $width . "x" . $height .
                            ($quality != DEF_JPG_QUALITY?" -quality $quality":"") . " " . $this->_path;
                        $ext = $this->getExt();
                        if($ext && $ext == 'gif' && substr($destination, -3) != 'gif')
                            $command .= '[0]';

                        if($this->waterText)
                            $command .= 
                        
                        " -gravity SouthEast  -stroke ".$this->_IMQuote."#000c".$this->_IMQuote." -strokewidth 2 -annotate +10+5 ".$this->_IMQuote.$this->waterText.$this->_IMQuote." -stroke  none   -fill ".$this->_IMQuote."#FFE76E".$this->_IMQuote."    -annotate +10+5 ".$this->_IMQuote.$this->waterText.$this->_IMQuote;
                        
                        $command .= ' -auto-orient '. $destination;                        
                        
                        $reply = system($command);
                        if (!file_exists($destination)) {
                            return array("error" => $this->lang[ERR_CANT_OUTPUT] . " IMAGEMAGICK: $reply");
                        }
                    } else {
                        $dest = imagecreatetruecolor($width, $height);
                        if ($this->_gdImg && imagecopyresampled($dest, $this->_gdImg, 0, 0, 0, 0, $width, $height, $this->_imgW, $this->_imgH)) {
                            // ��������� GD-����� � �������������� ����
                            
                            // ��������� ������������ �����, ���� ����������:
                            
                            if($this->waterText){
                                $textcolor = imagecolorallocate($dest, 243, 202, 1);
                                imagestring($dest, 2, $width - (strlen($this->waterText) * 6) - 7, $height - 18, $this->waterText, $textcolor);
                            }
                            
                            
                            $res = false;
                            switch ($this->_imgType) {
                                case IMT_GIF:
                                    $res = imagegif($dest, $destination);
                                    break;
                                case IMT_JPG:
                                    $res = imagejpeg($dest, $destination, $this->_jpgQuality);
                                    break;
                                case IMT_PNG:
                                    $res = imagepng($dest, $destination);
                                    break;
                            }
                            if (!$res) {
                                return array("error" => $this->lang[ERR_CANT_OUTPUT]);
                            }
                            unset($dest);
                        } else {
                            // �� ������� ������ �������� ����������� �� �������.
                            return array("error" => $this->lang[ERR_CANT_RESIZE]);
                        }
                    }
                } else {
                    // �������� ������� �� �����, ������ �������� �����������
                    if (!copy($this->_path, $destination)) {
                        return array("error" => $this->lang[ERR_CANT_COPY]);
                    }
                    $width = $this->_imgW;
                    $height = $this->_imgH;
                }
                return array("error" => false, "destination" => $destination, "width" => $width, "height" => $height);
            }

    /**
    * ���������  ��������������� (�� �������������) ��������
    *
    * @access public
    * @return array ��. ����������� �������� saveResized()
    */
            function saveCropped($destination, $width = 0, $height = 0, $source = "", $quality = DEF_JPG_QUALITY)
            {
                if ($width || $height)
                    $this->setLimits($width, $height);
                if ($source)
                    if (!$this->setImage($source)) {
                        return array("error" => $this->_lastError);
                    }

                    if ($this->_lastError)
                        return array("error" => $this->_lastError);

                    if (!$destination = $this->prepareDestination($destination))
                        return array('error' => $this->_lastError);
                    $shift_x = $shift_y = $crop_w = $crop_h = 0;
                    if ($this->need2resize()) {
                        if ($this->_useIM) {
                            list($shift_x, $shift_y, $crop_w, $crop_h, $width, $height) = $this->getCroppedDimsIM();

                            $reply = system(IMAGEMAGICK_PATH . "convert" . " -" . ($crop_w * $crop_h < 22000?"thumbnail":"resize") . " {$crop_w}x{$crop_h}" . " -crop " . $this->_IMQuote . "{$width}x{$height} +$shift_x +$shift_y" . $this->_IMQuote .
                                ($quality != DEF_JPG_QUALITY?" -quality $quality":"") . " " . $this->_path . ($this->getExt() == 'gif' ? '[0]' : '') . " -auto-orient " . $destination);

                            if (!file_exists($destination)) {
                                return array("error" => $this->lang[ERR_CANT_OUTPUT] . " IMAGEMAGICK: $reply");
                            }
                        } else {
                            if (!$this->_prepareGD()) {
                                return array("error" => $this->_lastError);
                            }
                            list($shift_x, $shift_y, $crop_w, $crop_h, $width, $height) = $this->getCroppedDims();

                            $dest = imagecreatetruecolor($this->_xLimit, $this->_yLimit);
                            if ($this->_gdImg && imagecopyresampled($dest, $this->_gdImg, 0, 0, $shift_x, $shift_y, $width, $height, $crop_w, $crop_h)) {
                                // ��������� GD-����� � �������������� ����
                                $res = false;
                                switch ($this->_imgType) {
                                    case IMT_GIF:
                                        $res = imagegif($dest, $destination);
                                        break;
                                    case IMT_JPG:
                                        $res = imagejpeg($dest, $destination, $this->_jpgQuality);
                                        break;
                                    case IMT_PNG:
                                        $res = imagepng($dest, $destination);
                                        break;
                                }
                                if (!$res) {
                                    return array("error" => $this->lang[ERR_CANT_OUTPUT]);
                                }
                                unset($dest);
                            } else {
                                // �� ������� ������ �������� ����������� �� �������.
                                return array("error" => $this->lang[ERR_CANT_RESIZE]);
                            }
                        }
                    } else {
                        // �������� ������� �� �����, ������ �������� �����������
                        if (!copy($this->_path, $destination)) {
                            return array("error" => $this->lang[ERR_CANT_COPY]);
                        }
                        $width = $this->_imgW;
                        $height = $this->_imgH;
                    }
                    return array("error" => false, "destination" => $destination, "width" => $width, "height" => $height, 
                        'resize_percent' => array(
                            'shift' => array('x' => $shift_x / $this->_imgW, 'y' => $shift_y / $this->_imgH),
                            'size' => array('width' => ($crop_w ? $crop_w : $width) / $this->_imgW, 'height' => ($crop_h ? $crop_h : $height) / $this->_imgH),
                            'crop' => array('width' => $crop_w / $this->_imgW, 'height' => $crop_h / $this->_imgH)
                        )
                    );
                }


                /**
                * @var $destination
                * @var $startX
                * @var $startY
                */
                public function saveCropPercent($destination, $startX, $startY, $wP, $hP, $w, $h)
                {
                    if(!$this->_useIM) throw new Exception('GD not supported yet');

                    $sX = floor($startX * $this->_imgW);
                    $sY = floor($startY * $this->_imgH);

                    $wPpx = floor($wP * $this->_imgW);
                    $hPpx = floor($hP * $this->_imgH);


                    $command = IMAGEMAGICK_PATH . 
                        "convert " . $this->_path . ($this->getExt() == 'gif' ? '[0]' : '') .
                        " -crop {$wPpx}x{$hPpx}+$sX+$sY " .
                        " -resize {$w}x{$h} ".
                        " " .   $destination;
                    
                    $reply = system($command);

                    if (!file_exists($destination)) {
                        return array("error" => $this->lang[ERR_CANT_OUTPUT] . " IMAGEMAGICK: $reply");
                    }

                    return array("error" => false, "destination" => $destination, "width" => $w, "height" => $h, 
                        'resize_percent' => array(
                            'shift' => array('x' => $startX, 'y' => $startY),
                            'size' => array('width' => $wP, 'height' => $hP),
                            'crop' => array('width' => $wP, 'height' => $hP)
                        )
                    );
                }

                public function makeRound($destination)
                {

                    if (!$destination = $this->prepareDestination($destination))
                        return array('error' => $this->_lastError);

                    $destination = preg_replace('~\.[a-z]{3,4}$~', '', $destination).'.png';

                    $radius = floor(min($this->_imgW, $this->_imgH) / 2);

                    $command = IMAGEMAGICK_PATH .
                        "convert ".$this->_path." \( +clone -threshold -1 -negate -fill white -draw \"circle $radius,$radius $radius,1\" \) -alpha off -compose copy_opacity -composite ".$destination;

                    $reply = system($command);

                    if (!file_exists($destination)) {
                        return array("error" => $this->lang[ERR_CANT_OUTPUT] . " IMAGEMAGICK: $reply");
                    }

                    return array("error" => false, "destination" => $destination, "width" => $radius*2, "height" => $radius * 2 );
                }
                
                /**
                * �������� �������� ���� �����������
                * �� ���� �������� ������, ������ �����:
                item[] = array(
                    'w' => ������,
                    'h' => ������,
                    'prefix' => ������� �����
                )
                */
                public function packetResize($destination, $sizes, $base_name = '', $removePrevFiles = false){
                    if(!$this->prepareDir($destination))
                        return false;
                    
                    $destination = realpath($destination);
                    
                    /** ������� ���������� ����� **/
                    if($removePrevFiles){
                        foreach($sizes as $s) @unlink($destination.'/'.(@$s['prefix']).$removePrevFiles);
                    }
                    
                    if(!is_array($sizes)) return false;
                    if(!$filename = $this->prepareDestination($destination.'/'.$base_name)) 
                        return false;
                    $base_name = basename($filename);
                    foreach($sizes as $size){
                        $prefix = trim(@$size['prefix']);
                        if(empty($size['method']) || ($size['method'] == 'resize')){
                            $r = $this->saveResized($destination.'/'.$prefix.$base_name, @$size['w'], @$size['h']);
                        }else{
                            $r = $this->saveCropped($destination.'/'.$prefix.$base_name, @$size['w'], @$size['h']);
                        }
                        if(empty($r['destination'])){
                            trigger_error('Empty desctination '.print_r($r, 1));
                            return false;
                        }
                        if(!empty($size['assign_as_next']))
                            $this->setImage($r['destination']);
                    }
                    
                    return $base_name;
                }
                
                /**
                * ������� �������� �� ������� ������� � ������ �������
                * �  $direction  ��������� ���� right ���� left
                */
                public function rotateImage($destination, $direction = 'right'){
                    
                    if ($this->_useIM) {
                            
                        if($direction == 'right') $deg = 90; elseif($direction == 'left') $deg = -90; else $direction = 'right';
                            
                        $command = IMAGEMAGICK_PATH . "convert ". $this->_path;
                            
                        $command .= " -rotate ".$deg." ". $destination;
                        
                        $reply = system($command);
                        
                        if (!file_exists($destination)) {
                            return array("error" => $this->lang[ERR_CANT_OUTPUT] . " IMAGEMAGICK: $reply");
                        }
                        
                        return basename($destination);
                        
                    } 
                    
                }
                
                /**
                * ������������ ���� � ����������������� �����, ������� ���������� ��� � �� �����
                *
                * @access public
                * @return void
                */
                function prepareDestination($destination)
                {
                    // ����������, ����� ��� ����� � ��� �����
                    $ext = '';
                    if (substr($destination, -1) == '\\' || substr($destination, -1) == '/') {
                        $destination .= $this->getRandomName();
                    } else {
                        // ������� ����������
                        if(preg_match("/^(.+)\.([^\.]{1,4})$/", $destination, $match)){
                            $oldD = $destination;
                            $destination = $match[1];
                            $ext = strtolower($match[2]);
                        }
                        
                        // ������������� ��� �����
                        
                        $destination = dirname($destination) . '/' . rURLs::cleanURL(basename($destination), true);
                    }
                    if(!$ext)
                        $ext = $this->getExt('jpg');

                    // *****************************************
                    // ������� �������, ���� ����� ��������� ����
                    if (!$this->prepareDir(dirname($destination))) {
                        trigger_error($this->_lastError = $this->lang[ERR_CANT_CRT_DIR] .': '.$destination, E_USER_NOTICE);
                        return false;
                    }

                    // �� ��� - ������������ ��� ���
                    if ($this->_ifFileExists == FNM_RENUM && file_exists($destination . ".$ext")) {
                        $i = 1;
                        while (file_exists($destination . "_$i.$ext"))$i++;
                        $destination .= "_$i.$ext";
                    } else {
                        $destination .= ".$ext";
                        if (file_exists($destination) && !is_writable($destination)) {
                            trigger_error($this->_lastError = $this->lang[ERR_CANT_OVERWRITE]);
                            return false;
                        }
                    } // **************************** Replace||Not
                    return $destination;
                }
                // �������
                function getSizeLimit()
                {
                    return $this->_sizeLimit;
                }
                function getXLimit()
                {
                    return $this->_xLimit;
                }
                function getYLimit()
                {
                    return $this->_yLimit;
                }
                function getFileName()
                {
                    return $this->_fileName;
                }
                function getIMGType()
                {
                    return $this->_imgType;
                }
                function getWidth()
                {
                    return $this->_imgW;
                }
                function getHeight()
                {
                    return $this->_imgH;
                }
                function getLastError(){
                    return $this->_lastError;
                }
                // �������
                function setSizeLimit($sizeLimit)
                {
                    $this->_sizeLimit = (int)$sizeLimit;
                }
                function setLimits($xLimit, $yLimit, $sizeLimit = 0)
                {
                    $this->_xLimit = (int)$xLimit;
                    $this->_yLimit = (int)$yLimit;
                    if ($sizeLimit)
                        $this->setSizeLimit($sizeLimit);
                }
                function ifExists($action){
                    $this->_ifFileExists = $action;
                }
                
                function getExt($default = ''){
                    switch($this->_imgType){
                        case IMT_GIF:
                            return 'gif';
                        break;
                        case IMT_JPG:
                            return 'jpg';
                        break;
                        case IMT_PNG:
                            return 'png';
                        break;
                    }
                    return $default;
                }
                
                /*********** ������ ��������� �-��� ����� � ��������� �� ����������� **/

                /**
                * �������������� �������. ����� ����� - ���������� �������� ��, ��� �����.
                *
                * @param string $dir �������, ������� ����� �������
                * @access public
                * @return bool
                */
                public function prepareDir($dir)
                {
                    $dir = rtrim($dir, "/\\");
                    //echo "preparing $dir <br>";
                    if (!is_dir($dir)) {
                        if (!$this->prepareDir(dirname($dir)))
                            return false;
                    //echo "making $dir <br>";
                        if (@!mkdir($dir, 0777))
                            return false;
                    }
                    return true;
                }

                /**
                * ��������� ��� ��� �����
                *
                * @param string $prefix ������� ��� ���������� �����
                * @access public
                * @return string ��������� ���
                */
                function getRandomName($prefix = "")
                {
                    return str_replace(".", dechex(rand(0, 15)), uniqid($prefix, true));
                }
            } // ����� Imager
