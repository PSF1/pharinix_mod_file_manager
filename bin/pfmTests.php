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
        protected static $cntOk = 0;
        protected static $cntFail = 0;
        
        public static function runMe(&$params, $debug = true) {
            $path = driverCommand::run('modGetPath', array(
                'name' => 'pharinix_mod_file_manager'
            ));
            $path = $path['path'];
            include_once $path.'drivers/fileManager.php';
            
            echo '<legend>'.__('Testing File Manager Driver').'</legend>';
            
            echo self::getLegend(__('Message legend'));
            echo self::getSuccess(__('This is a example of success message'));
            echo self::getFail(__('This is a example of fail message'));
            
            self::$cntFail = 0;
            self::$cntOk = 0;
            
            // START
            if (!is_dir('var/pfm_test/')) {
                if (!@mkdir('var/pfm_test/')) {
                    echo self::getFail(__('I can\'t make \'var/pfm_test/\' folder'));
                }
            }
            
            echo self::getLegend(__('Adding a root folder'));
            $resp = driverFileManager::mount('var/pfm_test/', '/pfm_test/');
            if ($resp == null) {
                echo self::getFail(__('I can\'t mount \'/pfm_test/\' folder'));
            } else {
                echo self::getSuccess(__('Ok'));
            }
            
            echo self::getLegend(__('Geting a root folder'));
            $rootFolder = driverFileManager::getByPath('/pfm_test/');
            if ($rootFolder == null) {
                echo self::getFail(__('I can\'t get \'/pfm_test/\' folder'));
            } else {
                echo self::getSuccess(__('Ok'));
                if ($rootFolder->isRootFolder()) {
                    echo self::getSuccess(__('Entity is a root folder'));
                } else {
                    echo self::getFail(__('Entity is not a root folder'));
                }
            }
            
            echo self::getLegend(__('Adding a 1st level folder'));
            $fLevel1 = $rootFolder->makeDir('level 1');
            if ($fLevel1 == null) {
                echo self::getFail(__('I can\'t make \'/pfm_test/level 1/\' folder'));
            } else if(is_dir('var/pfm_test/level 1/')) {
                echo self::getSuccess(__('Ok'));
                if (!$fLevel1->isRootFolder()) {
                    echo self::getSuccess(__('Entity is NOT a root folder'));
                } else {
                    echo self::getFail(__('Entity is a root folder'));
                }
                if (!$fLevel1->isFolder()) {
                    echo self::getFail(__('Entity is NOT a folder'));
                } else {
                    echo self::getSuccess(__('Entity is a folder'));
                }
            } else {
                echo self::getFail(__('Real folder of \'/pfm_test/level 1/\' it\'s not created (\'var/pfm_test/level 1/\').'));
            }
            
            echo self::getLegend(__('Geting a 1st level folder'));
            $fLevel1 = driverFileManager::getByPath('/pfm_test/level 1/');
            if ($fLevel1 == null) {
                echo self::getFail(__('I can\'t make \'/pfm_test/level 1/\' folder'));
            } else if(is_dir('var/pfm_test/level 1/')) {
                echo self::getSuccess(__('Ok'));
                if (!$fLevel1->isRootFolder()) {
                    echo self::getSuccess(__('Entity is NOT a root folder'));
                } else {
                    echo self::getFail(__('Entity is a root folder'));
                }
                if (!$fLevel1->isFolder()) {
                    echo self::getFail(__('Entity is NOT a folder'));
                } else {
                    echo self::getSuccess(__('Entity is a folder'));
                }
            } else {
                echo self::getFail(__('Real folder of \'/pfm_test/level 1/\' it\'s not created (\'var/pfm_test/level 1/\').'));
            }
            
            echo self::getLegend(__('Adding a 2nd & 3st level folder'));
            $fLevel2 = $fLevel1->makeDir('level 2');
            if ($fLevel2 == null) {
                echo self::getFail(__('I can\'t make \'/pfm_test/level 1/level 2/\' folder'));
            } else if(is_dir('var/pfm_test/level 1/level 2/')) {
                echo self::getSuccess(__('2nd Ok'));
                if (!$fLevel2->isRootFolder()) {
                    echo self::getSuccess(__('Entity is NOT a root folder'));
                } else {
                    echo self::getFail(__('Entity is a root folder'));
                }
                if (!$fLevel2->isFolder()) {
                    echo self::getFail(__('Entity is NOT a folder'));
                } else {
                    echo self::getSuccess(__('Entity is a folder'));
                }
            } else {
                echo self::getFail(__('Real folder of \'/pfm_test/level 1/level 2/\' it\'s not created (\'var/pfm_test/level 1/level 2/\').'));
            }
            $fLevel3 = $fLevel2->makeDir('level 3');
            if ($fLevel3 == null) {
                echo self::getFail(__('I can\'t make \'/pfm_test/level 1/level 2/level 3/\' folder'));
            } else if(is_dir('var/pfm_test/level 1/level 2/level 3/')) {
                echo self::getSuccess(__('3st Ok'));
                if (!$fLevel3->isRootFolder()) {
                    echo self::getSuccess(__('Entity is NOT a root folder'));
                } else {
                    echo self::getFail(__('Entity is a root folder'));
                }
                if (!$fLevel3->isFolder()) {
                    echo self::getFail(__('Entity is NOT a folder'));
                } else {
                    echo self::getSuccess(__('Entity is a folder'));
                }
            } else {
                echo self::getFail(__('Real folder of \'/pfm_test/level 1/level 2/level 3/\' it\'s not created (\'var/pfm_test/level 1/level 2/level 3/\').'));
            }
            
            echo self::getLegend(__('Delete empty 3st & 2nd level folder'));
            $resp = driverFileManager::rm('/pfm_test/level 1/level 2/level 3/');
            if (!$resp) {
                echo self::getFail(__('I can\'t remove \'/pfm_test/level 1/level 2/level 3/\' folder'));
            } else if(is_dir('var/pfm_test/level 1/level 2/level 3/')) {
                echo self::getFail(__('Real folder of \'/pfm_test/level 1/level 2/level 3/\' it\'s not removed (\'var/pfm_test/level 1/level 2/level 3/\').'));
            } else {
                echo self::getSuccess(__('3st Ok'));
            }
            $resp = driverFileManager::rm('/pfm_test/level 1/level 2/');
            if (!$resp) {
                echo self::getFail(__('I can\'t remove \'/pfm_test/level 1/level 2/\' folder'));
            } else if(is_dir('var/pfm_test/level 1/level 2/')) {
                echo self::getFail(__('Real folder of \'/pfm_test/level 1/level 2/\' it\'s not removed (\'var/pfm_test/level 1/level 2/\').'));
            } else {
                echo self::getSuccess(__('2nd Ok'));
            }
            
            echo self::getLegend(__('Delete empty 1st level folder'));
            $resp = driverFileManager::rm('/pfm_test/level 1/');
            if (!$resp) {
                echo self::getFail(__('I can\'t remove \'/pfm_test/level 1/\' folder'));
            } else if(is_dir('var/pfm_test/level 1/')) {
                echo self::getFail(__('Real folder of \'/pfm_test/level 1/\' it\'s not removed (\'var/pfm_test/level 1/\').'));
            } else {
                echo self::getSuccess(__('Ok'));
            }
            
            echo self::getLegend(__('Delete empty root folder'));
            $resp = driverFileManager::rm('/pfm_test/');
            if (!$resp) {
                echo self::getFail(__('I can\'t remove \'/pfm_test/\' folder'));
            } else if(is_dir('var/pfm_test/')) {
                echo self::getFail(__('Real folder of \'/pfm_test/\' it\'s not removed (\'var/pfm_test/\').'));
            } else {
                echo self::getSuccess(__('Ok'));
            }
            
            echo self::getLegend(__('Delete multilevel folder'));
            if (!is_dir('var/pfm_test/')) {
                if (!@mkdir('var/pfm_test/')) {
                    echo self::getFail(__('I can\'t make \'var/pfm_test/\' folder'));
                }
            }
            // Make test structure
            $resp = driverFileManager::mount('var/pfm_test/', '/pfm_test/');
            $rootFolder = driverFileManager::getByPath('/pfm_test/');
            $fLevel1 = $rootFolder->makeDir('level 1');
            $fLevel2 = $fLevel1->makeDir('level 2');
            $fLevel3 = $fLevel2->makeDir('level 3');
            // Delete from root
            $rootFolder->rm('level 1', true);
            $childs = $rootFolder->getChilds('level 1');
            if (count($childs) > 0) {
                echo self::getFail(__('Level 1 exist yet.'));
            } else {
                echo self::getSuccess(__('Ok'));
            }
            
            echo self::getLegend(__('Add file'));
            $rootFolder = driverFileManager::getByPath('/pfm_test/');
            $file = $rootFolder->getFile('test.txt', true);
            if ($file === false) {
                echo self::getFail(__('I cant create test.txt file.'));
            } else {
                echo self::getSuccess(__('Ok'));
            }
            $rootFolder->rm($file);
            driverFileManager::rm('/pfm_test/');
            
            echo self::getLegend(__('Test driverFileManager::getMimeByExt'));
            $resp = driverFileManager::getMimeByExt('hi.txt');
            if ($resp == 'text/plain') {
                echo self::getSuccess(__('Ok').': text/plain');
            } else {
                echo self::getFail(__('Fails').': text/plain');
            }
            $resp = driverFileManager::getMimeByExt('hi.mp4');
            if ($resp == 'video/mp4') {
                echo self::getSuccess(__('Ok').': video/mp4');
            } else {
                echo self::getFail(__('Fails').': video/mp4');
            }
            $resp = driverFileManager::getMimeByExt('hi.wav');
            if ($resp == 'audio/x-wav') {
                echo self::getSuccess(__('Ok').': audio/x-wav');
            } else {
                echo self::getFail(__('Fails').': audio/x-wav');
            }
            
            // END: The system must be clean
            
            $final = self::getLegend(sprintf(__('Executed %s tests OK, and %s FAILS.'), self::$cntOk, self::$cntFail));
            echo '<div class="well">';
            if (self::$cntFail > 0) {
                echo self::getFail($final);
            } else {
                echo self::getSuccess($final);
            }
            echo '</div>';
        }

        public static function getSuccess($msg) {
            ++self::$cntOk;
            $resp = <<<EOT
<div class="text-success" role="alert">
  <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
  <span class="sr-only">Ok:</span> $msg
</div>
EOT;
            return $resp;
        }
        
        public static function getFail($msg) {
            ++self::$cntFail;
            $resp = <<<EOT
<div class="text-danger" role="alert">
  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
  <span class="sr-only">Ok:</span> $msg
</div>
EOT;
            return $resp;
        }
        
        public static function getLegend($msg) {
            $resp = <<<EOT
<h4>$msg</h4>
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
        
        public static function getAccessFlags() {
            return driverUser::PERMISSION_FILE_ALL_EXECUTE;
        }
    }
}
return new commandPfmTests();