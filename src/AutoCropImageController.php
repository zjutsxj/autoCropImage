<?php
namespace yimao\autoCropImage;

use think\facade\Config;
use think\facade\Env;
use yimao\autoCropImage\autoCropImage;

class AutoCropImageController
{
    public function index()
    {
        $config = Config::pull('thumb'); // 获取缩略图配置
        $root_path = Env::get('root_path'); // root_path 项目根路径
        $root_path = rtrim($root_path, '/,\\'); // 去掉右侧 /
        $root_path .= '/public'; //

        /* 初始化 */
        $options = [
            'default_mode' => $config['default_mode'],
            'default_version' => $config['default_version'],
            'cache_time' => $config['cache_time'],
        ];
        $autoCropImage = new autoCropImage($options);

        /* 设置头信息 */
        $autoCropImage->set_header();

        /* 获取宽高、缩放模式和版本 */
        list($width, $height, $mode, $versions) = $autoCropImage->width_height_mode_versions();

        /* 获取文件路径 */
        $path = $autoCropImage->path();
        $path = ltrim($path, '/,\\'); // 去掉左侧 /
        //Log::record($path);

        /* 源文件 */
        $old = $root_path . '/' . $config['images_dir'] . $path;
        //Log::record($old);

        // 'thumb_dir' => '/uploads/thumb/%1$sx%2$s_mode%3$s/%5$s/%6$s',
        $thumb_dir = $root_path . $config['thumb_dir'];
        /* 指定规格文件 */
        $new = sprintf($thumb_dir, $width, $height, $mode, dirname($path), basename($path));
        //Log::record($new);
        //Log::save();

        /* 存在源文件 */
        if (file_exists($old)) {
            /* 不存指定规格文件夹 */
            if (!file_exists(dirname($new))) {
                $autoCropImage->mk_dir(dirname($new));
            }
            /* 不存指定规格文件 */
            if (!file_exists($new)) {
                /* 生成并输出图片 */
                $autoCropImage->make_crop_thumb($old, $new, $width, $height, $mode);
            }
            file_exists($new) && $autoCropImage->show_pic($new) && exit();
        }
        /* 其它处理 */
        $autoCropImage->show_not_found();
    }
}
