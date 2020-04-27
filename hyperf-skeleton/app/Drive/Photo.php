<?php

namespace App\Drive;

/**
 * 图片上传处理类
 *
 * @package    modules
 * @subpackage Common
 * @author     suning <snsnsky@gmail.com>
 *
 * $Id$
 */

ini_set('memory_limit', '512M');

class Photo
{
    private function __construct()
    {

    }

    /**
     * 判断图片类型,并返回。支持jpg,png,bmp,gif
     *
     * @param string $path 图片路径
     * @return boolean|string   失败返回false,成功返回图片扩展名
     */
    public static function getImageType($path)
    {
        $type = exif_imagetype($path);
        switch ($type) {
            case IMAGETYPE_JPEG :
                return 'jpg';
                break;
            case IMAGETYPE_GIF :
                return 'gif';
                break;
            case IMAGETYPE_PNG :
                return 'png';
                break;
            case IMAGETYPE_BMP :
                return 'bmp';
                break;
            default :
                return false;
        }
    }

    /**
     * 验证图片上传，并返回对应的值。只支持单张图片验证
     *
     * @param array $files  图片文件上传变量
     * @param int   $width  图片限制的最小宽
     * @param int   $height 图片限制的最小高
     * @param int   $size   图片限制的最大大小，以字节（Byte）为单位
     *
     * @return array $result    返回验证结果（状态码、状态、原因、图片类型、图片尺寸、临时文件名）
     * $result = array(
     *        'code' => $code,                     //状态码
     *        'status' => $status,                 //状态
     *        'msg' => $code_info[$code],            //原因
     *        'image_type'=> $image_type,            //图片类型
     *        'image_width'=> $image_size[0],     //图片的宽
     *        'image_height'=> $image_size[1],    //图片的高
     *        'tmp_name'=> $files['tmp_name'],    //临时文件名
     *    );
     */
    public static function checkImage($files, $width = 0, $height = 0, $size = 0)
    {
        $code = 0;
        $code_info = [
            0 => '验证通过！',
            1 => '没有选择图片！',
            2 => '图片上传失败！',
            3 => '图片类型错误！',
            4 => '图片非法！',
            5 => '图片尺寸不符合！',
            6 => '图片超过程序限制大小!',
            7 => '图片超过服务器限制大小!',
            8 => '图片超过上传框限制大小!',
            9 => '找不到临时文件夹！',
            10 => '图片写入失败！',
        ];

        if (empty($files)) {
            $code = 1;
        } else {
            switch ($files['error']) {
                case 1 :    //上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值
                    $code = 7;
                    break;
                case 2 :    //上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值
                    $code = 8;
                    break;
                case 3 :    //文件只有部分被上传
                    $code = 2;
                    break;
                case 4 :    //没有文件被上传
                    $code = 1;
                    break;
                case 6 :    //找不到临时文件夹
                    $code = 9;
                    break;
                case 7 :    //文件写入失败
                    $code = 10;
                    break;
                default:
                    $code = 0;
                    break;
            }
        }

        if ($code == 0) {
            //图片的大小验证
            if ($size && $files['size'] > $size) {
                $code = 6;
            }
            //判断图片类型
            $image_type = self::getImageType($files['tmp_name']);
            if ($image_type == false) {
                $code = 3;
            }
            //得不到图片尺寸错误
            $image_size = getimagesize($files['tmp_name']);
            if (!$image_size) {
                $code = 4;
            } //图片尺寸不符合提供的最小尺寸
            elseif (($width && $image_size[0] < $width) || ($height && $image_size[1] < $height)) {
                $code = 5;
            }
        }
        //返回验证信息
        $status = ($code > 0) ? false : true;
        $result = [
            'code' => $code,
            'status' => $status,
            'msg' => $code_info[$code],
            'image_type' => $image_type,
            'image_width' => $image_size[0],
            'image_height' => $image_size[1],
            'tmp_name' => $files['tmp_name'],
        ];

        return $result;
    }

