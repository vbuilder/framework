<?php

namespace vBuilder\Forms\Controls;

use Nette;
use Nette\Forms\Container as FormContainer;
use Nette\Forms\Form;
use Nette\Forms\Controls\HiddenField;
use Nette\Utils\Html;
use Nette\Image;
use Nette\Environment;
use Nette\Http\Session;

/**
 * Based on CaptchaControl from Pavel Máca and fixed for Nette 2.1.x
 *
 * @author Pavel Máca
 * @link http://github.com/PavelMaca/CaptchaControl
 * @license MIT License
 */
class Captcha extends Nette\Forms\Controls\TextBase
{
	/*	 * #@+ character groups */
	const CONSONANTS = 'bcdfghjkmnpqrstvwxz';
	const VOWELS = 'aeiuy'; // not 'o'
	const NUMBERS = '123456789'; // not '0'
	/*	 * #@- */

	/** @var string */
	public static $defaultFontFile;

	/** @var int */
	public static $defaultFontSize = 30;

	/** @var array from Image::rgb() */
	public static $defaultTextColor = array('red' => 48, 'green' => 48, 'blue' => 48);

	/** @var int */
	public static $defaultTextMargin = 25;

	/** @var array from Image::rgb() */
	public static $defaultBackgroundColor = array('red' => 232, 'green' => 234, 'blue' => 236);

	/** @var int */
	public static $defaultLength = 5;

	/** @var int */
	public static $defaultImageHeight = 0;

	/** @var int */
	public static $defaultImageWidth = 0;

	/** @var int|bool */
	public static $defaultFilterSmooth = 1;

	/** @var int|bool */
	public static $defaultFilterContrast = -60;

	/** @var int */
	public static $defaultExpire = 10800; // 3 hours

	/** @var bool */
	public static $defaultUseNumbers = true;

	/** @var bool */
	private static $registered = false;

	/** @var Session */
	private static $session;

	/** @var string */
	private $fontFile;

	/** @var int */
	private $fontSize;

	/** @var array from Image::rgb() */
	private $textColor;

	/** @var int */
	private $textMargin;

	/** @var array from Image::rgb() */
	private $backgroundColor;

	/** @var int */
	private $length;

	/** @var int */
	private $imageHeight;

	/** @var int */
	private $imageWidth;

	/** @var int|bool */
	private $filterSmooth;

	/** @var int|bool */
	private $filterContrast;

	/** @var int uniq id */
	private $uid;

	/** @var string */
	private $word;

	/** @var int */
	private $expire;

	/** @var bool */
	private $useNumbers;

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function __construct()
	{
		if (!extension_loaded('gd')) {
			throw new \Exception('PHP extension GD is not loaded.');
		}

		parent::__construct();
		$this->setOmitted(TRUE);
		$this->addFilter('strtolower');
		$this->label = Html::el('img');

		if(!self::$defaultFontFile)
			self::$defaultFontFile = __DIR__ . "/fonts/Vera.ttf";

		$this->setFontFile(self::$defaultFontFile);
		$this->setFontSize(self::$defaultFontSize);
		$this->setTextColor(self::$defaultTextColor);
		$this->setTextMargin(self::$defaultTextMargin);
		$this->setBackgroundColor(self::$defaultBackgroundColor);
		$this->setLength(self::$defaultLength);
		$this->setImageHeight(self::$defaultImageHeight);
		$this->setImageWidth(self::$defaultImageWidth);
		$this->setFilterSmooth(self::$defaultFilterSmooth);
		$this->setFilterContrast(self::$defaultFilterContrast);
		$this->setExpire(self::$defaultExpire);
		$this->useNumbers(self::$defaultUseNumbers);

		$this->setUid(uniqid());
	}

	/**
	 * Form container extension method. Do not call directly.
	 * @param Form
	 * @param string name
	 * @return CaptchaControl
	 */
	public static function addToContainer(Form $form, $name, $label = NULL)
	{
		$_this = new static;
		$_this->caption = $label;
		return $form[$name] = $_this;
	}

	/*	 * **************** Setters & Getters **************p*m* */

