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
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with Facula Framework. If not, see <http://www.gnu.org/licenses/>.
*******************************************************************************/

class ImagerGD extends ImageCommon implements ImageHandlerInterface
{
    private $imageRes = null;

    private $imageInfo = array();

    private $error = '';

    private $setting = '';

    public function __construct($file, &$config = array())
    {
        $image = array();

        $this->setting = $config;
        $this->setting['SupportedImageTypes'] = imagetypes();

        if ($image = $this->openImage($file)) {
            $this->imageRes = $image['Res'];
            $this->imageInfo = $image['Info'];

            return true;
        }

        return false;
    }

    public function __destruct()
    {
        if ($this->imageRes) {
            return imagedestroy($this->imageRes);
        }

        return false;
    }

    public function getLastError()
    {
        return $this->error;
    }

    public function getImageRes()
    {
        if ($this->imageRes) {
            return $this->imageRes;
        }

        return null;
    }

    public function openImage($file)
    {
        $imageRawInfo = $imageInfo = array();
        $imageRes = null;

        if ($imageRawInfo['General'] = getimagesize($file, $imageRawInfo['IPTC'])) {
            $imageInfo = array(
                'Width' => isset($imageRawInfo['General'][0]) ? $imageRawInfo['General'][0] : 0,
                'Height' => isset($imageRawInfo['General'][1]) ? $imageRawInfo['General'][1] : 0,
                'Type' => isset($imageRawInfo['General'][2]) ? $imageRawInfo['General'][2] : 0,
                'Bits' => isset($imageRawInfo['General']['bits']) ? $imageRawInfo['General']['bits'] : 0,
                'Channels' => isset($imageRawInfo['General']['channels']) ? $imageRawInfo['General']['channels'] : 3,
                'Mime' => isset($imageRawInfo['General']['mime']) ? $imageRawInfo['General']['mime'] : '',
            );

            if ($imageInfo['Type'] & $this->setting['SupportedImageTypes']) {
                $imageInfo['Area'] = $imageInfo['Width'] * $imageInfo['Height'];

                switch ($imageInfo['Type']) {
                    case IMAGETYPE_GIF:
                        if ($this->setting['MemoryLimit'] >= ($imageInfo['Area'] * $imageInfo['Channels'] + 1)) {
                            $imageRes = imagecreatefromgif ($file);
                        } else {
                            $this->error = 'ERROR_IMAGE_HANDLER_MEMORYLIMIT_EXCEED';
                        }
                        break;

                    case IMAGETYPE_JPEG:
                        if ($this->setting['MemoryLimit'] > ($imageInfo['Area'] * $imageInfo['Channels'] + 1)) {
                            $imageRes = imagecreatefromjpeg($file);
                        } else {
                            $this->error = 'ERROR_IMAGE_HANDLER_MEMORYLIMIT_EXCEED';
                        }
                        break;

                    case IMAGETYPE_PNG:
                        if ($this->setting['MemoryLimit'] >= ($imageInfo['Area'] * ($imageInfo['Channels'] + 3))) {
                            if ($imageRes = imagecreatefrompng($file)) {
                                imagealphablending($imageRes, true);

                                $imageInfo['Transparent'] = imagecolorallocatealpha($imageRes, 255, 255, 255, 127);
                            }
                        } else {
                            $this->error = 'ERROR_IMAGE_HANDLER_MEMORYLIMIT_EXCEED';
                        }
                        break;

                    case IMAGETYPE_WBMP:
                        if ($this->setting['MemoryLimit'] >= ($imageInfo['Area'] * $imageInfo['Channels'] + 1)) {
                            $imageRes = imagecreatefromwbmp($file);
                        } else {
                            $this->error = 'ERROR_IMAGE_HANDLER_MEMORYLIMIT_EXCEED';
                        }
                        break;

                    case IMAGETYPE_XBM:
                        if ($this->setting['MemoryLimit'] >= ($imageInfo['Area'] * $imageInfo['Channels'] + 1)) {
                            $imageRes = imagecreatefromxbm($file);
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

            if ($imageRes) {
                return array(
                    'Res' => $imageRes,
                    'Info' => $imageInfo,
                );
            }

        }

        return false;
    }

    private function closeImage(&$image)
    {
        if (isset($image['Res'])) {
            return imagedestroy($image['Res']);
        }

        return false;
    }

    public function resize($width, $height, $resizeSmall = false, $drawAreaWidth = 0, $drawAreaHeight = 0)
    {
        $transparent = $cutPosX = $cutPosY = 0;
        $newImgWidth = $drawAreaWidth ? $drawAreaWidth : $width;
        $newImgHeight = $drawAreaHeight ? $drawAreaHeight : $height;

        if ($this->imageRes) {
            if (!$resizeSmall && ($this->imageInfo['Width'] <= $width || $this->imageInfo['Height'] <= $height)) {
                return true;
            }

            if ($newImg = imagecreatetruecolor($newImgWidth, $newImgHeight)) {
                $old_ratio = $this->imageInfo['Width'] / $this->imageInfo['Height'];
                $new_ratio = $newImgWidth / $newImgHeight;

                if ($old_ratio > $new_ratio) {
                    $cutPosX = ceil(($this->imageInfo['Width'] - ($this->imageInfo['Height'] * $new_ratio)) / 2);
                    $cutPosY = 0;
                } else {
                    $cutPosX = 0;
                    $cutPosY = ceil(($this->imageInfo['Height'] - ($this->imageInfo['Width'] / $new_ratio)) /2);
                }

                if (isset($this->imageInfo['Transparent'])) {
                    imagealphablending($newImg, false);
                    imagesavealpha($newImg, true);

                    $transparent = $this->imageInfo['Transparent'];
                }

                if (imagefilledrectangle($newImg, 0, 0, $width, $height, $transparent) && imagecopyresampled($newImg, $this->imageRes, 0, 0, $cutPosX, $cutPosY, $width, $height, $this->imageInfo['Width'], $this->imageInfo['Height'])) {
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

    public function ratioResize($width, $height, $resizeSmall = false)
    {
        $ratio = $ratioWidth = $ratioHeight = 0;

        if ($this->imageRes) {
            $ratioWidth = $width / $this->imageInfo['Width'];
            $ratioHeight = $height / $this->imageInfo['Height'];

            if ($ratioWidth < $ratioHeight) {
                $ratio = $ratioWidth;
            } else {
                $ratio = $ratioHeight;
            }

            return $this->resize($this->imageInfo['Width'] * $ratio, $this->imageInfo['Height'] * $ratio, $resizeSmall);
        }

        return false;
    }

    public function fillResize($width, $height)
    {
        $ratio = $ratioWidth = $ratioHeight = 0;

        if ($this->imageRes) {
            $ratioWidth = $width / $this->imageInfo['Width'];
            $ratioHeight = $height / $this->imageInfo['Height'];

            if ($ratioWidth > $ratioHeight) {
                $ratio = $ratioWidth;
            } else {
                $ratio = $ratioHeight;
            }

            return $this->resize($this->imageInfo['Width'] * $ratio, $this->imageInfo['Height'] * $ratio, true, $width, $height);
        }

        return false;
    }

    public function waterMark($file, $align = 'center center', $margin = 0)
    {
        $watermark = null;
        $markX = $markY = 0;
        $result = false;

        if ($this->imageRes) {
            if ($watermark = $this->openImage($file)) {
                list($markX, $markY) = $this->getAlignPos($align, $this->imageInfo['Width'], $this->imageInfo['Height'], $watermark['Info']['Width'], $watermark['Info']['Height'], $margin);

                if (imagecopy($this->imageRes, $watermark['Res'], $markX, $markY, 0, 0, $watermark['Info']['Width'], $watermark['Info']['Height'])) {
                    $result = true;
                }

                $this->closeImage($watermark);

                return $result;
            }
        } else {
            $this->error = 'ERROR_IMAGE_HANDLER_IMAGE_NOTLOAD';
        }

        return false;
    }

    public function waterMarkText($text, $align = 'center center', $margin = 0, $color = array(255, 255, 255))
    {
        $colorLayer = $fontColor = $shadowFontColor = null;
        $fontX = $fontY = 0;
        $fontBoxPos = array();

        $result = false;

        if ($this->imageRes) {
            if (isset($this->setting['Font'][0]) && $this->setting['FontSize']) {
                $fontBoxPos = imagettfbbox($this->setting['FontSize'], 0, $this->setting['Font'], $text);

                $fontWidth = abs($fontBoxPos[4] - $fontBoxPos[0]);
                $fontHeight = abs($fontBoxPos[5] - $fontBoxPos[1]);

                if ($fontWidth < $this->imageInfo['Width'] && $fontHeight < $this->imageInfo['Height']) {
                    list($fontX, $fontY) = $this->getAlignPos($align, $this->imageInfo['Width'], $this->imageInfo['Height'], $fontWidth, $fontHeight, $margin);

                    $fontY += $fontHeight; // imagettfbbox will align using baseline...

                    if ($colorLayer = imagecreatetruecolor($fontWidth, $fontHeight)) {
                        if (isset($this->imageInfo['Transparent'])) {
                            imagesavealpha($colorLayer, true);
                            imagealphablending($colorLayer, false);

                            $alphaWhite = imagecolorallocatealpha($colorLayer, 255, 255, 255, 127);

                            imagefill($colorLayer, 0, 0, $alphaWhite);
                        }

                        $fontColor = imagecolorallocate($colorLayer, $color[0], $color[1], $color[2]);
                        $shadowFontColor = imagecolorallocate($colorLayer, 0, 0, 0);

                        if ($fontColor !== false && $shadowFontColor !== false) {
                            if (imagettftext($this->imageRes, $this->setting['FontSize'], 0, $fontX + 1, $fontY + 1, $shadowFontColor, $this->setting['Font'], $text) && imagettftext($this->imageRes, $this->setting['FontSize'], 0, $fontX, $fontY, $fontColor, $this->setting['Font'], $text)) {
                                $result = true;
                            }

                            imagedestroy($colorLayer);

                            return $result;
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

    public function save($file)
    {
        if ($this->imageRes) {
            if (!file_exists($file)) {
                switch ($this->imageInfo['Type']) {
                    case IMAGETYPE_GIF:
                        return imagegif($this->imageRes, $file);
                        break;

                    case IMAGETYPE_JPEG:
                        return imagejpeg($this->imageRes, $file, 85);
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