    /**
     * 验证网络图片，并返回对应的值。只支持单张图片验证
     *
     * @param string $url    网络图片路径
     * @param int    $width  图片限制的最小宽
     * @param int    $height 图片限制的最小高
     */
    public static function checkImageByUrl($url, $width = 0, $height = 0)
    {
        $code = 0;
        $code_info = [
            0 => '验证通过！',
            3 => '图片类型错误！',
            4 => '图片非法！',
            5 => '图片尺寸不符合！'
        ];
        //判断图片类型
        $image_type = self::getImageType($url);
        if ($image_type == false) {
            $code = 3;
        }
        //得不到图片尺寸错误
        $image_size = getimagesize($url);
        if (!$image_size) {
            $code = 4;
        } //图片尺寸不符合提供的最小尺寸
        elseif (($width && $image_size[0] < $width) || ($height && $image_size[1] < $height)) {
            $code = 5;
        }

        //返回验证信息
        $status = ($code > 0) ? false : true;
        $result = [
            'code' => $code,
            'status' => $status,
            'msg' => $code_info[$code],
            'image_type' => $image_type,
            'image_width' => $image_size[0],
            'image_height' => $image_size[1],
            'tmp_name' => $url,
        ];

        return $result;
    }

    /**
     * 获得hash值，用于拼接图片目录
     *
     * @param int $itemid hash的分割条件，关联的ID。默认按当前时间的年月日分割。
     *
     * @return array $path    返回hash数组
     */
    public static function getImageHash($itemid = 0)
    {
        if ($itemid) {
            $hash1 = sprintf("%02x", $itemid % 256);
            $hash2 = sprintf("%02x", $itemid / 256 % 256);
        } else {
            $time = time();
            $hash1 = date('Y', $time);
            $hash2 = date('m', $time);
            $hash3 = date('d', $time);
        }

        $path = [];
        $path['hash1'] = $hash1;
        $path['hash2'] = $hash2;
        if ($hash3) {
            $path['hash3'] = $hash3;
        }

        return $path;
    }

