<?php


/**
* Замена Imager-у!
* На данной версии работает только с imagemagick!
* Методы остаются теми-же, но код упрощен и улучшен!
* Плюс методы добавлены
*/
class rImage
{
	
	protected $file = NULL;
	protected $convert = 'convert';
	protected $gravity = 'Center';
	protected $allowEnlarge = false;
	protected $resizeMode = 'resize';
	protected $autoOrient = true;

	protected $destinationPath = '';


	/**
	Конструктор у нас protected, чтобы файл был точно существующий и валидный
	**/
	protected function __construct(rImageFile $file)
	{
		if(!$file->isValid()) 
			throw new Exception('Image ('.$file->getFile().') not valid!');
		$this->file = $file;

		$this->convert = defined('IMAGEMAGICK_PATH') ? IMAGEMAGICK_PATH.'convert' : 'convert';
		$this->destinationPath = dirname($file->getFile());
	}

	/**
	* Создает объект из готового файла
	**/
	static public function getFromFile($file)
	{
		$file = new rImageFile($file);
		
		if(!$file->isValid()) return false;
		
		if(!$file->w()) return false;		

		return new self($file);
	}


	public function setGravity($gravity)
	{
		$this->gravity = $gravity;
		return $this;
	}

	public function getGravityOpt()
	{
		return ' -gravity '.$this->gravity;
	}

	public function setResizeMode($resizeMode)
	{
		$this->resizeMode = $resizeMode == 'thumbnail' ? 'thumbnail' : 'resize';
		return $this;
	}

	public function getResizeOpt($width, $height)
	{
		$command = ' -'.$this->resizeMode.' '.$width.'x'.$height;

		if(!$this->allowEnlarge) $command .= '\\>';

		return $command;
	}

	public function allowEnlarge($ae)
	{
		$this->allowEnlarge = (bool) $ae;
		return $this;
	}

	public function setDestination($dir, $forceCreate = true)
	{
		if(!is_dir($dir) && $forceCreate)
		{
			if(!mkdir($dir, 0777, true)) throw new Exception('Cant create destinationPath '.$dir);
		}

		$this->destinationPath = realpath($dir);
		if(!is_writable($this->destinationPath)) throw new Exception('Destination not writable!', 1);
		

		return $this;
	}

	public function saveResized($width, $height, $name = null)
	{

		if(!$name)
			$name = $this->getRandomName();
		$newFile = $this->destinationPath.'/'.$name;


		$command = $this->convert.
			' '.$this->file->getFile().
			$this->getGravityOpt().
			$this->getResizeOpt($width, $height);


		if($this->autoOrient) $command .=' -auto-orient';


		$command .= ' '.$newFile;

		$reply = system($command);

		$newFile = new rImageFile($newFile);

		// print_r($newFile);

		if(!$newFile->isValid()) throw new Exception('Can\'t resize file! ('.$reply.') ');
		

		return $newFile;
	}


	public function saveCropped($width, $height, $name = null, $forceAspect = true)
	{
		if(!$name)
			$name = $this->getRandomName();
		$newFile = $this->destinationPath.'/'.$name;

		$resizeBlock = '';
		// надо ли ресайзить?
		if($this->file->w() > $width || $this->file->h() > $height)
		{
			$ratio = min(
				$this->file->w() / $width, 
				$this->file->h() / $height
			);
			$newWidth = round($this->file->w() / $ratio);
			$newHeight = round($this->file->h() / $ratio);
			$resizeBlock = ' -'.$this->resizeMode.' '.$newWidth.'x'.$newHeight;
		}

		$command = $this->convert.
			' '.$this->file->getFile().
			$resizeBlock.
			$this->getGravityOpt();

		if(!$resizeBlock && $forceAspect)
		{
			// картинка слишком маленькая для обрезки, но надо поправить соотношение сторон
			$widthRatio = $width / $this->file->w();
			$heightRatio = $height / $this->file->h();

			$newHeight = $this->file->h();
			$newWidth = $this->file->w();

			if($widthRatio < $heightRatio)
			{
				// подрезаем ширину
				$ratio = $heightRatio / $widthRatio;
				$newWidth = round($this->file->w() / $ratio);
			}elseif($widthRatio > $heightRatio)
			{
				// подрезаем высоту
				$ratio = $widthRatio / $heightRatio;
				$newHeight = round($this->file->h() / $ratio);
			}

			$command .= ' -crop '.$newWidth.'x'.$newHeight.'+0+0';
		}
		else
		{
			$command .= ' -crop '.$width.'x'.$height.'+0+0';
		}

		if($this->autoOrient) $command .=' -auto-orient';

		$command .= ' '.$newFile;

		$reply = system($command);

		$newFile = new rImageFile($newFile);
		if(!$newFile->isValid()) throw new Exception('Can\'t crop file! '.$command);
		

		return $newFile;

	}


