<?php 

/*****************************************************************************
	Facula Framework Imager GD Operator
	
	FaculaFramework 2013 (C) Rain Lee <raincious@gmail.com>
	
	@Copyright 2013 Rain Lee <raincious@gmail.com>
	@Author Rain Lee <raincious@gmail.com>
	@Package FaculaFramework
	@Version 2.0 prototype
	
	This file is part of Facula Framework.
	
	Facula Framework is free software: you can redistribute it and/or modify
	it under the terms of the GNU Lesser General Public License as published 
	by the Free Software Foundation, version 3.
	
	Facula Framework is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty ofapp:ds:parameter
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Lesser General Public License for more details.
	
	You should have received a copy of the GNU Lesser General Public License
	along with Facula Framework. If not, see <http://www.gnu.org/licenses/>.
*******************************************************************************/

class Image_GD implements imageHandlerInterface {
	private $imageRes = null;

	private $imageInfo = array();

	private $error = '';

	private $setting = '';

	public function __construct($file, &$config = array()) {
		$imageInfo = array('General', 'IPTC');
		$supportedImageTypes = null;
		$maxUseableMemory = intval($config['MemoryLimit']);

		$this->setting = $config;

		if ($imageInfo['General'] = getimagesize($file, $imageInfo['IPTC'])) {
			$supportedImageTypes = imagetypes();

			$this->imageInfo = array(
				'Width' => isset($imageInfo['General'][0]) ? $imageInfo['General'][0] : 0,
				'Height' => isset($imageInfo['General'][1]) ? $imageInfo['General'][1] : 0,
				'Type' => isset($imageInfo['General'][2]) ? $imageInfo['General'][2] : 0,
				'Bits' => isset($imageInfo['General']['bits']) ? $imageInfo['General']['bits'] : 0,
				'Channels' => isset($imageInfo['General']['channels']) ? $imageInfo['General']['channels'] : 3,
				'Mime' => isset($imageInfo['General']['mime']) ? $imageInfo['General']['mime'] : '',
			);

			if ($this->imageInfo['Type'] & $supportedImageTypes) {
				$this->imageInfo['Area'] = $this->imageInfo['Width'] * $this->imageInfo['Height'];

				switch($this->imageInfo['Type']) {
					case IMAGETYPE_GIF:
						if ($maxUseableMemory >= ($this->imageInfo['Area'] * $this->imageInfo['Channels'])) {
							$this->imageRes = imagecreatefromgif($file);
						} else {
							$this->error = 'ERROR_IMAGE_HANDLER_MEMORYLIMIT_EXCEED';
						}
						break;

					case IMAGETYPE_JPEG:
						if ($maxUseableMemory > ($this->imageInfo['Area'] * $this->imageInfo['Channels'])) {
							$this->imageRes = imagecreatefromjpeg($file);
						} else {
							$this->error = 'ERROR_IMAGE_HANDLER_MEMORYLIMIT_EXCEED';
						}
						break;

					case IMAGETYPE_PNG:
						if ($maxUseableMemory >= ($this->imageInfo['Area'] * ($this->imageInfo['Channels'] + 1))) {
							if ($this->imageRes = imagecreatefrompng($file)) {
								imagealphablending($this->imageRes, true);

								$this->imageInfo['Transparent'] = imagecolorallocatealpha($this->imageRes, 255, 255, 255, 127);
							}
						} else {
							$this->error = 'ERROR_IMAGE_HANDLER_MEMORYLIMIT_EXCEED';
						}
						break;

					case IMAGETYPE_WBMP:
						if ($maxUseableMemory >= ($this->imageInfo['Area'] * $this->imageInfo['Channels'])) {
							$this->imageRes = imagecreatefromwbmp($file);
						} else {
							$this->error = 'ERROR_IMAGE_HANDLER_MEMORYLIMIT_EXCEED';
						}
						break;

					case IMAGETYPE_XBM:
						if ($maxUseableMemory >= ($this->imageInfo['Area'] * $this->imageInfo['Channels'])) {
							$this->imageRes = imagecreatefromxbm($file);
						} else {
							$this->error = 'ERROR_IMAGE_HANDLER_MEMORYLIMIT_EXCEED';
						}
						break;

					default:
						$this->error = 'ERROR_IMAGE_HANDLER_IMAGE_INVALID';

						return false;
						break;
				}
			} else {
				$this->error = 'ERROR_IMAGE_HANDLER_IMAGE_NOTSUPPORTED';
			}

			return true;
		}

		return false;
	}

	public function __destruct() {
		if ($this->imageRes) {
			return imagedestroy($this->imageRes);
		}

		return false;
	}

	public function getLastError() {
		return $this->error;
	}

