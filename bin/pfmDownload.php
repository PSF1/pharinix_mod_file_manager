<?php

/* 
 * Pharinix File Manager Copyright (C) 2016 Pedro Pelaez <aaaaa976@gmail.com>
 * Sources https://github.com/PSF1/pharinix_mod_file_manager
 *
 * GNU GENERAL PUBLIC LICENSE - Version 3, 29 June 2007
 */

if (!defined("CMS_VERSION")) { header("HTTP/1.0 404 Not Found"); die(""); }

if (!class_exists("commandPfmDownload")) {
    class commandPfmDownload extends driverCommand {

        public static function runMe(&$params, $debug = true) {
            $path = driverCommand::getModPath('pharinix_mod_file_manager');
            include_once $path.'drivers/fileManager.php';
            
            $params = array_merge(array(
                'path' => '',
                'inline' => true,
            ), $params);
            $file = driverFileManager::getByPath($params['path']);
            if ($file != null) {
                /*header("Content-type: ".$file->getMimetype());
                header("Content-Disposition:inline;filename=\"".$file->getName()."\"");
                header('Content-Length: ' . $file->fileSize());
                //header("Cache-control: private"); //use this to open files directly                     
                readfile($file->getRealpath());*/
                
                self::downloadFile($file, 32 * 1024, true);
                exit;
            } else {
                header("HTTP/1.0 404 Not Found");
            }
        }

        /**
         * http://stackoverflow.com/a/13821992
         * 
          Parameters: downloadFile(File Location, File Name,
          max speed, is streaming
          If streaming - videos will show as videos, images as images
          instead of download prompt
         */
        /**
         * 
         * @param driverFileManagerFile $file
         * @param integer $maxSpeed
         * @param boolean $doStream
         * @return boolean
         */
        public static function downloadFile(&$file, $maxSpeed = 100, $doStream = false) {
            if (connection_status() != 0)
                return(false);
        //    in some old versions this can be pereferable to get extention
        //    $extension = strtolower(end(explode('.', $fileName)));
            //$extension = pathinfo($fileName, PATHINFO_EXTENSION);

            $contentType = $file->getMimetype();
            header("Cache-Control: public");
            header("Content-Transfer-Encoding: binary\n");
            header("Content-Type: $contentType");

            $contentDisposition = 'attachment';

            if ($doStream == true) {
                /* extensions to stream */
//                $array_listen = array('mp3', 'm3u', 'm4a', 'mid', 'ogg', 'ra', 'ram', 'wm',
//                    'wav', 'wma', 'aac', '3gp', 'avi', 'mov', 'mp4', 'mpeg', 'mpg', 'swf', 'wmv', 'divx', 'asf');
//                if (in_array($extension, $array_listen)) {
                    $contentDisposition = 'inline';
//                }
            }

//            if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
//                $fileName = preg_replace('/\./', '%2e', $fileName, substr_count($fileName, '.') - 1);
//                header("Content-Disposition: $contentDisposition;
//                    filename=\"$fileName\"");
//            } else {
//                header("Content-Disposition: $contentDisposition;
//                    filename=\"$fileName\"");
//            }
            header("Content-Disposition: $contentDisposition;filename=\"{$file->getName()}\"");

            header("Accept-Ranges: bytes");
            $range = 0;
            $size = $file->fileSize();

            if (isset($_SERVER['HTTP_RANGE'])) {
                list($a, $range) = explode("=", $_SERVER['HTTP_RANGE']);
                str_replace($range, "-", $range);
                $size2 = $size - 1;
                $new_length = $size - $range;
                header("HTTP/1.1 206 Partial Content");
                header("Content-Length: $new_length");
                header("Content-Range: bytes $range$size2/$size");
            } else {
                $size2 = $size - 1;
                header("Content-Range: bytes 0-$size2/$size");
                header("Content-Length: " . $size);
            }

            if ($size == 0) {
                die('Zero byte file! Aborting download');
            }
            $fp = fopen($file->getRealpath(), "rb");

            fseek($fp, str_replace('-', '', $range));

            while (!feof($fp) and ( connection_status() == 0)) {
                set_time_limit(0);
                print(fread($fp, 1024 * $maxSpeed));
                flush();
                ob_flush();
                sleep(1);
            }
            fclose($fp);

            return((connection_status() == 0) and ! connection_aborted());
        }
        
        public static function getHelp() {
            return array(
                "package" => "pharinix_mod_file_manager",
                "description" => __("Give download to a file if user can do it."), 
                "parameters" => array(
                    'path' => __('Virtual path.'),
                    'inline' => __('FALSE to force download the file to the client computer, TRUE to integrate in browser.'),
                ), 
                "response" => array(),
                "type" => array(
                    "parameters" => array(
                        'path' => 'string',
                        'inline' => 'boolean',
                    ), 
                    "response" => array(),
                ),
                "echo" => true
            );
        }
        
        public static function getAccess($ignore = "") {
            $me = __FILE__;
            return parent::getAccess($me);
        }
        
        public static function getAccessFlags() {
            return driverUser::PERMISSION_FILE_ALL_EXECUTE;
        }
    }
}
return new commandPfmDownload();