	/**
	 * @param string path to font file
	 * @return CaptchaControl provides a fluent interface
	 * @throws \Nette\InvalidArgumentException
	 */
	public function setFontFile($path)
	{
		if (!empty($path) && file_exists($path)) {
			$this->fontFile = $path;
		} else {
			throw new \Nette\InvalidArgumentException("Font file '" . $path . "' not found");
		}
		return $this;
	}

	/**
	 * @return string path to font file
	 */
	public function getFontFile()
	{
		return $this->fontFile;
	}

	/**
	 * @param int
	 * @return CaptchaControl provides a fluent interface
	 */
	public function setLength($length)
	{
		$this->length = (int) $length;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getLength()
	{
		return $this->length;
	}

	/**
	 * @param int
	 * @return CaptchaControl provides a fluent interface
	 */
	public function setFontSize($size)
	{
		$this->fontSize = (int) $size;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getFontSize()
	{
		return $this->fontSize;
	}

	/**
	 * @param array red => 0-255, green => 0-255, blue => 0-255
	 * @return CaptchaControl provides a fluent interface
	 * @throws  \Nette\InvalidArgumentException
	 */
	public function setTextColor($rgb)
	{
		if (!isset($rgb["red"]) || !isset($rgb["green"]) || !isset($rgb["blue"])) {
			throw new \Nette\InvalidArgumentException("TextColor must be valid rgb array, see Nette\Image::rgb()");
		}
		$this->textColor = Image::rgb($rgb["red"], $rgb["green"], $rgb["blue"]);
		return $this;
	}

	/**
	 * @return array generated by Image::rgb()
	 */
	public function getTextColor()
	{
		return $this->textColor;
	}

	/**
	 * @param int
	 * @return CaptchaControl provides a fluent interface
	 */
	public function setTextMargin($margin)
	{
		$this->textMargin = (int) $margin;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getTextMargin()
	{
		return $this->textMargin;
	}

	/**
	 * @param array red 0-255, green 0-255, blue 0-255
	 * @return CaptchaControl provides a fluent interface
	 * @throws \Nette\InvalidArgumentException
	 */
	public function setBackgroundColor($rgb)
	{
		if (!isset($rgb["red"]) || !isset($rgb["green"]) || !isset($rgb["blue"])) {
			throw new \Nette\InvalidArgumentException("BackgroundColor must be valid rgb array, see Nette\Image::rgb()");
		}
		$this->backgroundColor = Image::rgb($rgb["red"], $rgb["green"], $rgb["blue"]);
		return $this;
	}

	/**
	 * @return array generated by Image::rgb()
	 */
	public function getBackgroundColor()
	{
		return $this->backgroundColor;
	}

	/**
	 * @param int
	 * @return CaptchaControl provides a fluent interface
	 */
	public function setImageHeight($heightt)
	{
		$this->imageHeight = (int) $heightt;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getImageHeight()
	{
		return $this->imageHeight;
	}

	/**
	 * @param int
	 * @return CaptchaControl provides a fluent interface
	 */
	public function setImageWidth($width)
	{
		$this->imageWidth = (int) $width;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getImageWidth()
	{
		return $this->imageWidth;
	}

	/**
	 * @param int|bool
	 * @return CaptchaControl provides a fluent interface
	 */
	public function setFilterSmooth($smooth)
	{
		$this->filterSmooth = $smooth;
		return $this;
	}

	/**
	 * @return int|bool
	 */
	public function getFilterSmooth()
	{
		return $this->filterSmooth;
	}

	/**
	 * @param int|bool
	 * @return CaptchaControl provides a fluent interface
	 */
	public function setFilterContrast($contrast)
	{
		$this->filterContrast = $contrast;
		return $this;
	}

	/**
	 * @return int|bool
	 */
	public function getFilterContrast()
	{
		return $this->filterContrast;
	}

	/**
	 * Set session expiration time
	 * @param int
	 * @return CaptchaControl provides a fluent interface
	 */
	public function setExpire($expire)
	{
		$this->expire = (int) $expire;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getExpire()
	{
		return $this->expire;
	}

	/**
	 * Use numbers in captcha image?
	 * @param bool
	 * @return CaptchaControl provides a fluent interface
	 */
	public function useNumbers($useNumbers = true)
	{
		$this->useNumbers = (bool) $useNumbers;
		return $this;
	}

	/**
	 * @param int
	 * @return void
	 */
	private function setUid($uid)
	{
		$this->uid = $uid;
	}

	/**
	 * @return int
	 */
	public function getUid()
	{
		return $this->uid;
	}

	/**
	 * @param int
	 * @param string
	 * @return void
	 * @throws \Nette\InvalidStateException
	 */
	private function setSession($uid, $word)
	{
		if (!self::$session)
			throw new \Nette\InvalidStateException(__CLASS__ . ' session not found');


		self::$session->$uid = $word;
		self::$session->setExpiration($this->getExpire(), $uid);
	}

	/**
	 * @return string|bool return false if key not found
	 * @throws \Nette\InvalidStateException
	 */
	private function getSession($uid)
	{
		if (!self::$session)
			throw new \Nette\InvalidStateException(__CLASS__ . ' session not found');

		return isset(self::$session->$uid) ? self::$session->$uid : false;
	}

	/**
	 * Unset session key
	 * @param int
	 * @return void
	 */
	private function unsetSession($uid)
	{
		if (self::$session && isset(self::$session->$uid)) {
			unset(self::$session->$uid);
		}
	}

	/**
	 * @return string
	 */
	public function getUidName()
	{
		return "_uid_" . $this->getName();
	}

	/**
	 * Get or generate random word for image
	 * @return string
	 */
	private function getWord()
	{
		if (!$this->word) {
			$s = '';
			for ($i = 0; $i < $this->getLength(); $i++) {
				if($this->useNumbers === true && mt_rand(0, 10) % 3 === 0){
					$group = self::NUMBERS;
					$s .= $group{mt_rand(0, strlen($group) - 1)};
					continue;
				}
				$group = $i % 2 === 0 ? self::CONSONANTS : self::VOWELS;
				$s .= $group{mt_rand(0, strlen($group) - 1)};
			}
			$this->word = $s;
		}

		return $this->word;
	}

	/*	 * **************** TextBase **************p*m* */

	/**
	 * @param string deprecated
	 * @return Html
	 */
	public function getLabel($caption = NULL)
	{
		$this->setSession($this->getUid(), $this->getWord());

		$image = clone $this->label;
		$image->src = $this->getImageSrc();
		$image->width = $this->getImageWidth();
		$image->height = $this->getImageHeight();
		if(is_array($image->class)) $image->class[] = 'captcha';
			else $image->class .= ' captcha';

		if (!isset($image->alt))
			$image->alt = "Captcha";

		return $image;
	}

	protected function getImageSrc() {
		$userAgentStart = substr($_SERVER['HTTP_USER_AGENT'],0,32);
		$oldIE = $userAgentStart == "Mozilla/4.0 (compatible; MSIE 6." || $userAgentStart == "Mozilla/4.0 (compatible; MSIE 7.";
		$base64 = 'data:image/png;base64,' . $this->drawImage();
		if ($oldIE) {
			$md5 = md5($base64);
			$cache = Nette\Environment::getCache(\vBuilder\Application\UI\Controls\ImageFixer::CACHE_NS);
			$cache->save($md5, $base64);
			return $this->form->presenter['imageFixer']->link('//getFixedImage!', array (
				'base64' => $md5
			));
		} else {
			return $base64;
		}
	}

	/**
	 * This method will be called when the component (or component's parent)
	 * becomes attached to a monitored object. Do not call this method yourself.
	 * @param \Nette\ComponentModel\IComponent
	 * @return void
	 */
	protected function attached($parent) {
		parent::attached($parent);

		if($parent instanceof Form) {
			$name = $this->getUidName();
			$parent[$name] = new HiddenField($this->getUid());
			$parent[$name]->setOmitted(TRUE);
		}

		if($parent instanceof Nette\Application\UI\Presenter) {
			$context = $parent->getContext();

			if(!isset(self::$session)) {

				$session = $context->session;
				if(!$session->isStarted())
					$session->start();

				self::$session = $session->getSection('PavelMaca.Captcha');
			}
		}
	}

	/**
	 * Sets up monitoring for Presenter
	 *
	 * @return void
	 */
	protected function validateParent(Nette\ComponentModel\IContainer $parent) {
		parent::validateParent($parent);
		$this->monitor('Nette\Application\UI\Presenter');
	}

	/*	 * **************** Drawing image **************p*m* */

	/**
	 * Draw captcha image and encode to base64 string
	 * @return string
	 */
	private function drawImage()
	{
		$word = $this->getWord();
		$font = $this->getFontFile();
		$size = $this->getFontSize();
		$textColor = $this->getTextColor();
		$bgColor = $this->getBackgroundColor();

		$box = $this->getDimensions();
		$width = $this->getImageWidth();
		$height = $this->getImageHeight();

		$first = Image::fromBlank($width, $height, $bgColor);
		$second = Image::fromBlank($width, $height, $bgColor);

		$x = ($width - $box['width']) / 2;
		$y = ($height + $box['height']) / 2;

		$first->fttext($size, 0, $x, $y, $textColor, $font, $word);

		$frequency = $this->getRandom(0.05, 0.1);
		$amplitude = $this->getRandom(2, 4);
		$phase = $this->getRandom(0, 6);

		for ($x = 0; $x < $width; $x++) {
			for ($y = 0; $y < $height; $y++) {
				$sy = round($y + sin($x * $frequency + $phase) * $amplitude);
				$sx = round($x + sin($y * $frequency + $phase) * $amplitude);

				$color = $first->colorat($x, $y);
				$second->setpixel($sx, $sy, $color);
			}
		}

		$first->destroy();

		if (defined('IMG_FILTER_SMOOTH')) {
			$second->filter(IMG_FILTER_SMOOTH, $this->getFilterSmooth());
		}

		if (defined('IMG_FILTER_CONTRAST')) {
			$second->filter(IMG_FILTER_CONTRAST, $this->getFilterContrast());
		}

		// start buffering
		ob_start();
		imagepng($second->getImageResource());
		$contents = ob_get_contents();
		ob_end_clean();

		return base64_encode($contents);
	}

	/**
	 * Detects image dimensions and returns image text bounding box.
	 * @return array
	 */
	private function getDimensions()
	{
		$box = imagettfbbox($this->getFontSize(), 0, $this->getFontFile(), $this->getWord());
		$box['width'] = $box[2] - $box[0];
		$box['height'] = $box[3] - $box[5];

		if ($this->getImageWidth() === 0) {
			$this->setImageWidth($box['width'] + $this->getTextMargin());
		}
		if ($this->getImageHeight() === 0) {
			$this->setImageHeight($box['height'] + $this->getTextMargin());
		}

		return $box;
	}

	/**
	 * Returns a random number within the specified range.
	 * @param float lowest value
	 * @param float highest value
	 * @return float
	 */
	private function getRandom($min, $max)
	{
		return mt_rand() / mt_getrandmax() * ($max - $min) + $min;
	}

	/*	 * **************** Validation **************p*m* */

	/**
	 * Validate control. Do not call directly!
	 * @param Captcha
	 * @return bool
	 * @throws \Nette\InvalidStateException
	 */
	public function validateCaptcha(Captcha $control)
	{
		$parent = $control->getParent();
		$hiddenName = $control->getUidName();

		if (!isset($parent[$hiddenName])) {
			throw new \Nette\InvalidStateException('Can\'t find ' . __CLASS__ . ' hidden field ' . $hiddenName . ' in parent');
		}

		$uid = $parent[$hiddenName]->getHttpData(Form::DATA_TEXT);

		$sessionValue = $control->getSession($uid);
		$control->unsetSession($uid);

		return ($sessionValue === $control->getValue());
	}

	/**
	 * @return Nette\Callback
	 */
	public function getValidator()
	{
		return callback($this, 'validateCaptcha');
	}

}