    /**
     * 创建一图像，并保存报对应目录
     *
     * @param array $image_cfg    图片上传配置
     *                            $image_cfg = array(
     *                            'base' => '/data/www/img_haodou/pic/recipe/',//必需。base设置图片上传的基础路径
     *                            'thumb' => array(
     *                            //必需，thumb下必须有一个尺寸的配置。thumb设置各个尺寸的详细配置。如宽、高、缩略图方式、起始位置、水印、水印方式、质量、详细路径等
     *                            'key1' => array(        //key自由设置
     *                            'width' => 400,        //新生成图片的宽
     *                            'height' => 400,    //新生成图片的高
     *                            'maxheight' => 400,    //新生成图片的最大的宽，未设置新生成图片的高的情况下有效
     *                            'proportion' => true,//是否按原图比例生成新图（true/false），默认为true
     *                            'position' => 'top',//生成新图时的裁剪起始位置（top上/middle中/buttom下），默认为top
     *                            'quality' => 80,    //生成新图的图片质量，默认为80
     *                            'watermark' => true,//是否需要水印（true/false），默认为false
     *                            'watermark_type' => '_waterMark' //生成水印的方法，有特殊需求的，需在上传类中自己添加，在这里配置方法名
     *                            'url' => '{hash1}/{hash2}/{id}_{extra}_1{ext}',//该尺寸图片保存的详细路径
     *                            'move' => true //是否直接上传图片，(true/false),默认为false
     *                            ),
     *                            'key2' => array(        //key自由设置
     *                            'width' => 400,        //新生成图片的宽
     *                            'height' => 400,    //新生成图片的高
     *                            'maxheight' => 400,    //新生成图片的最大的宽，未设置新生成图片的高的情况下有效
     *                            'proportion' => true,//是否按原图比例生成新图（true/false），默认为true
     *                            'position' => 'top',//生成新图时的裁剪起始位置（top上/middle中/buttom下），默认为top
     *                            'quality' => 80,    //生成新图的图片质量，默认为80
     *                            'watermark' => true,//是否需要水印（true/false），默认为false
     *                            'watermark_type' => '_waterMark' //生成水印的方法，有特殊需求的，需在上传类中自己添加，在这里配置方法名
     *                            'url' => '{hash1}/{hash2}/{id}_{extra}_2{ext}',//该尺寸图片保存的详细路径
     *                            'move' => true //是否直接上传图片，(true/false),默认为false
     *                            )
     *                            ),
     *                            'hash1' => $hash_arr['hash1'],     //替换详细路径的变量值
     *                            'hash2' => $hash_arr['hash2'],    //替换详细路径的变量值
     *                            'id' => $rid,                    //替换详细路径的变量值
     *                            'extra' => $randstr,            //替换详细路径的变量值
     *                            'ext' => '.jpg'                    //替换详细路径的变量值
     *                            );
     * @param array $image_status 图片验证信息
     *
     * @return bool|array $result    图像创建成功与否|图片上传成功与否
     * $result = array(
     * 'status' => true,    //所有尺寸的上传状态，全部成功为true，否则为false
     * 'thumb' => array(    //各尺寸的上传状态，成功为该尺寸配置文件中的详细路径，失败则为false
     * 'key1' => '{hash1}/{hash2}/{id}_{extra}_1{ext}'    //键名为配置中的key，键值为配置用的url替换后的值
     * 'key2' => '{hash1}/{hash2}/{id}_{extra}_2{ext}'    //键名为配置中的key，键值为配置用的url替换后的值
     * )
     * );
     */
    public static function imageResize($image_cfg, $image_status)
    {
        //从文件或URL中创建一图像
        switch ($image_status['image_type']) {
            case 'jpg' :
                $image = imagecreatefromjpeg($image_status['tmp_name']);
                break;
            case 'gif' :
                $image = imagecreatefromgif($image_status['tmp_name']);
                break;
            case 'png' :
                $image = imagecreatefrompng($image_status['tmp_name']);
                break;
            case 'bmp' :
                $image = self::imagecreatefrombmp($image_status['tmp_name']);
                break;
            default :
                $data = [
                    'error' => 'image_type error!!',
                    'info' => serialize([
                        'image_cfg' => $image_cfg,
                        'image_status' => $image_status,
                    ]),
                ];
                $date_path = date('Y/m');

                runtime_log("Upload/{$date_path}/error", $data);

                return false;
        }

        // 验证从文件或URL中创建图像是否成功
        if ($image == false) {
            $data = [
                'error' => 'image create error!!',
                'info' => serialize([
                    'image_cfg' => $image_cfg,
                    'image_status' => $image_status,
                ]),
            ];
            $date_path = date('Y/m');

            runtime_log("Upload/{$date_path}/error", $data);

            return false;
        }



        // 处理图片
        $result = self::_imageResize($image, $image_cfg, $image_status);

        return $result;
    }

