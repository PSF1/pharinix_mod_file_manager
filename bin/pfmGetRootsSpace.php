<?php

/* 
 * Pharinix File Manager Copyright (C) 2016 Pedro Pelaez <aaaaa976@gmail.com>
 * Sources https://github.com/PSF1/pharinix_mod_file_manager
 *
 * GNU GENERAL PUBLIC LICENSE - Version 3, 29 June 2007
 */

if (!defined("CMS_VERSION")) { header("HTTP/1.0 404 Not Found"); die(""); }

if (!class_exists("commandPfmGetRootsSpace")) {
    class commandPfmGetRootsSpace extends driverCommand {
        protected static $cntOk = 0;
        protected static $cntFail = 0;
        
        public static function runMe(&$params, $debug = true) {
            $path = driverCommand::getModPath('pharinix_mod_file_manager');
            include_once $path.'drivers/fileManager.php';
            
            $resp = array();
            $roots = driverFileManager::getRoots();
            foreach($roots as $root) {
                $total = disk_total_space($root->getRealpath());
                $free = disk_free_space($root->getRealpath());
                $percentage = (($total - $free) * 100) / $total;
                $resp[] = array(
                    'path' => $root->getPath(),
                    'used' => ($total - $free),
                    'total' => $total,
                    'used_percent' => round($percentage, 2),
                );
            }
            return $resp;
        }
        
        public static function getHelp() {
            return array(
                "package" => "pharinix_mod_file_manager",
                "description" => __("Get list of root folders, and disk space information."), 
                "parameters" => array(), 
                "response" => array(),
                "type" => array(
                    "parameters" => array(), 
                    "response" => array(),
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
return new commandPfmGetRootsSpace();