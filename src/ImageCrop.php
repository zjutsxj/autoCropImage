<?php
namespace yimao\autoCropImage;

/**
 * 调用方法：
 *
 * $ic=new ImageCrop('old.jpg','afterCrop.jpg');
 * $ic->Crop(120,80,2);
 * $ic->SaveImage();
 * //$ic->SaveAlpha();将补白变成透明像素保存
 * $ic->destory();
 */
class ImageCrop
{

    public $sImage;
    public $dImage;
    public $src_file;
    public $dst_file;
    public $src_width;
    public $src_height;
    public $src_ext;
    public $src_type;

    public function __construct($src_file, $dst_file = '')
    {
        $this->src_file = $src_file;
        $this->dst_file = $dst_file;
        if (!$dst_file) {
            $this->dst_file = $this->src_file;
        }

    }

    public function SetSrcFile($src_file)
    {
        $this->src_file = $src_file;
    }

    public function SetDstFile($dst_file)
    {
        $this->dst_file = $dst_file;
    }

    public function LoadImage()
    {
        list($this->src_width, $this->src_height, $this->src_type) = getimagesize($this->src_file);
        if (!$this->src_width || !$this->src_height || !$this->src_type) {
            return false;
        }
        switch ($this->src_type) {
            case IMAGETYPE_JPEG:
                $this->sImage = @imagecreatefromjpeg($this->src_file);
                $this->ext = 'jpg';
                break;
            case IMAGETYPE_PNG:
                $this->sImage = @imagecreatefrompng($this->src_file);
                $this->ext = 'png';
                break;
            case IMAGETYPE_GIF:
                $this->sImage = @imagecreatefromgif($this->src_file);
                $this->ext = 'gif';
                break;
            default:
                break;
        }
        return $this->sImage && is_resource($this->sImage) ? true : false;
    }

    public function SaveImage($fileName = '')
    {
        $this->dst_file = $fileName ? $fileName : $this->dst_file;
        if ($this->dImage && is_resource($this->dImage)) {
            switch ($this->src_type) {
                case IMAGETYPE_JPEG:
                    @imagejpeg($this->dImage, $this->dst_file, 100);
                    break;
                case IMAGETYPE_PNG:
                    @imagepng($this->dImage, $this->dst_file);
                    break;
                case IMAGETYPE_GIF:
                    @imagegif($this->dImage, $this->dst_file);
                    break;
                default:
                    break;
            }
        }
    }

    public function OutImage()
    {
        if ($this->dImage && is_resource($this->dImage)) {
            switch ($this->src_type) {
                case IMAGETYPE_JPEG:
                    header('Content-type: image/jpeg');
                    @imagejpeg($this->dImage);
                    break;
                case IMAGETYPE_PNG:
                    header('Content-type: image/png');
                    @imagepng($this->dImage);
                    break;
                case IMAGETYPE_GIF:
                    header('Content-type: image/gif');
                    @imagegif($this->dImage);
                    break;
                default:
                    break;
            }
        }
    }

    public function SaveAlpha($fileName = '')
    {
        $this->dst_file = $fileName ? $fileName : $this->dst_file;
        if ($this->dImage && is_resource($this->dImage)) {
            @imagesavealpha($this->dImage, true);
            @imagepng($this->dImage, $this->dst_file);
        }
    }

    public function OutAlpha()
    {
        if ($this->dImage && is_resource($this->dImage)) {
            @imagesavealpha($this->dImage, true);
            header('Content-type: image/png');
            @imagepng($this->dImage);
        }
    }

    public function destory()
    {
        if ($this->sImage && is_resource($this->sImage)) {
            @imagedestroy($this->sImage);
        }

        if ($this->dImage && is_resource($this->dImage)) {
            @imagedestroy($this->dImage);
        }

    }

    /**
     * 创建Image资源
     */
    public function &createImage($width, $height)
    {
        $im = @imagecreatetruecolor($width, $height);
        if (!$im || !is_resource($im)) {
            return false;
        }

        $bg = @imagecolorallocatealpha($im, 255, 255, 255, 127);
        @imagefill($im, 0, 0, $bg);
        @imagecolortransparent($im, $bg);
        return $im;
    }