    /**
     * 根据配置创建不同的图片，并移动到对应的目录
     *
     * @param resource $image        新创建的图像
     * @param array    $image_cfg    图片上传配置
     * @param array    $image_status 图片验证信息
     *
     * @return array $result        返回成功状态，和各图片的路径
     */
    private static function _imageResize($image, $image_cfg, $image_status)
    {
        // 唯一文件名
        $result = [
            'status' => true,
            'thumb' => [],
        ];
        $checkdir_array = [];
        foreach ($image_cfg['thumb'] as $key => $val) {
            if ($val['url'] && $val['url'] != false) {
                //裁剪图片已发生错误，统一返回各尺寸裁剪错误
                if (isset($result['status']) && $result['status'] == false) {
                    $result['thumb'][$key] = false;
                    continue;
                }

                //替换变量，得到最终保存的路径和名称
                $dest_path = $val['url'];
                foreach ($image_cfg as $cfg_key => $cfg_val) {
                    if (!in_array($cfg_key, ['base', 'thumb'])) {
                        $dest_path = str_replace("{" . $cfg_key . "}", $cfg_val, $dest_path);
                    }
                }


                //取'/'最后出现的位置
                $str_index = strrpos($dest_path, "/");
                //取需要保存的目录
                $dest_folder = substr($dest_path, 0, $str_index);
                //验证目录并创建，同一目录只验证并创建一次
                if (!isset($checkdir_array[$dest_folder])) {
                    $checkdir_array[$dest_folder] = true;
                    if (!is_dir($image_cfg['base'] . $dest_folder) && $dest_folder != './' && $dest_folder != '../') {
                        $dirname = $image_cfg['base'];
                        $folders = explode('/', $dest_folder);
                        foreach ($folders as $folder) {
                            $dirname .= $folder . '/';
                            if ($folder != '' && $folder != '.' && $folder != '..' && !is_dir($dirname)) {
                                mkdir($dirname, 0777, true);
                            }
                        }
                    }
                }
                //图片最终保存的路径
                $dest_path = $image_cfg['base'] . $dest_path;
                //保存成功状态
                $save_flag = false;
                //原图，则直接上传
                if ($val['move'] == true) {
                    $save_flag = move_uploaded_file($image_status['tmp_name'], $dest_path);
                } //其他则裁剪成对应尺寸
                else {
                    //图片类型
                    $image_type = $image_status['image_type'];
                    //原图的宽
                    $width = $image_status['image_width'];
                    //原图的高
                    $height = $image_status['image_height'];
                    //新图的宽和高
                    if (isset($val['width']) && isset($val['height'])) {
                        $new_width = $val['width'];
                        $new_height = $val['height'];
                    } elseif (isset($val['width']) && !isset($val['height'])) {
                        $new_width = ($image_status['image_width'] > $val['width']) ? $val['width'] : $image_status['image_width'];
                        $new_height = round(($new_width / $image_status['image_width']) * $image_status['image_height']);
                        if (isset($val['maxheight'])) {
                            $new_height = ($val['maxheight'] > $new_height) ? $new_height : $val['maxheight'];
                        }
                    } else {
                        $new_width = $image_status['image_width'];
                        $new_height = $image_status['image_height'];
                    }

                    //是否按比例裁剪，默认是
                    $proportion = (isset($val['proportion'])) ? $val['proportion'] : true;
                    //裁剪起始位置
                    $position = (isset($val['position'])) ? $val['position'] : 'top';
                    //生成的图片质量，默认80
                    $quality = (isset($val['quality'])) ? $val['quality'] : 85;
                    //是否需要水印，默认不需要
                    $watermark = (isset($val['watermark'])) ? $val['watermark'] : false;
                    //水印的方式，默认为网站LOGO
                    $watermark_type = (isset($val['watermark_type'])) ? $val['watermark_type'] : '_watermark';

                    //裁剪图片，并保存到对应目录
                    $save_flag = self::imageResizeSave($image_type, $image, $dest_path, $width, $height, $new_width, $new_height, $proportion, $position, $quality, $watermark, $watermark_type);
                }

                if ($save_flag === true) {
                    $result['thumb'][$key] = str_replace($image_cfg['base'], '', $dest_path);
                } else {
                    $result['status'] = false;
                    $result['thumb'][$key] = false;

                    $data = [
                        'error' => 'imageResizeSave error!!',
                        'info' => serialize([
                            'save_flag' => $save_flag,
                            'key' => $key,
                            'val' => $val,
                            'image_cfg' => $image_cfg,
                            'image_status' => $image_status,
                        ]),
                    ];
                    $date_path = date('Y/m');

                    runtime_log("Upload/{$date_path}/error", $data);
                }
            }
        }
        imagedestroy($image);

        return $result;
    }

