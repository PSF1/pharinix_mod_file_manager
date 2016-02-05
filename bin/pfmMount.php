<?php

/* 
 * Pharinix File Manager Copyright (C) 2016 Pedro Pelaez <aaaaa976@gmail.com>
 * Sources https://github.com/PSF1/pharinix_mod_file_manager
 *
 * GNU GENERAL PUBLIC LICENSE - Version 3, 29 June 2007
 */

if (!defined("CMS_VERSION")) { header("HTTP/1.0 404 Not Found"); die(""); }

if (!class_exists("commandPfmMount")) {
    class commandPfmMount extends driverCommand {

        public static function runMe(&$params, $debug = true) {
            $path = driverCommand::run('modGetPath', array(
                'name' => 'pharinix_mod_file_manager'
            ));
            $path = $path['path'];
            include_once $path.'drivers/fileManager.php';
            
            $params = array_merge(array(
                'path' => '',
                'name' => ''
            ), $params);
            
            $inst = driverFileManager::mount($params['path'], $params['name']);
            
            return array('ok' => ($inst != null));
        }

        public static function getHelp() {
            return array(
                "package" => "pharinix_mod_file_manager",
                "description" => __("Add a new root folder."), 
                "parameters" => array(
                    'path' => __('Real path.'),
                    'name' => __('Name to use how root.')
                ), 
                "response" => array(
                    'ok' => __('TRUE if ok'),
                ),
                "type" => array(
                    "parameters" => array(
                        'path' => 'string',
                        'name' => 'string',
                    ), 
                    "response" => array(
                        'ok' => 'boolean',
                    ),
                ),
                "echo" => false
            );
        }
        
        public static function getAccess($ignore = "") {
            $me = __FILE__;
            return parent::getAccess($me);
        }
        
//        public static function getAccessFlags() {
//            return driverUser::PERMISSION_FILE_ALL_EXECUTE;
//        }
    }
}
return new commandPfmMount();