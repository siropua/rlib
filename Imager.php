<?php

/**
* Работа с изображениями.
*
* Пример использования:
*
*<code>
* 	<?
* $path2image = "/tmp/tmp.image"; // входное изображение
*
* // создаём объект. вторым параметром можно переопределить строки возвращаемых ошибок
* $imager = new Imager($path2image, array(
* ERR_CANT_COPY=>"Не можем скопировать изображение",
* ERR_CANT_CRT_DIR=>"Не можем создать котолог йюзера",
* ERR_CANT_DTR_IMG_SIZE=>"Не можем определить размеры изображения",
* ERR_IMG_NOT_VALID=>"Фотка повреждена"
* ));
*
* $dir = "/www/host/destination_dir/";
* $filename = "картинка.jpg"; // имя файла автоматически транслитерируется в латиницу, расширение будет проставлено в соотвецтвии с типом
*
* // сохраняем изображение в уменьшеном виде
* $result = $imager->saveResized($dir.$filename, PHOTO_MAX_WIDTH, PHOTO_MAX_HEIGHT);
* if(!$result['error'])
* {
* // если оно сохранилось, можем создать и превьюшку.
* // последний параметр передается для того, чтобы использовать уже уменьшенный и оптимизированный вариант картинки, для скорости
* $thumb_result = $imager->saveResized($dir."thumb_".$filename, THUMB_WIDTH, THUMB_HEIGHT, $result['destination']);
* }else $thumb_result = $result;
*
* // проверяем как всё прошло
* if(!$result['error'] && !$thumb_result['error'])
* {
*
* // ВСЕ ОК! Картинка загружена! ее путь в системе будет в $result['destination'];
* // т.е. например имя файла мможем получить через basename($result['destination'])
* // также доступны width, height
* }else
* {
* // Ошибка! Её текст будет в $result['error']
* }
* 	?>
*</code>
*
* ВАЖНОЕ: Чтобы использовать ImageMagick, необходимо создать константу IMAGEMAGICK_PATH до создания объекта
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
* Работа с изображениями
*
* @package rLib
* @author Steel Ice
* @copyright Copyright (c) 2007
* @version $Id: Imager.php,v 1.7 2008/02/20 13:34:26 steel Exp $
* @access public
*/
class Imager {

   /** @var string Путь к картинке */
    var $_path = "";

    /** @var int Максимальный размер картинки.
	* 	120kb по умолчанию максимум */
    var $_sizeLimit = 120000; //

	/** @var int максимум по длинне */
    var $_xLimit = 600;
    /** @var int максимум по высоте */
    var $_yLimit = 700; //
    /** @var int Что делаеть, при дубликате имени файла
    * 	Возможные варианты:
    * 	FNM_REPLACE - Заменять файлы
    * 	FNM_RENUM - Перенумеровывать (dup.jpg => dup1.jpg)
    */
    var $_ifFileExists = FNM_RENUM;
	/** @var int Качество jpg-файлов в результате */
    var $_jpgQuality = DEF_JPG_QUALITY; // качество выводимых JPG по умолчанию

	/**
	* @var object GD object of imahe
	*/
    var $_gdImg = 0;

    var $_imgW = 0;
    var $_imgH = 0;
    var $_imgFilesize = 0;
    var $_imgType = IMT_NONE;
    var $_fileName = "";