    /**
     * 生成图片
     *
     * @param string   $image_type     图片类型
     * @param resource $image          图片对象
     * @param string   $dest_path      缩略图保存路径
     * @param int      $width          原图的宽
     * @param int      $height         原图的高
     * @param int      $new_width      缩略图的宽
     * @param int      $new_height     缩略图的高
     * @param boolean  $proportion     缩略图是否需要按比例，默认按比例
     * @param string   $position       缩略图位置，默认top(top截取上面、middle截取中间、buttom截取下面)
     * @param int      $quality        图片质量，默认80
     * @param boolean  $watermark      是否需要水印，默认不需要
     * @param string   $watermark_type 水印的方法，默认水印为LOGO，特殊水印需自己处理
     *
     * @return boolean                    返回生成图片的成功状态
     */
    public static function imageResizeSave($image_type, $image, $dest_path, $width, $height, $new_width, $new_height, $proportion = true, $position = 'top', $quality = 85, $watermark = false, $watermark_type = '_watermark')
    {
        $srcX = $srcY = 0;

        // 按比例生成图片，重新计算宽高和截取的起始位置
        if ($proportion == true) {
            // 原图尺寸小于缩略图尺寸
            if ($width <= $new_width && $height <= $new_height) {
                $new_width = $width;
                $new_height = $height;
            } // 原图的高大于宽
            else if ($height * $new_width > $width * $new_height) {
                $test_height = round($new_height * $width / $new_width);
                $srcX = 0;
                if ($position == 'middle') {
                    $srcY = round(($height - $test_height) / 2);
                } elseif ($position == 'buttom') {
                    $srcY = round($height - $test_height);
                }
                $height = $test_height;
            } // 原图的宽大于高
            else {
                $text_width = round($new_width * $height / $new_height);
                if ($position == 'middle') {
                    $srcX = round(($width - $text_width) / 2);
                } elseif ($position == 'buttom') {
                    $srcX = round($width - $text_width);
                }
                $srcY = 0;
                $width = $text_width;
            }
            if ($new_width < 1) {
                $new_width = 1;
            }
            if ($new_height < 1) {
                $new_height = 1;
            }
        }

        $image_color = imagecreatetruecolor($new_width, $new_height);
        $trans_colour = imagecolorallocate($image_color, 255, 255, 255);
        imagefill($image_color, 0, 0, $trans_colour);
        if (!imagecopyresampled($image_color, $image, 0, 0, $srcX, $srcY, $new_width, $new_height, $width, $height)) {
            return -1;
        }

        // 给图片信息加上水印信息
        if ($watermark == true) {
            if (method_exists('\Common\PhotoImage', $watermark_type)) {
                self::$watermark_type($image_color, $new_width, $new_height);
            } else {
                self::_watermark($image_color, $new_width, $new_height);
            }
        }

        // 输出图片信息到文件
        switch ($image_type) {
            case 'jpg' :
            case 'png' :
            case 'bmp' :
                if (imagejpeg($image_color, $dest_path, $quality)) {
                    imagedestroy($image_color);

                    return true;
                }
                break;
            case 'gif' :
                imagecolortransparent($image_color, imagecolorallocate($image_color, 0, 0, 0));
                if (imagegif($image_color, $dest_path)) {
                    imagedestroy($image_color);

                    return true;
                }
                break;
            default :
                return -2;
        }
        imagedestroy($image_color);

        return -3;
    }

