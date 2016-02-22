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
            ), $params);
            $file = driverFileManager::getByPath($params['path']);
            if ($file != null) {
                header("Content-type: ".$file->getMimetype());
                header("Content-Disposition:inline;filename=\"".$file->getName()."\"");
                header('Content-Length: ' . $file->fileSize());
                //header("Cache-control: private"); //use this to open files directly                     
                readfile($file->getRealpath());
            } else {
                header("HTTP/1.0 404 Not Found");
            }
        }

        public static function getHelp() {
            return array(
                "package" => "pharinix_mod_file_manager",
                "description" => __("Give download to a file if user can do it."), 
                "parameters" => array(
                    'path' => __('Virtual path.'),
                ), 
                "response" => array(),
                "type" => array(
                    "parameters" => array(
                        'path' => 'string',
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