	/**
	* Придумывает случайное имя для файла
	* @var string $preix префикс для имени
	* @return string
	**/
	public function getRandomName($prefix = '')
	{
		return uniqid($prefix).$this->file->getExtension();
	}


}


/**
	
	Обертка для работы с файлом изображения

**/
class rImageFile
{

	protected $sourceFile;
	public $imageSizeRaw = false;

	public function __construct($file)
	{
		$this->setFile($file);
	}

	public function setFile($file)
	{
		$this->sourceFile = realpath($file);
		if(!$this->sourceFile)
		{
			// throw new Exception('File '.$file.' not exists!', 1);
			
			$this->imageSizeRaw = false;
			return false;
		}
		$this->imageSizeRaw = getimagesize($this->sourceFile);

	}

	public function getFile()
	{
		return $this->sourceFile;
	}

	/**
	* Возвращает имя оригинального файла.
	* @var bool $cutExtension если true — возвращает без расширения
	**/
	public function getBasename($cutExtension = false)
	{
		
		if(!$cutExtension) return basename($this->sourceFile);

		$basename = preg_replace('~\\.[^.]+$~', '', basename($this->sourceFile));
		return $basename ? $basename : trim(basename($this->sourceFile), ' .');
	}


	/**
	* Возвращает расширение файла на основе его типа.
	* Если тип какойнить не вебовский — возвращает $defaultExtension
	* @var string $defaultExtension расширение по умолчанию
	* @return string
	*/
	public function getExtension($defaultExtension = '.jpg')
	{
		if(!$this->type()) return '';

		if($this->type() == IMAGETYPE_PNG) return '.png';
		if($this->type() == IMAGETYPE_GIF) return '.gif';

		return $defaultExtension;
	}

	/**
	* Простое и безопасное получение значения индекса массива imageSizeRaw
	* @var $idx int индекс в массиве
	* @return string|null значение массива или null если значения не существует
	**/
	protected function imageSizeIdx($idx)
	{
		return is_array($this->imageSizeRaw) 
				&& isset($this->imageSizeRaw[$idx]) ?
				$this->imageSizeRaw[$idx] : NULL;
	}

	/**
	* Возвращает, чи правда ли у нас тут файл с изображением
	* @return bool
	**/
	public function isValid()
	{
		
		return (bool) $this->imageSizeIdx(0);
	}

	
	/**
	* Возвращает ширину
	* @return int
	*/
	public function w()
	{
		return (int) $this->imageSizeIdx(0);
	}

	/**
	* Возвращает высоту
	* @return int
	*/
	public function h()
	{
		return (int) $this->imageSizeIdx(1);
	}

	/**
	* Тип изображения в формате  IMAGETYPE_ХХХ
	*/
	public function type()
	{
		return $this->imageSizeIdx(2);
	}


	/**
	* Mime-тип
	* @return string
	*/
	public function mime()
	{
		return $this->imageSizeIdx(3);
	}
}