<?php

/* 
 * Pharinix File Manager Copyright (C) 2016 Pedro Pelaez <aaaaa976@gmail.com>
 * Sources https://github.com/PSF1/pharinix_mod_file_manager
 *
 * GNU GENERAL PUBLIC LICENSE - Version 3, 29 June 2007
 */

class driverFileManager {
    
    /**
     * Remove a file or folder
     * 
     * @param string $path Virtual file or folder path
     * @param boolean $preserve Don't delete real path
     * 
     * @return boolean TRUE if success
     */
    public static function rm($path, $preserve = false) {
        $resp = false;
        $file = self::getByPath($path);
        if ($file != null) {
            $node = driverNodes::getNodes(array(
                'nodetype' => 'file',
                'count' => 1,
                'where' => '`parent` = '.$file->getId(),
            ), false);
            // Have it childs?
            if ($node[0]['amount'] == 0) { // nop
                // Remove virtual file
                driverCommand::run('delNode', array(
                    'nodetype' => 'file',
                    'nid' => $file->getId()
                ));
                // Remove real file
                if (!$preserve) {
                    if ($file->isFile()) {
                        $resp = @unlink($file->getRealpath());
                    } else {
                        $resp = @rmdir($file->getRealpath());
                    }
                }
            }
        }
        return $resp;
    }
    
    /**
     * Get a file or folder instance by path
     * @param string $path Virtual file or folder path
     * @return \driverFileManagerFile File or folder instance, or null if not found
     */
    public static function getByPath($path) {
        if (!driverTools::str_start('/', $path)) {
            $path = '/'.$path;
        }
        
        $node = driverCommand::run('getNodes', array(
            'nodetype' => 'file',
            'where' => '`path` = \''.$path.'\'',
        ));
        
        foreach($node as $f) {
            return new driverFileManagerFile((object) $f);
        }
        
        return null;
    }
    
    /**
     * Add a new root folder
     * @param string $path Real folder path
     * @param string $name New folder name
     * 
     * @return driverFileManagerFile The new root instance or null
     */
    public static function mount($path, $name) {
        $resp = null;
        driverFileManager::clearName($name);
        if (!driverTools::str_end('/', $path)) {
            $path .= '/';
        }
        if ($path != '' && $name != '') {
            if (is_dir($path)) {
                $params = array(
                    'nodetype' => 'file',
                    'isfolder' => 1,
                    'path' => '/' . $name . '/',
                    'realpath' => $path
                );
                $nresp = driverCommand::run('addNode', $params);
                if ($nresp['ok']) {
                    $preResp = new stdClass();
                    $preResp->isfolder = $params['isfolder'];
                    $preResp->path = $params['path'];
                    $preResp->realpath = $params['realpath'];
    //                $preResp->parent = $params['parent'];
                    // $preResp->mimetype = $params['mimetype'];
                    $preResp->id = $nresp['nid'];
                    $resp = new driverFileManagerFile($preResp);
                }
            }
        }
        return $resp;
    }
    
    public static function clearName(&$name) {
        $name = str_replace('/', '', $name);
        $name = str_replace('\\', '', $name);
    }
}

/**
 * Data base image from file or folder.
 * This class can't see access restrictions, but the OS can prevent her functionality.
 */
class driverFileManagerFile {
    /**
     * @var integer ID
     */
    protected $id = 0;
    /**
     * @var boolean Is a folder? 
     */
    protected $_isfolder = false;
    /**
     * @var string File system path 
     */
    protected $path = '';
    /**
     * @var string Real file system path 
     */
    protected $realpath = '';
    /**
     * @var integer Parent ID, or 0. 
     */
    protected $parent = 0;
    /**
     * @var string The suggested mime type, usually it's given by the uploader. 
     */
    protected $mimetype = "application/octet-stream";
    
    /**
     * If $std is defined try to import data from it
     * @param stdClass $std
     */
    public function __construct($std = null) {
        if ($std != null) {
            if (isset($std->id)) $this->id = $std->id;
            if (isset($std->isfolder)) {
                $this->_isfolder = $std->isfolder == '1' || 
                                  $std->isfolder === true || 
                                  $std->isfolder == 'true';
            }
            if (isset($std->path)) $this->path = $std->path;
            if (isset($std->realpath)) $this->realpath = $std->realpath;
            if (isset($std->parent)) $this->parent = $std->parent;
            if (isset($std->mimetype)) $this->mimetype = $std->mimetype;
        }
    }
    
