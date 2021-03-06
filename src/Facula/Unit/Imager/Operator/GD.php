<?php

/**
 * GD Operator for Imager
 *
 * Facula Framework 2015 (C) Rain Lee
 *
 * Facula Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, version 3.
 *
 * Facula Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Facula Framework. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author     Rain Lee <raincious@gmail.com>
 * @copyright  2015 Rain Lee
 * @package    Facula
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Unit\Imager\Operator;

use Facula\Unit\Imager\OperatorImplement;
use Facula\Unit\Imager\Base;

/**
 * Imager GD Handler
 */
class GD extends Base implements OperatorImplement
{
    /** Resource handler */
    private $imageRes = null;

    /** Image information */
    private $imageInfo = array();

    /** Container for last error */
    private $error = '';

    /** Instance setting */
    private $setting = '';

    /**
     * Constructor of Image handler
     *
     * @param string $file Path to the file
     * @param array $config Configure array
     *
     * @return void
     */
    public function __construct($file, array &$config = array())
    {
        $image = array();

        $this->setting = $config;
        $this->setting['SupportedImageTypes'] = imagetypes();

        if ($image = $this->openImage($file)) {
            $this->imageRes = $image['Res'];
            $this->imageInfo = $image['Info'];
        }
    }

    /**
     * Destructor of Image handler
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->imageRes) {
            imagedestroy($this->imageRes);
        }
    }

    /**
     * Get the last triggered error
     *
     * @return string The error message or code
     */
    public function getLastError()
    {
        return $this->error;
    }

    /**
     * Get the loaded image resource
     *
     * @return mixed GD Image resource when image loaded, or false when fail
     */
    public function getImageRes()
    {
        if ($this->imageRes) {
            return $this->imageRes;
        }

        return null;
    }