    /**
     * 图片裁剪
     *
     * @param string   $image_type  图片类型
     * @param resource $image       图片对象
     * @param string   $dest_path   缩略图保存路径
     * @param int      $width       原图的宽
     * @param int      $height      原图的高
     * @param int      $crop_width  图片裁剪区域的宽
     * @param int      $crop_height 图片裁剪区域的高
     * @param int      $dst_w       裁剪后图片的宽,为0时默认为裁剪区域的宽$crop_width
     * @param int      $dst_h       裁剪后图片的高,为0时默认为裁剪区域的高$crop_height
     * @param int      $src_x       从原图载入的区域起始x坐标
     * @param int      $src_y       从原图载入的区域起始y坐标
     * @param int      $zoom_width  原图缩放后的宽,为0时表不进行缩放
     * @param int      $zoom_height 原图缩放后的高,为0时表不进行缩放
     * @param int      $quality     图片质量，默认80
     *
     * @return boolean                    返回生成图片的成功状态
     */
    public static function crop($image_type, $image, $dest_path, $width, $height, $crop_width, $crop_height, $dst_w = 0, $dst_h = 0, $src_x = 0, $src_y = 0, $zoom_width = 0, $zoom_height = 0, $quality = 85)
    {
        $xscale = 1;
        $yscale = 1;

        $src_w = $crop_width;
        $src_h = $crop_height;

        if ($zoom_width > 0) {
            $xscale = $width / $zoom_width;
            $src_x = round($src_x * $xscale);
            $src_w = round($xscale * $crop_width);
        }
        if ($zoom_height > 0) {
            $yscale = $height / $zoom_height;
            $src_y = round($src_y * $yscale);
            $src_h = round($yscale * $crop_height);
        }
        // 裁剪起始位置超出图片尺寸
        if ($src_x >= $width || $src_y >= $height) {
            return false;
        }
        // 确定目标图像大小
        $dst_w = $dst_w > 0 ? $dst_w : $crop_width;
        $dst_h = $dst_h > 0 ? $dst_h : $crop_height;

        $image_color = imagecreatetruecolor($dst_w, $dst_h);
        $trans_color = imagecolorallocate($image_color, 255, 255, 255);
        imagefill($image_color, 0, 0, $trans_color);
        if (!imagecopyresampled($image_color, $image, 0, 0, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)) {
            return false;
        }

        //输出图片信息到文件
        switch ($image_type) {
            case 'jpg' :
            case 'png' :
            case 'bmp' :
                if (imagejpeg($image_color, $dest_path, $quality)) {
                    imagedestroy($image_color);

                    return true;
                }
                break;
            case 'gif' :
                imagecolortransparent($image_color, imagecolorallocate($image_color, 0, 0, 0));
                if (imagegif($image_color, $dest_path)) {
                    imagedestroy($image_color);

                    return true;
                }
                break;
            default :
                return false;
        }
        imagedestroy($image_color);

        return false;
    }

    /**
     * 按指定角度旋转图片
     *
     * @param array   $image_cfg    图片上传配置
     * @param array   $image_status 图片验证信息
     * @param integer $angle        旋转角度(90: 逆时针旋转90度, -90: 顺时针旋转90度)
     *
     * @return boolean
     */
    public static function rotate($image_cfg, $image_status, $angle)
    {
        if (!is_numeric($angle)) {
            return false;
        }

        $image = false;
        //从文件或URL中创建一图像
        switch ($image_status['image_type']) {
            case 'jpg' :
                $image = imagecreatefromjpeg($image_status['tmp_name']);
                break;
            case 'gif' :
                $image = imagecreatefromgif($image_status['tmp_name']);
                break;
            case 'png' :
                $image = imagecreatefrompng($image_status['tmp_name']);
                break;
            case 'bmp' :
                $image = self::imagecreatefrombmp($image_status['tmp_name']);
                break;
            default :
                break;
        }

        // 验证从文件或URL中创建图像是否成功
        if ($image == false) {
            $date_path = date('Y/m');
            $log_path = "ImageRotate/{$date_path}/error";

            runtime_log($log_path, [
                'error' => 'image_type error!',
                'status' => serialize($image_status),
                'time' => date('Y-m-d H:i:s'),
            ]);

            return false;
        }

        $quality = 100;
        $rotate_result = true;
        $rotate = imagerotate($image, $angle, 0);
        $tempone = tempnam(ini_get('upload_tmp_dir'), 'img');

        // 输出图片信息到文件
        switch ($image_status['image_type']) {
            case 'jpg' :
            case 'png' :
            case 'bmp' :
                if (imagejpeg($rotate, $tempone, $quality)) {
                    imagedestroy($rotate);
                }
                break;
            case 'gif' :
                imagecolortransparent($rotate, imagecolorallocate($rotate, 0, 0, 0));
                if (imagegif($rotate, $tempone)) {
                    imagedestroy($rotate);
                }
                break;
            default :
                $rotate_result = false;
                break;
        }

        if ($rotate_result) {
            $image_file = [
                'name' => basename($tempone),
                'tmp_name' => $tempone,
                'error' => 0,
            ];
            $image_status2 = self::checkImage($image_file);
            $resize_result = self::imageResize($image_cfg, $image_status2);
            if (!$resize_result['status']) {
                $log_data = '旋转图片后裁剪出错: $image_cfg[' . serialize($image_cfg) . '], $image_status[' . serialize($image_status) . '], $resize_result[' . serialize($resize_result) . '] @ ' . date('Y-m-d H:i:s');
            }
        } else {
            $log_data = '旋转图片时出错: $image_cfg[' . serialize($image_cfg) . '], $image_status[' . serialize($image_status) . '] @ ' . date('Y-m-d H:i:s');
        }

        file_exists($tempone) && unlink($tempone);

        if (isset($log_data)) {
            if (!isset($log_path)) {
                $date_path = date('Y/m');
                $log_path = "ImageRotate/{$date_path}/error";
            }
            runtime_log($log_path, $log_data);

            return false;
        }

        return true;
    }

