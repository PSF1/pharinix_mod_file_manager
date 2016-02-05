<?php

/* 
 * Pharinix File Manager Copyright (C) 2016 Pedro Pelaez <aaaaa976@gmail.com>
 * Sources https://github.com/PSF1/pharinix_mod_file_manager
 *
 * GNU GENERAL PUBLIC LICENSE - Version 3, 29 June 2007
 */

if (!defined("CMS_VERSION")) { header("HTTP/1.0 404 Not Found"); die(""); }

if (!class_exists("commandPfmTests")) {
    class commandPfmTests extends driverCommand {

        public static function runMe(&$params, $debug = true) {
            $path = driverCommand::run('modGetPath', array(
                'name' => 'pharinix_mod_file_manager'
            ));
            $path = $path['path'];
            include_once $path.'drivers/fileManager.php';
            
            echo '<legend>'.__('Testing File Manager Driver').'</legend>';
            
            echo self::getLegend(__('Message legend'));
            echo self::getSuccess(__('This is a example of success message'));
            echo self::getAlert(__('This is a example of fail message'));
            
            // START
            if (!is_dir('var/pfm_test/')) {
                if (!@mkdir('var/pfm_test/')) {
                    echo self::getAlert(__('I can\'t make \'var/pfm_test/\' folder'));
                }
            }
            
            echo self::getLegend(__('Adding a root folder'));
            $resp = driverFileManager::mount('var/pfm_test/', '/pfm_test/');
            if ($resp == null) {
                echo self::getAlert(__('I can\'t mount \'/pfm_test/\' folder'));
            } else {
                echo self::getSuccess(__('Ok'));
            }
            
            echo self::getLegend(__('Geting a root folder'));
            $resp = driverFileManager::getByPath('/pfm_test/');
            if ($resp == null) {
                echo self::getAlert(__('I can\'t get \'/pfm_test/\' folder'));
            } else {
                echo self::getSuccess(__('Ok'));
            }
            
            echo self::getLegend(__('Delete empty root folder'));
            $resp = driverFileManager::rm('/pfm_test/');
            if (!$resp) {
                echo self::getAlert(__('I can\'t remove \'/pfm_test/\' folder'));
            } else if(is_dir('var/pfm_test/')) {
                echo self::getAlert(__('Real folder of \'/pfm_test/\' it\'s not removed (\'var/pfm_test/\').'));
            } else {
                echo self::getSuccess(__('Ok'));
            }
            
            // END: We need clear system
            // TODO: Delete nodes
            
            // Delete test folder
//            if (!@rmdir('var/pfm_test/')) {
//                echo self::getAlert(__('I can\'t remove \'var/pfm_test/\' folder'));
//                return;
//            }
        }

        public static function getSuccess($msg) {
            $resp = <<<EOT
<div class="alert alert-success" role="alert">
  <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
  <span class="sr-only">Ok:</span> $msg
</div>
EOT;
            return $resp;
        }
        
        public static function getLegend($msg) {
            $resp = <<<EOT
<h2>$msg</h2>
EOT;
            return $resp;
        }
        
        public static function getHelp() {
            return array(
                "package" => "pharinix_mod_file_manager",
                "description" => __("Test the driver."), 
                "parameters" => array(), 
                "response" => array(),
                "type" => array(
                    "parameters" => array(), 
                    "response" => array(),
                ),
                "echo" => true
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
return new commandPfmTests();