    /**
     * Load a file into instance
     *
     * @param string $file Path to the file
     *
     * @return mixed array of image information when succeed, or false for fail
     */
    public function openImage($file)
    {
        $imageRawInfo = $imageInfo = array();
        $imageRes = null;

        if ($imageRawInfo['General'] = getimagesize($file, $imageRawInfo['IPTC'])) {
            $imageInfo = array(
                'Width' => isset($imageRawInfo['General'][0])
                    ? $imageRawInfo['General'][0] : 0,

                'Height' => isset($imageRawInfo['General'][1])
                    ? $imageRawInfo['General'][1] : 0,

                'Type' => isset($imageRawInfo['General'][2])
                    ? $imageRawInfo['General'][2] : 0,

                'Bits' => isset($imageRawInfo['General']['bits'])
                    ? $imageRawInfo['General']['bits'] : 0,

                'Channels' => isset($imageRawInfo['General']['channels'])
                    ? $imageRawInfo['General']['channels'] : 3,

                'Mime' => isset($imageRawInfo['General']['mime'])
                    ? $imageRawInfo['General']['mime'] : '',
            );

            if ($imageInfo['Type'] & $this->setting['SupportedImageTypes']) {
                $imageInfo['Area'] = $imageInfo['Width'] * $imageInfo['Height'];

                switch ($imageInfo['Type']) {
                    case IMAGETYPE_GIF:
                        if ($this->setting['MemoryLimit'] >=
                            ($imageInfo['Area'] * ($imageInfo['Channels'] + 1))) {
                            $imageRes = imagecreatefromgif($file);

                            $imageInfo['Transparent'] = imagecolorallocatealpha(
                                $imageRes,
                                0,
                                0,
                                0,
                                127
                            );

                            // GIF file needs color transparent.
                            // because it doesn't have an alpha layer like PNG did.
                            imagecolortransparent($imageRes, $imageInfo['Transparent']);
                        } else {
                            $this->error = 'ERROR_IMAGE_HANDLER_MEMORYLIMIT_EXCEED';
                        }
                        break;

                    case IMAGETYPE_JPEG:
                        if ($this->setting['MemoryLimit'] >
                            ($imageInfo['Area'] * ($imageInfo['Channels'] + 1))) {
                            $imageRes = imagecreatefromjpeg($file);
                        } else {
                            $this->error = 'ERROR_IMAGE_HANDLER_MEMORYLIMIT_EXCEED';
                        }
                        break;

                    case IMAGETYPE_PNG:
                        if ($this->setting['MemoryLimit'] >=
                            ($imageInfo['Area'] * ($imageInfo['Channels'] + 3))) {
                            if ($imageRes = imagecreatefrompng($file)) {
                                imagealphablending($imageRes, true);

                                $imageInfo['Transparent'] = imagecolorallocatealpha(
                                    $imageRes,
                                    255,
                                    255,
                                    255,
                                    127
                                );
                            }
                        } else {
                            $this->error = 'ERROR_IMAGE_HANDLER_MEMORYLIMIT_EXCEED';
                        }
                        break;

                    case IMAGETYPE_WBMP:
                        if ($this->setting['MemoryLimit'] >=
                            ($imageInfo['Area'] * ($imageInfo['Channels'] + 1))) {
                            $imageRes = imagecreatefromwbmp($file);
                        } else {
                            $this->error = 'ERROR_IMAGE_HANDLER_MEMORYLIMIT_EXCEED';
                        }
                        break;

                    case IMAGETYPE_XBM:
                        if ($this->setting['MemoryLimit'] >=
                            ($imageInfo['Area'] * ($imageInfo['Channels'] + 1))) {
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

    /**
     * Close the image resource
     *
     * @param array $image Array that returned by self::openImage method
     *
     * @return bool Return true when image destroyed, false when fail
     */
    private function closeImage(&$image)
    {
        if (isset($image['Res']) && imagedestroy($image['Res'])) {
            return true;
        }

        return false;
    }

    /**
     * Resize the image
     *
     * @param integer $width Width of the resulting image
     * @param integer $height Height of the resulting image
     * @param bool $resizeSmall Resize the image to resulting size even it small than it
     * @param integer $drawAreaWidth Width of the max drawing area
     * @param integer $drawAreaHeight Height of the max drawing area
     *
     * @return bool Return true when image destroyed, false when fail
     */
    public function resize(
        $width,
        $height,
        $resizeSmall = false,
        $drawAreaWidth = 0,
        $drawAreaHeight = 0
    ) {
        $transparent = $cutPosX = $cutPosY = 0;
        $newImgWidth = $drawAreaWidth ? $drawAreaWidth : $width;
        $newImgHeight = $drawAreaHeight ? $drawAreaHeight : $height;

        if ($this->imageRes) {
            if (!$resizeSmall
                && ($this->imageInfo['Width'] <= $width
                || $this->imageInfo['Height'] <= $height)) {
                return true;
            }

            if ($newImg = imagecreatetruecolor($newImgWidth, $newImgHeight)) {
                $old_ratio = $this->imageInfo['Width'] / $this->imageInfo['Height'];
                $new_ratio = $newImgWidth / $newImgHeight;

                if ($old_ratio > $new_ratio) {
                    $cutPosX = ceil(
                        ($this->imageInfo['Width'] - ($this->imageInfo['Height'] * $new_ratio)) / 2
                    );
                    $cutPosY = 0;
                } else {
                    $cutPosX = 0;
                    $cutPosY = ceil(
                        ($this->imageInfo['Height'] - ($this->imageInfo['Width'] / $new_ratio)) /2
                    );
                }

                if (isset($this->imageInfo['Transparent'])) {
                    // Make it transparent when the origin picture is transparent
                    imagealphablending($newImg, true);
                    imagesavealpha($newImg, true);

                    $transparent = $this->imageInfo['Transparent'];
                }

                if (imagefilledrectangle(
                    $newImg,
                    0,
                    0,
                    $width,
                    $height,
                    $transparent
                )
                &&
                imagecopyresampled(
                    $newImg,
                    $this->imageRes,
                    0,
                    0,
                    $cutPosX,
                    $cutPosY,
                    $width,
                    $height,
                    $this->imageInfo['Width'],
                    $this->imageInfo['Height']
                )) {
                    imagedestroy($this->imageRes); // Remove the org image file res

                    $this->imageRes = $newImg; // Replace the old image res with new one

                    $this->imageInfo['Width'] = $newImgWidth; // Renew the size info
                    $this->imageInfo['Height'] = $newImgHeight;

                    if (isset($this->imageInfo['Transparent'])) {
                        $this->imageInfo['Transparent'] = imagecolorallocatealpha(
                            $newImg,
                            255,
                            255,
                            255,
                            127
                        );
                    }

                    return true;
                }
            }
        } else {
            $this->error = 'ERROR_IMAGE_HANDLER_IMAGE_NOTLOAD';
        }

        return false;
    }

    /**
     * Resize the image in ratio
     *
     * @param integer $width Max width of the image
     * @param integer $height Max Height of the image
     * @param integer $resizeSmall Resize the image even it's size small than resulting
     *
     * @return bool Return true when image destroyed, false when fail
     */
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

            return $this->resize(
                $this->imageInfo['Width'] * $ratio,
                $this->imageInfo['Height'] * $ratio,
                $resizeSmall
            );
        }

        return false;
    }

    /**
     * Full the image with specified size
     *
     * @param integer $width Max width of the image
     * @param integer $height Max Height of the image
     *
     * @return bool Return the result if self::resize when success, or false for fail
     */
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

            return $this->resize(
                $this->imageInfo['Width'] * $ratio,
                $this->imageInfo['Height'] * $ratio,
                true,
                $width,
                $height
            );
        }

        return false;
    }

    /**
     * Blur the current image resource
     *
     * @param integer $level Level of blur
     *
     * @return bool Return true when image success blured, false otherwise
     */
    public function blur($level = 1)
    {
        $gaussian = array(
            array(2.0, 3.0, 2.0),
            array(3.0, 6.0, 3.0),
            array(2.0, 3.0, 2.0)
        );
        $div = array_sum(array_map('array_sum', $gaussian));

        if ($this->imageRes) {
            for ($i = 0; $i < $level; $i++) {
                if (!imageconvolution(
                    $this->imageRes,
                    $gaussian,
                    $div,
                    0
                )) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Sharpen the current image resource
     *
     * @param integer $level Level of sharp
     *
     * @return bool Return true when image success blured, false otherwise
     */
    public function sharp($level = 1)
    {
        $shape = array(
            array(0.0, -0.3, 0.0),
            array(-0.3, 6.0, -0.3),
            array(0.0, -0.3, 0.0)
        );
        $div = array_sum(array_map('array_sum', $shape));

        if ($this->imageRes) {
            for ($i = 0; $i < $level; $i++) {
                if (!imageconvolution(
                    $this->imageRes,
                    $shape,
                    $div,
                    0
                )) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Add image for water mark in to current image
     *
     * @param string $file Path to the image file
     * @param string $align Align type
     * @param integer $margin Align type
     *
     * @return bool Return the result if self::resize when success, or false for fail
     */
    public function waterMark($file, $align = 'center center', $margin = 0)
    {
        $watermark = $watermarkMarker = null;
        $markX = $markY = 0;
        $result = false;

        if ($this->imageRes) {
            if ($watermark = $this->openImage($file)) {
                if ($watermark['Info']['Width'] * 3 > $this->imageInfo['Width']
                    || $watermark['Info']['Height'] * 3 > $this->imageInfo['Height']) {
                    $this->error = 'ERROR_IMAGE_HANDLER_WATERMARK_TARGET_TOOSMALL';

                    return false;
                }

                list($markX, $markY) = $this->getAlignPos(
                    $align,
                    $this->imageInfo['Width'],
                    $this->imageInfo['Height'],
                    $watermark['Info']['Width'],
                    $watermark['Info']['Height'],
                    $margin
                );

                if (!$watermarkMarker = imagecreatetruecolor(
                    $watermark['Info']['Width'],
                    $watermark['Info']['Height']
                )) {
                    return false;
                }

                imagecopy(
                    $watermarkMarker,
                    $this->imageRes,
                    0,
                    0,
                    $markX,
                    $markY,
                    $watermark['Info']['Width'],
                    $watermark['Info']['Height']
                );

                imagecopy(
                    $watermarkMarker,
                    $watermark['Res'],
                    0,
                    0,
                    0,
                    0,
                    $watermark['Info']['Width'],
                    $watermark['Info']['Height']
                );

                if (imagecopymerge(
                    $this->imageRes,
                    $watermarkMarker,
                    $markX,
                    $markY,
                    0,
                    0,
                    $watermark['Info']['Width'],
                    $watermark['Info']['Height'],
                    100
                )) {
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

    /**
     * Add text for water mark in to current image
     *
     * @param string $text Text content of water mark
     * @param string $align Align type
     * @param integer $margin Align type
     * @param array $color R,G,B value of array
     * @param integer $size Font size instead of default one
     *
     * @return bool Return true when added, false for fail
     */
    public function waterMarkText(
        $text,
        $align = 'center center',
        $margin = 0,
        array $color = array(255, 255, 255, 128),
        $size = 0
    ) {
        $colorLayer = $fontColor = $shadowFontColor = null;
        $fontX = $fontY = 0;
        $fontBoxPos = array();
        $fontSize = $size ? (int)$size : $this->setting['FontSize'];

        $result = false;

        if ($this->imageRes) {
            if (!isset($this->setting['Font'][0]) || !$fontSize) {
                throw new \Exception("Font and FontSize must be set to use text watermark.");

                return false;
            }

            $fontBoxPos = imagettfbbox(
                $fontSize,
                0,
                $this->setting['Font'],
                $text
            );

            $fontWidth = abs($fontBoxPos[4] - $fontBoxPos[0]);
            $fontHeight = abs($fontBoxPos[5] - $fontBoxPos[1]);

            if ($fontWidth * 3 > $this->imageInfo['Width']
                || $fontHeight * 3 > $this->imageInfo['Height']) {
                $this->error = 'ERROR_IMAGE_HANDLER_WATERMARK_TARGET_TOOSMALL';

                return false;
            }

            list($fontX, $fontY) = $this->getAlignPos(
                $align,
                $this->imageInfo['Width'],
                $this->imageInfo['Height'],
                $fontWidth,
                $fontHeight,
                $margin
            );

            $fontY += $fontHeight; // imagettfbbox will align using baseline...

            if (!isset($color[3])) {
                $color[3] = 255;
            }

            // http://php.net/manual/en/function.imagecolorallocatealpha.php#106642 Thank you!
            $color[3] = ((~((int)$color[3])) & 0xff) >> 1;

            if (!isset($color[0], $color[1], $color[2])) {
                throw new \Exception("All Color setting must be set in array(R, G, B).");

                return false;
            }

            if ($colorLayer = imagecreatetruecolor($fontWidth, $fontHeight)) {
                if (isset($this->imageInfo['Transparent'])) {
                    imagesavealpha($colorLayer, true);
                    imagealphablending($colorLayer, false);

                    $alphaWhite = imagecolorallocatealpha(
                        $colorLayer,
                        255,
                        255,
                        255,
                        127
                    );

                    imagefill($colorLayer, 0, 0, $alphaWhite);
                }

                $fontColor = imagecolorallocatealpha(
                    $colorLayer,
                    $color[0],
                    $color[1],
                    $color[2],
                    $color[3]
                );
                $shadowFontColor = imagecolorallocatealpha($colorLayer, 0, 0, 0, $color[3]);

                if ($fontColor !== false && $shadowFontColor !== false) {
                    if (imagettftext(
                        $this->imageRes,
                        $fontSize,
                        0,
                        $fontX + 1,
                        $fontY + 1,
                        $shadowFontColor,
                        $this->setting['Font'],
                        $text
                    )
                    &&
                    imagettftext(
                        $this->imageRes,
                        $fontSize,
                        0,
                        $fontX,
                        $fontY,
                        $fontColor,
                        $this->setting['Font'],
                        $text
                    )) {
                        $result = true;
                    }

                    imagedestroy($colorLayer);

                    return $result;
                }
            }
        } else {
            $this->error = 'ERROR_IMAGE_HANDLER_IMAGE_NOTLOAD';
        }

        return false;
    }

    /**
     * Save image to disk
     *
     * @param string $file The path of the file will be save to
     *
     * @return bool Return true when saved, false for fail
     */
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