    /**
     * Update data base record
     * @return boolean
     */
    public function save() {
        $resp = false;
        if ($this->id != 0) {
            $resp = driverCommand::run('updateNode', array(
                'nodetype' => 'file',
                'nid' => $this->id,
                'isfolder' => $this->_isfolder,
                'path' => $this->path,
                'realpath' => $this->realpath,
                'parent' => $this->parent,
                'mimetype' => $this->mimetype,
            ));
            $resp = $resp['ok'];
        }
        return $resp;
    }
    
    /**
     * Reload from data base
     * @return boolean
     */
    public function refresh() {
        $resp = false;
        if ($this->id != 0) {
            $node = driverCommand::run('getNode', array(
                'nodetype' => 'file',
                'node' => $this->id
            ));
            if (isset($node[$this->id])) {
                $this->_isfolder = $node[$this->id]['isfolder'] == '1';
                $this->path = $node[$this->id]['path'];
                $this->realpath = $node[$this->id]['realpath'];
                $this->parent = $node[$this->id]['parent'];
                $this->mimetype = $node[$this->id]['mimetype'];
                $resp = true;
            }
        }
        return $resp;
    }
    
    // Methods
    
    /**
     * Make a new folder
     * 
     * @see driverFileManagerFile::makeDir($name)
     * @param string $name
     * @return driverFileManagerFile The new folder instance
     */
    public function mkDir($name) {
        return $this->makeDir($name);
    }
    
    /**
     * Make a new folder
     * @param string $name
     * @return driverFileManagerFile The new folder instance
     */
    public function makeDir($name) {
        $resp = null;
        if ($this->isFolder()) {
            driverFileManager::clearName($name);
            if (@mkdir($this->realpath.$name.'/')) {
                $params = array(
                    'nodetype' => 'file',
                    'isfolder' => 1,
                    'path' => $this->path.$name.'/',
                    'realpath' => $this->realpath.$name.'/',
                    'parent' => $this->id,
                    //'mimetype' => $this->mimetype
                );
                $nresp = driverCommand::run('addNode', $params);
                if ($nresp['ok']) {
                    $preResp = new stdClass();
                    $preResp->isfolder = $params['isfolder'];
                    $preResp->path = $params['path'];
                    $preResp->realpath = $params['realpath'];
                    $preResp->parent = $params['parent'];
    //                $preResp->mimetype = $params['mimetype'];
                    $preResp->id = $nresp['nid'];
                    $resp = new driverFileManagerFile($preResp);
                }
            }
        }
        return $resp;
    }
    
    /**
     * Remove a file or folder
     * @param string $name File or folder name to remove
     * @param boolean $recursive If is a folder and not empty, remove content?
     */
    public function rm($name, $recursive = false) {
        driverFileManager::clearName($name);
        
    }
    
    // Getters and Setters
    
    /**
     * Get file size
     * @return integer
     */
    public function fileSize() {
        $resp = 0;
        if ($this->isFile()) {
            if (is_file($this->realpath)) {
                $resp = filesize($this->realpath);
            }
        }
        return $resp;
    }
    
    public function getId() {
        return $this->id;
    }

    public function isFile() {
        return $this->_isfolder == false;
    }
    
    public function isFolder() {
        return $this->_isfolder;
    }
    
    public function isRootFolder() {
        return $this->parent == 0;
    }
    
    public function getIsfolder() {
        return $this->_isfolder;
    }

    public function getPath() {
        return $this->path;
    }

    public function getParent() {
        return $this->parent;
    }

    public function getMimetype() {
        return $this->mimetype;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setIsfolder($isfolder) {
        $this->_isfolder = $isfolder;
    }

    public function setPath($path) {
        $this->path = $path;
    }

    public function setParent($parent) {
        $this->parent = $parent;
    }

    public function setMimetype($mimetype) {
        $this->mimetype = $mimetype;
    }
    public function getRealpath() {
        return $this->realpath;
    }

    public function setRealpath($realpath) {
        $this->realpath = $realpath;
    }

}