    /**
     * 检查是否是合格的图片检验结果
     *
     * @param array $image_status
     * @return bool
     */
    public static function isValidImageObject($image_status)
    {
        if (is_array($image_status)) {
            static $_required_fields = [
                'tmp_name' => true,
                'image_type' => true,
                'image_width' => true,
                'image_height' => true,
            ];

            $diff = array_diff_key($_required_fields, $image_status);

            if (empty($diff)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 生成水印
     *
     * @param object $im     图片资源
     * @param string $width  图片资源的宽度
     * @param string $height 图片资源的高度
     *
     * @return object|boolean        图片资源
     */
    private static function _watermark(& $im, $width, $height)
    {
        global $app;
        $water_path = dirname($app->cfg['path']['root']) . '/public/images3/waterimg_min.png';
        if (file_exists($water_path)) {
            // 加载水印图片
            $water = imagecreatefrompng($water_path);
            if ($water) {
                return imagecopy($im, $water, $width - 20 - 150, $height - 40 - 57, 0, 0, 150, 57);
            }
        }

        return false;
    }

    /**
     * 加透明层
     *
     * @param object $im     图片资源
     * @param string $width  图片资源的宽度
     * @param string $height 图片资源的高度
     *
     * @return object|boolean        图片资源
     */
    private static function _mergeMark(& $im, $width, $height)
    {
        global $app;
        in_array($width, array(48, 100)) ? $key = $width : $key = 100;
        $merge_path = dirname($app->cfg['path']['root']) . '/public/images/collect_base_' . $key . '.png';
        if (file_exists($merge_path)) {
            $merge = imagecreatefrompng($merge_path);
            imagecopy($im, $merge, 0, 0, 0, 0, $key, $key);
        }

        return false;
    }

    /**
     * 转换BMP为GD格式
     *
     * @param string $src  输入文件
     * @param string $dest 输出文件
     *
     * @return boolean         成功返回true,失败返回false
     */
    private static function ConvertBMP2GD($src, $dest)
    {
        if (!($src_f = fopen($src, "rb"))) {
            return false;
        }
        if (!($dest_f = fopen($dest, "wb"))) {
            return false;
        }

        $type = $offset = $width = $height = $bits = null;

        $header = unpack("vtype/Vsize/v2reserved/Voffset", fread($src_f, 14));
        $info = unpack("Vsize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vncolor/Vimportant", fread($src_f, 40));

        extract($info);
        extract($header);

        if ($type != 0x4D42) {
            return false;
        }

        $palette_size = $offset - 54;
        $ncolor = $palette_size / 4;
        $gd_header = "";

        $gd_header .= ($palette_size == 0) ? "\xFF\xFE" : "\xFF\xFF";
        $gd_header .= pack("n2", $width, $height);
        $gd_header .= ($palette_size == 0) ? "\x01" : "\x00";
        if ($palette_size) {
            $gd_header .= pack("n", $ncolor);
        }
        $gd_header .= "\xFF\xFF\xFF\xFF";
        fwrite($dest_f, $gd_header);

        if ($palette_size) {
            $palette = fread($src_f, $palette_size);
            $gd_palette = "";
            $j = 0;
            while ($j < $palette_size) {
                $b = $palette{$j++};
                $g = $palette{$j++};
                $r = $palette{$j++};
                $a = $palette{$j++};
                $gd_palette .= "$r$g$b$a";
            }
            $gd_palette .= str_repeat("\x00\x00\x00\x00", 256 - $ncolor);
            fwrite($dest_f, $gd_palette);
        }

        $scan_line_size = (($bits * $width) + 7) >> 3;
        $scan_line_align = ($scan_line_size & 0x03) ? 4 - ($scan_line_size & 0x03) : 0;

        for ($i = 0, $l = $height - 1; $i < $height; $i++, $l--) {
            fseek($src_f, $offset + (($scan_line_size + $scan_line_align) * $l));
            $scan_line = fread($src_f, $scan_line_size);
            if ($bits == 24) {
                $gd_scan_line = "";
                $j = 0;
                while ($j < $scan_line_size) {
                    $b = $scan_line{$j++};
                    $g = $scan_line{$j++};
                    $r = $scan_line{$j++};
                    $gd_scan_line .= "\x00$r$g$b";
                }
            } else if ($bits == 8) {
                $gd_scan_line = $scan_line;
            } else if ($bits == 4) {
                $gd_scan_line = "";
                $j = 0;
                while ($j < $scan_line_size) {
                    $byte = ord($scan_line{$j++});
                    $p1 = chr($byte >> 4);
                    $p2 = chr($byte & 0x0F);
                    $gd_scan_line .= "$p1$p2";
                }
                $gd_scan_line = substr($gd_scan_line, 0, $width);
            } else if ($bits == 1) {
                $gd_scan_line = "";
                $j = 0;
                while ($j < $scan_line_size) {
                    $byte = ord($scan_line{$j++});
                    $p1 = chr((int)(($byte & 0x80) != 0));
                    $p2 = chr((int)(($byte & 0x40) != 0));
                    $p3 = chr((int)(($byte & 0x20) != 0));
                    $p4 = chr((int)(($byte & 0x10) != 0));
                    $p5 = chr((int)(($byte & 0x08) != 0));
                    $p6 = chr((int)(($byte & 0x04) != 0));
                    $p7 = chr((int)(($byte & 0x02) != 0));
                    $p8 = chr((int)(($byte & 0x01) != 0));
                    $gd_scan_line .= "$p1$p2$p3$p4$p5$p6$p7$p8";
                }
                $gd_scan_line = substr($gd_scan_line, 0, $width);
            }
            fwrite($dest_f, $gd_scan_line);
        }
        fclose($src_f);
        fclose($dest_f);

        return true;
    }

    /**
     * 生成BMP图片资源
     *
     * @param string $filename 图片文件名
     * @return res|boolean        成功返回图片资源,失败返回false
     */
    public static function imagecreatefrombmp($filename)
    {
        $tmp_name = tempnam(ini_get('upload_tmp_dir'), 'GD');
        if (self::ConvertBMP2GD($filename, $tmp_name)) {
            $img = imagecreatefromgd($tmp_name);
            unlink($tmp_name);

            return $img;
        }

        return false;
    }
}
