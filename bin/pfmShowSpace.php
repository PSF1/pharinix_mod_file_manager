<?php

/* 
 * Pharinix File Manager Copyright (C) 2016 Pedro Pelaez <aaaaa976@gmail.com>
 * Sources https://github.com/PSF1/pharinix_mod_file_manager
 *
 * GNU GENERAL PUBLIC LICENSE - Version 3, 29 June 2007
 */

if (!defined("CMS_VERSION")) { header("HTTP/1.0 404 Not Found"); die(""); }

if (!class_exists("commandPfmShowSpace")) {
    class commandPfmShowSpace extends driverCommand {
        protected static $cntOk = 0;
        protected static $cntFail = 0;
        
        public static function runMe(&$params, $debug = true) {
            $roots = driverCommand::run('pfmGetRootsSpace');
            foreach($roots as $root) {
                echo "<span><b>{$root['path']}</b>: ";
                echo sprintf(__('Used %s of %s, free %s.'),
                            driverTools::formatBytes($root['used']),
                            driverTools::formatBytes($root['total']),
                            driverTools::formatBytes($root['total'] - $root['used']));
                echo "</span>";
                $status = 'progress-bar-success';
                if ($root['used_percent'] >= 90) {
                    $status = 'progress-bar-danger';
                } else if ($root['used_percent'] >= 80) {
                    $status = 'progress-bar-warning';
                }
                echo '<div class="progress"><div class="progress-bar active '.$status.'" role="progressbar" aria-valuenow="'.$root['used_percent'].'" aria-valuemin="0" aria-valuemax="100" style="width: '.$root['used_percent'].'%"><span>'.$root['used_percent'].'%</span></div></div>';
                echo '';
            }
        }
        
        public static function getHelp() {
            return array(
                "package" => "pharinix_mod_file_manager",
                "description" => __("Show list of root folders, and disk space information."), 
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
return new commandPfmShowSpace();