	public function resize($width, $height, $resizeSmall = false, $drawAreaWidth = 0, $drawAreaHeight = 0) {
		$transparent = 0;
		$newImgWidth = $drawAreaWidth ? $drawAreaWidth : $width;
		$newImgHeight = $drawAreaHeight ? $drawAreaHeight : $height;

		if ($this->imageRes) {
			if (!$resizeSmall && ($this->imageInfo['Width'] < $width || $this->imageInfo['Height'] < $height)) {
				return true;
			}

			if ($newImg = imagecreatetruecolor($newImgWidth, $newImgHeight)) {
				if (isset($this->imageInfo['Transparent'])) {
					imagealphablending($newImg, false);
					imagesavealpha($newImg, true);

					$transparent = $this->imageInfo['Transparent'];
				}

				if (imagefilledrectangle($newImg, 0, 0, $width, $height, $transparent) && imagecopyresampled($newImg, $this->imageRes, 0, 0, 0, 0, $width, $height,  $this->imageInfo['Width'], $this->imageInfo['Height'])) {
					imagedestroy($this->imageRes); // Remove the org image file res

					$this->imageRes = $newImg; // Replace the old image res with new one

					$this->imageInfo['Width'] = $newImgWidth; // Renew the size info
					$this->imageInfo['Height'] = $newImgHeight;

					if (isset($this->imageInfo['Transparent'])) {
						$this->imageInfo['Transparent'] = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
					}

					return true;
				}
			}
		} else {
			$this->error = 'ERROR_IMAGE_HANDLER_IMAGE_NOTLOAD';
		}

		return false;
	}

	public function ratioResize($width, $height, $resizeSmall = false) {
		$ratio = 0;
		$ratioWidth = $width / $this->imageInfo['Width'];
		$ratioHeight = $height / $this->imageInfo['Height'];

		if ($ratioWidth < $ratioHeight) {
			$ratio = $ratioWidth;
		} else {
			$ratio = $ratioHeight;
		}

		return $this->resize($this->imageInfo['Width'] * $ratio, $this->imageInfo['Height'] * $ratio, $resizeSmall);
	}

	public function fillResize($width, $height) {
		$ratio = 0;
		$ratioWidth = $width / $this->imageInfo['Width'];
		$ratioHeight = $height / $this->imageInfo['Height'];

		if ($ratioWidth > $ratioHeight) {
			$ratio = $ratioWidth;
		} else {
			$ratio = $ratioHeight;
		}

		return $this->resize($this->imageInfo['Width'] * $ratio, $this->imageInfo['Height'] * $ratio, true, $width, $height);
	}

	public function waterMark($file, $align = 'center') {
		
	}

	public function waterMarkText($text, $align = 'center', $color = array(255, 255, 255)) {
		$colorLayer = $fontColor = $shadowFontColor = null;
		$fontX = $fontY = 0;

		if ($this->imageRes) {
			if (isset($this->setting['Font'][0]) && $this->setting['FontSize']) {
				$fontWidth = intval(mb_strlen($text) * $this->setting['FontSize'] / 1.5);
				$fontHeight = $this->setting['FontSize'];

				if ($fontWidth < $this->imageInfo['Width'] && $fontHeight < $this->setting['FontSize']) {
					switch ($align) {
						case 'topleft':
							break;

						case 'topright':
							break;

						case 'midleft':
							break;

						case 'midright':
							break;

						case 'buttoleft':
							break;

						case 'buttoright':
							break;
						
						default:
							$fontY = intval(($this->imageInfo['Height'] / 2) - ($fontHeight / 2));
							$fontX = intval(($this->imageInfo['Width'] / 2) - ($fontWidth / 2));
							break;
					}
					
					if ($colorLayer = imagecreatetruecolor($fontWidth, $fontHeight)) {
						if (isset($this->imageInfo['Transparent'])) {
							$fontColor = imagecolorallocatealpha($colorLayer, $color[0], $color[1], $color[2], 127);
							$shadowFontColor = imagecolorallocatealpha($colorLayer, 0, 0, 0, 127);
						} else {
							$fontColor = imagecolorallocate($colorLayer, $color[0], $color[1], $color[2]);
							$shadowFontColor = imagecolorallocate($colorLayer, 0, 0, 0);
						}

						if ($fontColor !== false && $shadowFontColor !== false) {
							return (imagettftext($this->imageRes, $this->setting['FontSize'], 0, $fontX + 1, $fontY + 1, $shadowFontColor, $this->setting['Font'], $text) && 
									imagettftext($this->imageRes, $this->setting['FontSize'], 0, $fontX, $fontY, $fontColor, $this->setting['Font'], $text)
									? true : false);
						}
					}
				} else {
					$this->error = 'ERROR_IMAGE_HANDLER_WATERMARK_TOOLARGE';
				}
			} else {
				$this->error = 'ERROR_IMAGE_HANDLER_FONT_NOTSET';
			}
		} else {
			$this->error = 'ERROR_IMAGE_HANDLER_IMAGE_NOTLOAD';
		}

		return false;
	}

	public function getWatermarkPos($width, $height, $wmWidth, $wmHeight) {

	}

	public function save($file) {
		if ($this->imageRes) {
			if (!file_exists($file)) {
				switch($this->imageInfo['Type']) {
					case IMAGETYPE_GIF:
						return imagegif($this->imageRes, $file);
						break;

					case IMAGETYPE_JPEG:
						return imagejpeg($this->imageRes, $file);
						break;

					case IMAGETYPE_PNG:
						return imagepng($this->imageRes, $file);
						break;

					case IMAGETYPE_WBMP:
						return imagewbmp($this->imageRes, $file);
						break;

					case IMAGETYPE_XBM:
						return imagexbm($this->imageRes, $file);
						break;

					default:
						$this->error = 'ERROR_IMAGE_HANDLER_IMAGE_INVALID';

						return false;
						break;
				}
			} else {
				$this->error = 'ERROR_IMAGE_HANDLER_SAVE_FILEEXISTED';
			}
		} else {
			$this->error = 'ERROR_IMAGE_HANDLER_IMAGE_NOTLOAD';
		}

		return false;
	}
}

?>