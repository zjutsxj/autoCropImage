# autoCropImage - 图片自动缩放程序

基于 ThinkPHP5.1+ 的图片缩放程序  
将图片自动缩放成指定大小，减少图片体积，从而加快下载速度，降低下载时间和成本。

## 安装
> composer require zjutsxj/think-autocropimage

## 使用方法
http://localhost/images/pic.jpg_50x100.jpg  
http://localhost/images/pic.jpg_50x100m2.jpg  
http://localhost/images/pic.jpg_50x100v2014.jpg  
http://localhost/images/pic.jpg_50x100m2v2014.jpg  

### 使用说明
方式一：50x100 缩放成 50x100 大小  
方式二：50x100m2 方式一并且使用mode 2  
方式三：50x100v2014 方式一并且指定缓存版本2014  
方式四：50x100m2v2014 方式一，使用mode 2并且指定缓存版本2014  

### 缩放模式说明
mode 1 : 强制裁剪，生成图片严格按照需要，不足放大，超过裁剪，图片始终铺满。  
mode 2 : 和1类似，但不足的时候 不放大 会产生补白，可以用png消除。  
mode 3 : 只缩放，不裁剪，保留全部图片信息，会产生补白。  
mode 4 : 只缩放，不裁剪，保留全部图片信息，此时的参数只是限制了生成的图片的最大宽高，不产生补白。  
mode 5 : 生成的图比例严格按照需要的比例，宽和高不超过给定的参数。 

## 环境要求
ThinkPHP5.1+

## 配置文件
~~~
直接在应用的 config 目录下面创建 thumb.php 文件
return [
    // %1$s 宽, %2$s 高, %3$s 模式, %4$s 目录, %5$s 文件名
    'thumb_dir' => '/uploads/thumb/%1$sx%2$s_mode%3$s/%4$s/%5$s',

    /* 默认缩放模式 */
    'default_mode' => 1,

    /* 默认版本 */
    'default_version' => 1,

    /* 默认图片目录
     * 设置后将可以：
     * 1. URL 减少使用路径 http://localhost/images/pic.jpg_50x100.jpg > http://localhost/pic.jpg_50x100.jpg
     * 2. URL 隐藏原大小图片路径
     */
    'images_dir' => '',

    /* header 缓存时长 */
    'cache_time' => '1 years',
];
~~~

## 简单用法
在 .htaccess 文件中添加以下代码
~~~
# 自动生成缩略图
RewriteRule ^.*(?:gif|jpg|jpeg|png|GIF|JPG|JPEG|PNG)(?:_)([0-9]+)x([0-9]+)(?:m([1-5]))?(?:v([A-Za-z0-9_]*))?(?:.)?(?:gif|jpg|jpeg|png|GIF|JPG|JPEG|PNG)?$ thumb.html [L,QSA]
~~~

存放缩略图目录权限修改为可写入  
使用浏览器访问  
http://localhost/images/pic.jpg、 
http://localhost/images/pic.jpg_50x100.jpg  
当第二个地址看见缩略图即安装成功  

## 参考资料 
该扩展是参考 autoCropImage 项目进行扩展的
autoCropImage 开源项目 https://github.com/mingfunwong/autoCropImage