    public function Crop($dst_width, $dst_height, $mode = 1, $dst_file = '')
    {
        // 判断是否需要裁减：
        if ($dst_width < 1 || $dst_height < 1) {
            return false;
        }

        list($this->src_width, $this->src_height, $this->src_type) = getimagesize($this->src_file);
        if ($this->src_width == $dst_width && $this->src_height == $dst_height) {
            if ($this->src_file == $this->dst_file) {
                return true;
            } else {
                // 复制一份文件：
                return @copy($this->src_file, $this->dst_file);
            }
        }
        $this->LoadImage();
        if ($dst_file) {
            $this->dst_file = $dst_file;
        }

        $ratio_w = 1.0 * $dst_width / $this->src_width;
        $ratio_h = 1.0 * $dst_height / $this->src_height;
        $ratio = 1.0;
        switch ($mode) {
            case 1: // always crop
                $this->dImage = $this->createImage($dst_width, $dst_height);
                if (!$this->dImage) {return false;} // failed
                if (($ratio_w < 1 && $ratio_h < 1) || ($ratio_w > 1 && $ratio_h > 1)) {
                    $ratio = $ratio_w < $ratio_h ? $ratio_h : $ratio_w;
                    $tmp_w = (int) ($dst_width / $ratio);
                    $tmp_h = (int) ($dst_height / $ratio);
                    $tmp_img = @imagecreatetruecolor($tmp_w, $tmp_h);
                    $src_x = abs(($this->src_width - $tmp_w) / 2);
                    $src_y = abs(($this->src_height - $tmp_h) / 2);
                    @imagecopy($tmp_img, $this->sImage, 0, 0, $src_x, $src_y, $tmp_w, $tmp_h);
                    @imagecopyresampled($this->dImage, $tmp_img, 0, 0, 0, 0, $dst_width, $dst_height, $tmp_w, $tmp_h);
                    @imageinterlace($this->dImage, 1);
                    @imagedestroy($tmp_img);
                } else {
                    $ratio = $ratio_w < $ratio_h ? $ratio_h : $ratio_w;
                    $tmp_w = (int) ($this->src_width * $ratio);
                    $tmp_h = (int) ($this->src_height * $ratio);
                    $tmp_img = @imagecreatetruecolor($tmp_w, $tmp_h);
                    @imagecopyresampled($tmp_img, $this->sImage, 0, 0, 0, 0, $tmp_w, $tmp_h, $this->src_width, $this->src_height);
                    @imageinterlace($tmp_img, 1);
                    $src_x = abs($tmp_w - $dst_width) / 2;
                    $src_y = abs($tmp_h - $dst_height) / 2;
                    @imagecopy($this->dImage, $tmp_img, 0, 0, $src_x, $src_y, $dst_width, $dst_height);
                    @imagedestroy($tmp_img);
                }
                break;
            case 2: // only small
                $this->dImage = $this->createImage($dst_width, $dst_height);
                if (!$this->dImage) {return false;} // failed
                if ($ratio_w < 1 && $ratio_h < 1) {
                    $ratio = $ratio_w < $ratio_h ? $ratio_h : $ratio_w;
                    $tmp_w = (int) ($dst_width / $ratio);
                    $tmp_h = (int) ($dst_height / $ratio);
                    $tmp_img = @imagecreatetruecolor($tmp_w, $tmp_h);
                    $src_x = (int) ($this->src_width - $tmp_w) / 2;
                    $src_y = (int) ($this->src_height - $tmp_h) / 2;
                    @imagecopy($tmp_img, $this->sImage, 0, 0, $src_x, $src_y, $tmp_w, $tmp_h);
                    @imagecopyresampled($this->dImage, $tmp_img, 0, 0, 0, 0, $dst_width, $dst_height, $tmp_w, $tmp_h);
                    @imageinterlace($this->dImage, 1);
                    @imagedestroy($tmp_img);
                } elseif ($ratio_w > 1 && $ratio_h > 1) {
                    $dst_x = (int) abs($dst_width - $this->src_width) / 2;
                    $dst_y = (int) abs($dst_height - $this->src_height) / 2;
                    @imagecopy($this->dImage, $this->sImage, $dst_x, $dst_y, 0, 0, $this->src_width, $this->src_height);
                } else {
                    $src_x = 0;
                    $dst_x = 0;
                    $src_y = 0;
                    $dst_y = 0;
                    if (($dst_width - $this->src_width) < 0) {
                        $src_x = (int) ($this->src_width - $dst_width) / 2;
                        $dst_x = 0;
                    } else {
                        $src_x = 0;
                        $dst_x = (int) ($dst_width - $this->src_width) / 2;
                    }

                    if (($dst_height - $this->src_height) < 0) {
                        $src_y = (int) ($this->src_height - $dst_height) / 2;
                        $dst_y = 0;
                    } else {
                        $src_y = 0;
                        $dst_y = (int) ($dst_height - $this->src_height) / 2;
                    }
                    @imagecopy($this->dImage, $this->sImage, $dst_x, $dst_y, $src_x, $src_y, $this->src_width, $this->src_height);
                }
                break;
            case 3: // keep all image size and create need size
                $this->dImage = $this->createImage($dst_width, $dst_height);
                if (!$this->dImage) {return false;} // failed
                if ($ratio_w > 1 && $ratio_h > 1) {
                    $dst_x = (int) (abs($dst_width - $this->src_width) / 2);
                    $dst_y = (int) (abs($dst_height - $this->src_height) / 2);
                    @imagecopy($this->dImage, $this->sImage, $dst_x, $dst_y, 0, 0, $this->src_width, $this->src_height);
                } else {
                    $ratio = $ratio_w > $ratio_h ? $ratio_h : $ratio_w;
                    $tmp_w = (int) ($this->src_width * $ratio);
                    $tmp_h = (int) ($this->src_height * $ratio);
                    $tmp_img = @imagecreatetruecolor($tmp_w, $tmp_h);
                    // PNG 黑色背景问题
                    if ($this->src_type === IMAGETYPE_PNG) {
                        @imagealphablending($tmp_img, false);
                        @imagesavealpha($tmp_img, true);
                    }
                    @imagecopyresampled($tmp_img, $this->sImage, 0, 0, 0, 0, $tmp_w, $tmp_h, $this->src_width, $this->src_height);
                    @imageinterlace($tmp_img, 1);
                    $dst_x = (int) (abs($tmp_w - $dst_width) / 2);
                    $dst_y = (int) (abs($tmp_h - $dst_height) / 2);
                    @imagecopy($this->dImage, $tmp_img, $dst_x, $dst_y, 0, 0, $tmp_w, $tmp_h);
                    @imagedestroy($tmp_img);
                }
                break;
            case 4: // keep all image but create actually size
                if ($ratio_w > 1 && $ratio_h > 1) {
                    $this->dImage = $this->sImage; // do nothing!
                } else {
                    $ratio = $ratio_w > $ratio_h ? $ratio_h : $ratio_w;
                    $tmp_w = (int) ($this->src_width * $ratio);
                    $tmp_h = (int) ($this->src_height * $ratio);
                    $this->dImage = @imagecreatetruecolor($tmp_w, $tmp_h);
                    @imagecopyresampled($this->dImage, $this->sImage, 0, 0, 0, 0, $tmp_w, $tmp_h, $this->src_width, $this->src_height);
                    @imageinterlace($this->dImage, 1);
                }
                break;
            case 5: // if dst > src , crop , if (dst < src) crop fixed ratio
                $ratio = $ratio_w < $ratio_h ? $ratio_h : $ratio_w;
                $tmp_w = (int) ($dst_width / $ratio);
                $tmp_h = (int) ($dst_height / $ratio);
                $src_x = floor(abs(($this->src_width - $tmp_w) / 2));
                $src_y = floor(abs(($this->src_height - $tmp_h) / 2));
                if (($ratio_w < 1 && $ratio_h < 1) || ($ratio_w > 1 && $ratio_h > 1)) {
                    if ($ratio_w < 1 && $ratio_h < 1) {
                        $tmp_img = imagecreatetruecolor($tmp_w, $tmp_h);
                        $this->dImage = imagecreatetruecolor($dst_width, $dst_height);
                        imagecopy($tmp_img, $this->sImage, 0, 0, $src_x, $src_y, $tmp_w, $tmp_h);
                        imagecopyresampled($this->dImage, $tmp_img, 0, 0, 0, 0, $dst_width, $dst_height, $tmp_w, $tmp_h);
                        @imageinterlace($this->dImage, 1);
                        imagedestroy($tmp_img);
                    } elseif ($ratio_w > 1 && $ratio_h > 1) {
                        $this->dImage = @imagecreatetruecolor($tmp_w, $tmp_h);
                        @imagecopy($this->dImage, $this->sImage, 0, 0, $src_x, $src_y, $tmp_w, $tmp_h);
                    }
                } else {
                    $this->dImage = @imagecreatetruecolor($tmp_w, $tmp_h);
                    @imagecopy($this->dImage, $this->sImage, 0, 0, $src_x, $src_y, $this->src_width, $this->src_height);
                }
                break;
        }
        return $this->dImage && is_resource($this->dImage);
    } // end Crop

}
