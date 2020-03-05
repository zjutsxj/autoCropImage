<?php
namespace yimao\autoCropImage;

use yimao\autoCropImage\ImageCrop;

/**
 * autoCropImage - 图片自动缩放程序
 *
 * @link https://github.com/mingfunwong/autoCropImage
 * @license http://opensource.org/licenses/MIT
 * @author Mingfun Wong <mingfun.wong.chn@gmail.com>
 */
class autoCropImage
{

    /* 默认缩放模式
     * mode 1 : 强制裁剪，生成图片严格按照需要，不足放大，超过裁剪，图片始终铺满
     * mode 2 : 和1类似，但不足的时候 不放大 会产生补白，可以用png消除。
     * mode 3 : 只缩放，不裁剪，保留全部图片信息，会产生补白，
     * mode 4 : 只缩放，不裁剪，保留全部图片信息，此时的参数只是限制了生成的图片的最大宽高，不产生补白
     * mode 5 : 生成的图比例严格按照需要的比例，宽和高不超过给定的参数。
     */
    private $default_mode = 1;

    /* 默认版本 */
    private $default_version = 1;

    /* header 缓存时长 */
    private $cache_time = '10 years';

    public function __construct($options)
    {
        if (isset($options['default_mode'])) {
            $this->default_mode = $options['default_mode'];
        }

        if (isset($options['default_version'])) {
            $this->default_version = $options['default_version'];
        }

        if (isset($options['cache_time'])) {
            $this->cache_time = $options['cache_time'];
        }
    }

    /**
     * 生成并输出图片
     * @access public
     * @param mixed $src
     * @param mixed $dst
     * @param mixed $width
     * @param mixed $height
     * @param mixed $mode
     * @return void
     */
    public function make_crop_thumb($src, $dst, $width, $height, $mode)
    {
        $ic = new ImageCrop($src, $dst);
        $ic->Crop($width, $height, $mode);
        list($width, $height, $type) = getimagesize($src);
        if ($type === IMAGETYPE_PNG) {
            $ic->SaveAlpha();
        } else {
            $ic->SaveImage();
        }

        $ic->destory();
    }

    /**
     * 设置头信息
     *
     * @access public
     * @return void
     */
    public function set_header()
    {
        header('Expires: ' . date('D, j M Y H:i:s', strtotime('now + ' . $this->cache_time)) . ' GMT');
        $etag = md5(serialize($this->from($_SERVER, 'QUERY_STRING')));
        if ($this->from($_SERVER, 'HTTP_IF_NONE_MATCH') === $etag) {
            header('Etag:' . $etag, true, 304);
            exit;
        } else {
            header('Etag:' . $etag);
        }
    }

    /**
     * 获取请求路径
     *
     * @access public
     * @return string
     */
    public function path()
    {
        $path = $this->_str_replace_once($this->_str_replace_once('thumb.html', '', $this->from($_SERVER, 'SCRIPT_NAME')), '', $this->_str_replace_once('?' . $this->from($_SERVER, 'QUERY_STRING'), '', $this->from($_SERVER, 'REQUEST_URI')));
        return preg_replace('/(?:_)([0-9]+)x([0-9]+)(?:m([1-5]))?(?:v([A-Za-z0-9_]*))?(?:.)?(?:gif|jpg|png|GIF|JPG|PNG)?$/', '', $path);
    }

    /**
     * 子字符串替换一次
     *
     * @access public
     * @param string $needle
     * @param string $replace
     * @param string $haystack
     * @return string
     */
    public function _str_replace_once($needle, $replace, $haystack)
    {
        $pos = strpos($haystack, $needle);
        if ($pos === false) {
            return $haystack;
        }
        return substr_replace($haystack, $replace, $pos, strlen($needle));
    }

    /**
     * 获取宽高、缩放模式和版本
     *
     * @access public
     * @return array($width, $height, $mode, $versions)
     */
    public function width_height_mode_versions()
    {
        if ($request_uri = $this->from($_SERVER, 'REQUEST_URI')) {
            if (preg_match('/(?:gif|jpg|png|GIF|JPG|PNG)(?:_)([0-9]+)x([0-9]+)(?:m([1-5]))?(?:v([A-Za-z0-9_]*))?(?:.)?(?:gif|jpg|png|GIF|JPG|PNG)?$/', $request_uri, $match)) {
                if ($this->from($match, 1) && $this->from($match, 2)) {
                    return array(
                        $match[1],
                        $match[2],
                        $this->from($match, 3, $this->default_mode, true),
                        $this->from($match, 4, $this->default_version, true),
                    );
                }
            }
        }
        if ($query_string = $this->from($_SERVER, 'QUERY_STRING')) {
            if (preg_match('/^([0-9]+)x([0-9]+)(?:m([1-5]))?(?:v([A-Za-z0-9_]*))?$/', $query_string, $match)) {
                if ($this->from($match, 1) && $this->from($match, 2)) {
                    return array($match[1], $match[2], $this->from($match, 3, $this->default_mode, true), $this->from($match, 4, $this->default_version, true));
                }
            }
        }
        return $this->show_not_found();
    }

    /**
     * 输出图片
     *
     * @access public
     * @param mixed $file
     * @return void
     */
    public function show_pic($file)
    {
        $info = getimagesize($file);
        header("Content-Type: {$info['mime']}");
        readfile($file);
        exit();
    }

    /**
     * 404 Not Found 输出
     *
     * @access public
     * @return void
     */
    public function show_not_found()
    {
        header($this->from($_SERVER, 'SERVER_PROTOCOL') . ' 404 Not Found');
        // 生成图片
        $img = imagecreate(100, 100);
        imagecolorallocate($img, 0xff, 0xff, 0xff);
        $size = 12;
        $color = imagecolorallocate($img, 0xcc, 0xcc, 0xcc);
        $font = "./static/fonts/image.ttf";
        $string = 'No image';

        imagettftext($img, $size, 0, 10, 56, $color, $font, $string);
        header('Content-Type: image/gif');
        imagegif($img);
        exit;
    }

    /**
     * 递归创建目录
     *
     * @access public
     * @param mixed $dir
     * @param int $mode
     * @return bool
     */
    public function mk_dir($dir, $mode = 0755)
    {
        if (is_dir($dir) || @mkdir($dir, $mode, true)) {
            return true;
        }

        if (!$this->mk_dir(dirname($dir), $mode)) {
            return false;
        }

        return @mkdir($dir, $mode, true);
    }

    /**
     * 获得数组指定键的值
     *
     * @access public
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @param bool $check_empty
     * @return mixed
     */
    public function from($array, $key, $default = false, $check_empty = false)
    {
        return (isset($array[$key]) === false or ($check_empty === true && empty($array[$key])) === true) ? $default : $array[$key];
    }
}
