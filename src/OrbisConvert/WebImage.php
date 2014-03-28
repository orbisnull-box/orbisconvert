<?php
/**
 * User: orbisnull
 * Date: 08.09.13
 */

namespace OrbisConvert;

use DeltaUtils\FileConverter;

class WebImage
{
    protected $fileConverter;

    /**
     * @return FileConverter
     */
    public function getFileConverter()
    {
        if (is_null($this->fileConverter)) {
            $this->fileConverter = new FileConverter();
        }
        return $this->fileConverter;
    }

    public static function getDimension($file)
    {
        $info = getimagesize($file);
        return ['width' => $info[0], 'height' => $info[1], 'imageType' => $info[2]];
    }

    function resizeToWidth($fileInput, $fileOutput, $width)
    {
        $command = "convert #INPUT# -resize {$width} #OUTPUT#";
        return $this->getFileConverter()->convert($fileInput, $command, $fileOutput);
    }

    function resizeToHeight($fileInput, $fileOutput, $height)
    {
        $command = "convert #INPUT# -resize x{$height} #OUTPUT#";
        return $this->getFileConverter()->convert($fileInput, $command, $fileOutput);
    }

    function scale($fileInput, $fileOutput, $percent)
    {
        $command = "convert #INPUT# -resize {$percent}% #OUTPUT#";
        return $this->getFileConverter()->convert($fileInput, $command, $fileOutput);
    }

    function resize($fileInput, $fileOutput, $width, $height)
    {
        $command = "convert #INPUT# -resize {$width}x{$height} #OUTPUT#";
        return $this->getFileConverter()->convert($fileInput, $command, $fileOutput);
    }

    public function resizeToMax($fileInput, $fileOutput, $sideSize)
    {
        $command = "convert #INPUT# -resize {$sideSize}x{$sideSize} #OUTPUT#";
        return $this->getFileConverter()->convert($fileInput, $command, $fileOutput);
    }

    public function resizeAndScale($fileInput, $fileOutput, $width, $height = null)
    {
        $imgSize = self::getDimension($fileInput);
        if (is_null($height)) {
            $height = $width;
        }
        $sharpen = ($imgSize['imgType'] === IMAGETYPE_PNG ) ? '' : '-unsharp 0x1';
        if (($width / $imgSize['width']) >= ($height / $imgSize['height'])) {
            $command = "convert {$fileInput} -resize {$width}x -gravity center -crop {$width}x{$height}+0+0 {$sharpen} +repage {$fileOutput}";
        } else {
            $command = "convert {$fileInput} -resize x{$height} -gravity center -crop {$width}x{$height}+0+0 {$sharpen} +repage {$fileOutput}";
        }
        $result =  $this->getFileConverter()->convert($fileInput, $command, $fileOutput);
        return $result;
    }

    public function resizeAndCrop($fileInput, $fileOutput, $width, $height)
    {
        $command = "convert #INPUT# -resize {$width}x{$height}^ \\
          -gravity center -extent {$width}x{$height} #OUTPUT#";
        return $this->getFileConverter()->convert($fileInput, $command, $fileOutput);
    }

    public function origin($fileInput, $fileOutput)
    {
        $command = "ln -s #INPUT# #OUTPUT#";
        return $this->getFileConverter()->exec($fileInput, $command, $fileOutput);
    }

    public function optimize($file)
    {
        if (is_link($file)) {
            return false;
        }
        $imgSize = self::getDimension($file);
        switch ($imgSize['imageType']) {
            case IMAGETYPE_JPEG :
                $command = "jpegoptim --strip-all {$file}";
                break;
            case IMAGETYPE_PNG:
                $command = "optipng  {$file}";
                break;
            default :
                return true;
        }

        $result =  $this->getFileConverter()->convert($file, $command);
        return $result;
    }

}