	/** @var string Содержит строку с последней ошибкой */
    var $_lastError = "";
    /** @var int Содержит код последней ошибки */
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
    * Конструкторъ
    *
    * @param string $path2image Путь к исходной картинке.
    * 							Можно, в принципе, не задавать
    * @param array $lang Массив с языковыми строками. В основном содержит ошибки
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
    * Принудительно отключает использование ImageMagick
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
    * Подготавливает GD-поток
    *
    * @access public
    * @return bool В случае ошибки возвратит FALSE
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
     * Устанавливает картинку
     *
     * @param string $path Путь к файлу картинки
     * @return bool Если картинка не найдена или имеет неподерживаемый формат возвратит FALSE
     **/
    function setImage($path)
    {
        $this->_lastError = '';
        if (!file_exists($path)) {
            $this->_lastError = $this->lang[ERR_IMG_NOT_FOUND];
            //trigger_error($this->_lastError . " ($path)", E_USER_NOTICE);
            // очищаем переменные, шобы знать шо у нас ошыбко
            $this->_imgFilesize = $this->_imgH = $this->_imgW = $this->_gdImg = 0;
            $this->_imgType = IMT_NONE;
            return false;
        }
        @$size = getimagesize($path);
        if (!$size) {
            //trigger_error($this->_lastError = $this->lang[ERR_CANT_DTR_IMG_SIZE], E_USER_NOTICE);
            // очищаем переменные, шобы знать шо у нас ошыбко
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
     * Сохраняет оригинал
     *
     * @return bool
     **/

     function saveOriginal($destination, $source = "")
     {
        if ($source){
            if (!$this->setImage($source)) {
                return array("error" => $this->_lastError);
            }
        }
        if ($this->_lastError) {
            return array("error" => $this->_lastError);
        }
        if (!$destination = $this->prepareDestination($destination)) {
                return array('error' => $this->_lastError);
        }
        if (!copy($this->_path, $destination)) {
                return array('error' => 'Can\'t save result');
        }
        return array("error" => false, "destination" => $destination, "width" => $this->_imgW, "height" => $this->_imgH);
     }

    /**
     * Определяет, нужно ли изменять размер
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
     * Получение массива со значениями, как нужно уменьшить изображение
     *
     * @return array Индексы возвращемого массива:
     * 					0 - новый размер длинны
     * 					1 - новый размер высоты
     * 					2 - ratio: отношение, на которое изменяется размер
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
    *	Получаем массив со значениями, как нужно обрезать изображение
    *
    * @access private
    * @return array  Возвращает массив, как нужно обрезать изображение.
    * 	 Индексы массива:
    * 		0 - смещение по Х в оригинале
    * 		1 - смещение по Y в оригинале
    * 		2 - длинна в оригинале
    * 		3 - высота в оригинале
    * 		4 - результирующая длинна
    * 		5 - результирующая высота
    * 		Полная длинна и высота  картинки будет равна $_xLimit и $_yLimit соответственно.

    */
    function getCroppedDims()
    {
        // проверяем нужность расчётов
        if (!$this->need2resize())
            return array(0, 0, $this->_imgW, $this->_imgH, $this->_imgW, $this->_imgH);
        // таки нужно. вперёд
        $mul = min($this->_imgW / $this->_xLimit, $this->_imgH / $this->_yLimit);
        $crop_w = (int)ceil($this->_xLimit * $mul);
        $crop_h = (int)ceil($this->_yLimit * $mul);
        $shift_x = floor(($this->_imgW - $crop_w) / 2);
        $shift_y = floor(($this->_imgH - $crop_h) / 2);
        return array($shift_x, $shift_y, $crop_w, $crop_h, $this->_xLimit, $this->_yLimit);
    }

    function getCroppedDimsIM()
    {
        // проверяем нужность расчётов
        if (!$this->need2resize())
            return array(0, 0, $this->_imgW, $this->_imgH, $this->_imgW, $this->_imgH);
        // таки нужно. вперёд
        $ratio = ($this->_imgW > $this->_imgH)? $this->_yLimit / $this->_imgH : $this->_xLimit / $this->_imgW;
        $crop_h = (int)($this->_imgH * $ratio);
        $crop_w = (int)($this->_imgW * $ratio);
        $shift_x = (int)(($crop_w - $this->_xLimit) / 2);
        $shift_y = (int)(($crop_h - $this->_yLimit) / 2);

        return array($shift_x, $shift_y, $crop_w, $crop_h, $this->_xLimit, $this->_yLimit);
    }

    /**
    * Уменьшаем  пропорционально (по необходимости) картинку
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
                            // Сохраняем GD-поток в соотвецтвующий файл
                            
                            // сохраняем вотемарковый текст, если необходимо:
                            
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
                            // По какимто жутким причинам отресайзить не удалось.
                            return array("error" => $this->lang[ERR_CANT_RESIZE]);
                        }
                    }
                } else {
                    // изменять размеры не нужно, просто копируем изображение
                    if (!copy($this->_path, $destination)) {
                        return array("error" => $this->lang[ERR_CANT_COPY]);
                    }
                    $width = $this->_imgW;
                    $height = $this->_imgH;
                }
                return array("error" => false, "destination" => $destination, "width" => $width, "height" => $height);
            }

    /**
    * Уменьшаем  пропорционально (по необходимости) картинку
    *
    * @access public
    * @return array см. возвращемые значение saveResized()
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
                                // Сохраняем GD-поток в соотвецтвующий файл
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
                                // По какимто жутким причинам отресайзить не удалось.
                                return array("error" => $this->lang[ERR_CANT_RESIZE]);
                            }
                        }
                    } else {
                        // изменять размеры не нужно, просто копируем изображение
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
                * пакетное создание кучи изображений
                * на вход подается массив, формат такой:
                item[] = array(
                    'w' => ширина,
                    'h' => высота,
                    'prefix' => префикс файла
                )
                */
                public function packetResize($destination, $sizes, $base_name = '', $removePrevFiles = false){
                    if(!$this->prepareDir($destination))
                        return false;
                    
                    $destination = realpath($destination);
                    
                    /** удаляем предыдущие файлы **/
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
                * Поворот картинки по часовой стрелке и против часовой
                * в  $direction  указываем либо right либо left
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
                * Обрабатывает путь к результатирующему файлу, выбирая подходящее имя и всё такое
                *
                * @access public
                * @return void
                */
                function prepareDestination($destination)
                {
                    // определяем, какое имя файла у нас будет
                    $ext = '';
                    if (substr($destination, -1) == '\\' || substr($destination, -1) == '/') {
                        $destination .= $this->getRandomName();
                    } else {
                        // убираем расширение
                        if(preg_match("/^(.+)\.([^\.]{1,4})$/", $destination, $match)){
                            $oldD = $destination;
                            $destination = $match[1];
                            $ext = strtolower($match[2]);
                        }
                        
                        // нормализируем имя файла
                        
                        $destination = dirname($destination) . '/' . rURLs::cleanURL(basename($destination), true);
                    }
                    if(!$ext)
                        $ext = $this->getExt('jpg');

                    // *****************************************
                    // готовим каталог, куда будет переписан файл
                    if (!$this->prepareDir(dirname($destination))) {
                        trigger_error($this->_lastError = $this->lang[ERR_CANT_CRT_DIR] .': '.$destination, E_USER_NOTICE);
                        return false;
                    }

                    // чо там - переписывать или нет
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
                // Геттеры
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
                // Сеттеры
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
                
                /*********** ВСЯКИЕ СЕРВИСНЫЕ Ф-ЦИИ ОСОБО К КАРТИНКАМ НЕ ОТНОСЯЩИЕСЯ **/

                /**
                * Подготавливает каталог. Ежели нужно - рекурсивно посоздаёт всё, что нужно.
                *
                * @param string $dir Каталог, который нужно создать
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
                * Случайное имя для файла
                *
                * @param string $prefix Префикс для случайного имени
                * @access public
                * @return string Случайное имя
                */
                function getRandomName($prefix = "")
                {
                    return str_replace(".", dechex(rand(0, 15)), uniqid($prefix, true));
                }
            } // Класс Imager
