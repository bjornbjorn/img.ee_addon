<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Imglib
{

    public function __construct()
    {
        $this->EE = get_instance();
    }

    /**
     * Get server path from src
     *
     * @param $src
     */
    public function server_path($src)
    {
        /**
         * If string contains a {filedir_x} reference we replace it with the correct server path
         */
        if (preg_match('/^{filedir_(\d+)}/', $src, $matches))
        {
            $filedir_id = $matches[1];
            $this->EE->load->model('file_upload_preferences_model');
            $upload_dest_info = $this->EE->file_upload_preferences_model->get_file_upload_preferences(FALSE, $filedir_id);
            $src = str_replace('{filedir_'.$filedir_id.'}', $upload_dest_info['server_path'], $src);
        }

        return $src;

    }

    /**
     * Get the cached file for an image
     *
     * @param $src
     * @param $options
     */
    public function get_cache_filename($src, $options)
    {
        $lastpos = strrpos($src, '\\') > strrpos($src, '/') ? strrpos($src, '\\') : strrpos($src, '/');
        $filename = substr($src, $lastpos+1);
        $filename_wo_ext = substr($filename, 0, strrpos($filename,'.'));
        $extension = substr($filename, strrpos($filename,'.'));

        $cached_filename = $filename_wo_ext;
        if(isset($options['width']) && $options['width']) {
            $cached_filename.='-w'.$options['width'];
        }
        if(isset($options['height']) && $options['height']) {
            $cached_filename.='-h'.$options['height'];
        }
        if(isset($options['zoom_crop']) && $options['zoom_crop']) {
            $cached_filename.='-zc';
        }

        if(isset($options['position'])) {
            $cached_filename.='-'.strtolower($options['position']);
        }


        return $cached_filename . $extension;
    }

    /**
     * Output error message if debug is turned on
     *
     * @param $msg
     */
    public function error($msg)
    {
        if($this->EE->config->item('wda_img_debug') == 'y')
        {
            $this->EE->output->show_message(
                array(
                    'title' => 'IMG Error',
                    'heading' => 'IMG Error',
                    'content' => '<p>'.$msg.'</p><p>&nbsp;</p><p>PS! You are seing this because wda_img_debug=y in your config file (turn off for production)</p>'
                )
            );
        }
        else
        {
            // do other stuff
        }
    }

}