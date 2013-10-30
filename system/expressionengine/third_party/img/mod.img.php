<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Img Module Front End File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Bjørn Børresen
 * @link		http://www.wedoaddons.com
 */

require_once PATH_THIRD.'img/libraries/ImageWorkshop.php';

class Img {
	
	public $return_data;

    private $prefix;
    private $debug = FALSE;

    private $errors = array(
        '1' => 'Could not find image: %s',
    );

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
        $this->EE->load->add_package_path(PATH_THIRD.'img/'); // add this addon folder to package path in case we're calling this addon from another addon
        $this->EE->load->library('imglib');
        $this->prefix = $this->EE->config->item('wda_img_prefix') ? $this->EE->config->item('wda_img_prefix') : 'img:';
        $this->debug = $this->EE->config->item('wda_img_debug') == 'y';

        $current_mem_limit = intval(ini_get('memory_limit'));
        if($current_mem_limit < 30)
        {
            ini_set('memory_limit', "30M");
        }
	}


    /**
     * Resize an image. Defaults to resize/zoom+crop to make the image fit the specified rectangle.
     *
     * @return mixed
     */
    public function resize($src=FALSE, $width=FALSE,$height=FALSE,$zoom_crop=TRUE,$retina=FALSE)
    {
        $from_template = FALSE;
        if(!$src)
        {
            $from_template = TRUE;
            $src = $this->EE->TMPL->fetch_param('src');
            $width = $this->EE->TMPL->fetch_param('w', $this->EE->TMPL->fetch_param('width'));
            $height = $this->EE->TMPL->fetch_param('h', $this->EE->TMPL->fetch_param('height'));
            $zoom_crop = $this->EE->TMPL->fetch_param('zoom_crop') != 'no';
            $retina = $this->EE->TMPL->fetch_param('retina') == 'yes';
            $position = $this->EE->TMPL->fetch_param('position', 'MM');

            /**
             * If $src contains {filedir_X} parse it
             */
            /**
             * If string contains a {filedir_x} reference we replace it with the correct url
             */
            if (preg_match('/^{filedir_(\d+)}/', $src, $matches))
            {
                $filedir_id = $matches[1];
                $this->EE->load->model('file_upload_preferences_model');
                $upload_dest_info = $this->EE->file_upload_preferences_model->get_file_upload_preferences(FALSE, $filedir_id);
                $src = str_replace('{filedir_'.$filedir_id.'}', $upload_dest_info['server_path'], $src);
            }

            // check if sizebyclass is set
            if(!$width && !$height) {
                $sizebyclass = $this->EE->TMPL->fetch_param('sizebyclass');
                $sizebyclass_arr = $this->EE->config->item('wda_img_sizebyclass');
                if(isset($sizebyclass_arr[$sizebyclass])) {
                    $width = $sizebyclass_arr[$sizebyclass][0];
                    $height = $sizebyclass_arr[$sizebyclass][1];

                    if($height == 'auto') {
                        $height = null;
                    }
                }
            }

        }

        // make sure we have a real server path to the image
        $src = $this->EE->imglib->server_path($src);

        if(strpos($src,'|') !== FALSE) {
            $src_arr = explode('|', $src);
            if(isset($src_arr[0]) && $src_arr[0] != '') {
                $src = $src_arr[0];
            } else if(isset($src_arr[1]) && $src_arr[1] != '') {
                $src = $src_arr[1];
            }
        }


	// make sure the image actually exists
	if (!file_exists($src)) {
		return '';
	}

        $tag_width = $width;
        $tag_height = $height;

        if($retina) {
            if($width) {
                $width = $width * 2;
            }
            if($height && $height != null) {
                $height = $height * 2;
            }
        }

        if(!$src && $this->EE->config->item('wda_img_debug') == 'n')
        {
            return '';
        }

            if($width || $height) {

                $resized_filename = $this->EE->imglib->get_cache_filename($src, array('width' => $width,'height'=>$height, 'zoom_crop' => $zoom_crop, 'position' => $position));
                $cached_file_path = $this->EE->config->slash_item('wda_img_cache_path') . $resized_filename;
                $image_url = FALSE;

                /**
                 * Check timestamp of cache file, overwrite cache if the original file is newer then the
                 * cached version
                 */
                $overwrite_cache = FALSE;
                if(file_exists($cached_file_path)) {
                    $original_filemtime = @filemtime($src);
                    $cached_filemtime = @filemtime($cached_file_path);

                    if($original_filemtime > $cached_filemtime) {
                        $overwrite_cache = TRUE;
                    }
                }

                if($this->EE->config->item('wda_img_disable_cache') == 'y' || $overwrite_cache || !file_exists($cached_file_path)) {

					if(!$src) {

						// empty, no source specified
						$img = new \PHPImageWorkshop\ImageWorkshop(
							array('imageFromPath' => PATH_THIRD.'img/error_image.png')
						);


						// sprintf($this->errors[1],$src)

					} else {
						try {
							$img = new \PHPImageWorkshop\ImageWorkshop(
								array('imageFromPath' => $src,
								'width' => $width,
								'height' => $height,
								)
							);
						} catch(\PHPImageWorkshop\ImageWorkshopException $e) {
							$img = new \PHPImageWorkshop\ImageWorkshop(
								array('imageFromPath' => PATH_THIRD.'img/error_image.png')
							);
						}
					}				

                    /**
                     * This means the original is the exact size we're requesting, so do not change it, if config option is set
                     */
                    if($width && $height && $this->EE->config->item('wda_img_do_not_resize_if_match') == 'y' && $img->getWidth() == $width && $img->getHeight() == $height)
                    {
                        $resized_filename = basename($src);

                        $webroot_path = realpath($this->EE->config->item('wda_img_webroot_path'));
                        $image_url = str_replace($webroot_path, '', realpath($src));
                        if(DIRECTORY_SEPARATOR == '\\') {   // windows fix
                            $image_url = str_replace(DIRECTORY_SEPARATOR, '/', $image_url);
                        }
                        $image_url = $this->EE->config->item('site_url').trim($image_url,'/');
                        $cached_file_path = $src;
                    }
                    else {
                        if($zoom_crop) {

                            if($height != null)
                            {
                                $ratioX = $width / $img->getWidth();
                                $ratioY = $height / $img->getHeight();
                                $ratio = $ratioX > $ratioY ? $ratioX : $ratioY;
                                $resizeWidth = ceil($img->getWidth() * $ratio);
                                $resizeHeight = ceil($img->getHeight() * $ratio);
                                $img->resizeInPixel($resizeWidth, $resizeHeight, true);
                                $img->cropInPixel($width, $height, 0, 0, $position);
                            }
                            else
                            {
                                $img->resizeInPixel($width, null, true);
                            }


                            /*

                            ($width > $height) ? $largestSide = $width : $largestSide = $height;
                            $img->resizeInPixel($largestSide, $largestSide, true);
                            $img->cropInPixel($width, $height, 0, 0, 'MM');
                            */
                        }
                        else {
                            $img->resizeInPixel($width?$width:null, $height?$height:null, true);
                        }

                        $img->save($this->EE->config->slash_item('wda_img_cache_path'), $resized_filename);
                    }
                }

                $vars = array(
                    $this->prefix.'width' => $tag_width,
                    $this->prefix.'height' => $tag_height,
                    $this->prefix.'actual_width' => $width,
                    $this->prefix.'actual_height' => $height,
                    $this->prefix.'url' => $image_url ? $image_url : $this->EE->config->slash_item('wda_img_cache_url').$resized_filename,
                    $this->prefix.'server_path' => $cached_file_path,
                    $this->prefix.'filename' => $resized_filename,
                );

                if($from_template)
                {
                    return $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, array($vars));
                }
                else
                {
                    return $vars;
                }

            } else {
                $this->EE->imglib->error('Neither width nor height specified for image: '.$src);
            }
    }

    /**
     * Action to serve an error image
     */
    public function error_on_image()
    {
        $err = intval($this->EE->input->get('err'));
        if($err > 0 && $this->EE->config->item('wda_img_debug') == 'y' && isset($this->errors[$err])) {
            $width = intval($this->EE->input->get('w'));
            $height = intval($this->EE->input->get('h'));
            if($width == 0) { $width = 100; }
            if($height == 0) { $height = 100; }
            $error_string = $this->errors[$err];

            $img = new \PHPImageWorkshop\ImageWorkshop(
                array(
                    'width' => $width,
                    'height' => $height,
                )
            );

            $textLayer = new \PHPImageWorkshop\ImageWorkshop(array(
                "text" => $error_string,                
                "fontSize" => 22,
                "fontColor" => "000000",
                "width" => $width,
                "height" => $height,
            ));

            $img->addLayer(1, $textLayer, 10, 10, 'LB');

            $image = $img->getResult();

            header('Content-type: image/jpeg');

            imagejpeg($image, null, 95);

        }
    }

}
/* End of file mod.img.php */
/* Location: /system/expressionengine/third_party/img/mod.img.php */
