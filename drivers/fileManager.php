<?php

/* 
 * Pharinix File Manager Copyright (C) 2016 Pedro Pelaez <aaaaa976@gmail.com>
 * Sources https://github.com/PSF1/pharinix_mod_file_manager
 *
 * GNU GENERAL PUBLIC LICENSE - Version 3, 29 June 2007
 */

class driverFileManager {
    const CHANGECRUD_OWNER = 8;
    const CHANGECRUD_GROUP = 4;
    const CHANGECRUD_ALL = 0;
    
    /**
     * Knowed mime types cache
     * @var array 
     */
    public static $mimeTypes = array();
    
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
                $resp = self::rmFileEntity($file, $preserve);
            }
        }
        return $resp;
    }
    
    /**
     * Delete a file or folder from data base and OS file system.
     * IMPORTANT: This method don't verify childs.
     * @param driverFileManagerFile $file Entity to delete
     * @param boolean $preserve Erase real file or folder?
     * @return boolean TRUE if Ok.
     */
    public static function rmFileEntity($file, $preserve = false) {
        // Remove virtual file
        driverCommand::run('delNode', array(
            'nodetype' => 'file',
            'nid' => $file->getId()
        ));
        // Remove real file
        $resp = true;
        if (!$preserve) {
            if ($file->isFile()) {
                $resp = @unlink($file->getRealpath());
            } else {
                $resp = @rmdir($file->getRealpath());
            }
        }
        return $resp;
    }
    
    /**
     * Get list of root folders
     * @return \driverFileManagerFile
     */
    public static function getRoots() {
        $node = driverCommand::run('getNodes', array(
            'nodetype' => 'file',
            'where' => '`parent` = 0',
        ));
        
        $resp = array();
        foreach($node as $f) {
            $resp[] = new driverFileManagerFile((object) $f);
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
     * Get file or folder by id
     * @param integer $id
     * @return \driverFileManagerFile
     */
    public static function getById($id) {
        $node = driverCommand::run('getNode', array(
            'nodetype' => 'file',
            'node' => $id,
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
                    'mimetype' => 'inode/directory',
                    'realpath' => $path
                );
                $nresp = driverCommand::run('addNode', $params);
                if ($nresp['ok']) {
                    $preResp = new stdClass();
                    $preResp->isfolder = $params['isfolder'];
                    $preResp->path = $params['path'];
                    $preResp->realpath = $params['realpath'];
    //                $preResp->parent = $params['parent'];
                    $preResp->mimetype = $params['mimetype'];
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
    
    /**
     * @see http://php.net/manual/es/function.mime-content-type.php#107798
     */
    public static function mimeTypesUpdate() {
        $url = 'http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types';
        $modPath = driverCommand::getModPath('pharinix_mod_file_manager');
        @file_put_contents($modPath.'assets/mimetypes.txt', @file_get_contents($url));
        self::$mimeTypes = array();
    }

    public static function getMimeTypes() {
        if (count(self::$mimeTypes) == 0) {
            $s = array();
            $modPath = driverCommand::getModPath('pharinix_mod_file_manager');
            $lines = @explode("\n", @file_get_contents($modPath.'assets/mimetypes.txt'));
            foreach ($lines as $x)
                if (isset($x[0]) && 
                    $x[0] !== '#' && 
                    preg_match_all('#([^\s]+)#', $x, $out) && 
                    isset($out[1]) && 
                    ($c = count($out[1])) > 1) {
                        for ($i = 1; $i < $c; $i++) {
                            $s[$out[1][$i]] = $out[1][0];
                        }
                    }
            return $s;
        }
        
        return self::$mimeTypes;
    }
    
    /**
     * Get mime type by file extension
     * @param string $name File name
     * @return string Mime type
     */
    public static function getMimeByExt($name) {
        $types = self::getMimeTypes();
        $fInfo = driverTools::pathInfo($name);
        if (isset($types[$fInfo['ext']])) {
            return $types[$fInfo['ext']];
        }
        return "application/octet-stream";
    }
    
    /**
     * Verify if a URL is from file manager module.
     * @param string $url
     * @return boolean
     */
    public static function isFileManagerDownloadURL($url) {
        return (stripos($url, 'pfmDownload') !== false);
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
    
    protected $resource = null;
    
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
     * Create a file entity and create it if dont exist.
     * @param string $name File name
     * @return driverFileManagerFile File entity or FALSE if fail.
     */
    public function createFile($name) {
        return $this->getFile($name, true);
    }
    
    /**
     * Get file entity or create it if dont exist.
     * @param string $name File name
     * @param boolean $create Create if dont exist?
     * @return driverFileManagerFile File entity or FALSE if fail.
     */
    public function getFile($name, $create = false) {
        if($this->isFile()) return false;
        driverFileManager::clearName($name);
        
        $files = $this->getChilds($name);
        if (count($files) > 0) {
            foreach($files as $file) {
                return $file;
            }
        }
        if ($create) {
            $params = array(
                'nodetype' => 'file',
                'isfolder' => false,
                'path' => $this->path . $name,
                'realpath' => $this->realpath . $name,
                'parent' => $this->id,
                'mimetype' => driverFileManager::getMimeByExt($name)
            );
            $nresp = driverCommand::run('addNode', $params);
            $resp = false;
            if ($nresp['ok']) {
                $preResp = new stdClass();
                $preResp->isfolder = $params['isfolder'];
                $preResp->path = $params['path'];
                $preResp->realpath = $params['realpath'];
                $preResp->parent = $params['parent'];
                $preResp->mimetype = $params['mimetype'];
                $preResp->id = $nresp['nid'];
                $resp = new driverFileManagerFile($preResp);
                $resp->open('a+');
                $resp->close();
            }
            return $resp;
        }
        return false;
    }
    
    /**
     * Open the file, and change state in data base.
     * @param string $mode The mode parameter specifies the type of access you require to the stream.
     * @see http://php.net/manual/en/function.fopen.php
     * @return resource Returns a file pointer resource on success, or FALSE on error.
     */
    public function open($mode) {
        if ($this->isFolder()) return false;
        
        $resp = driverCommand::run('updateNode', array(
            'nodetype' => 'file',
            'nid' => $this->id,
            'state' => '1',
        ));
        if ($resp['ok']) {
            $this->resource = @fopen($this->realpath, $mode);
            return $this->resource;
        } else {
            return false;
        }
    }
    
    /**
     * Close the file, and change state in data base.
     * @return boolean 
     */
    public function close() {
        if ($this->isFolder()) return false;
        $resp = driverCommand::run('updateNode', array(
            'nodetype' => 'file',
            'nid' => $this->id,
            'state' => '0',
        ));
        $resp = @fclose($this->resource);
        $this->resource = null;
        
        return $resp;
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
                'isfolder' => ($this->_isfolder == '1'),
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
     * Change file owner or/and owner group
     * 
     * @param string $owner Mail of the user or ID of the new owner. If it's null, or it is not set, command don't change it, to set to root you must value how 0, zero.
     * @param type $group Title or ID of the group. If it's null, or it is not set, command don't change it, to set to root you must value how 0, zero.
     * @return array
     */
    public function chOwn($owner = null, $group = null) {
        return driverCommand::run('chownNode', array(
            'nodetype' => 'file',
            'nid' => $this->id,
            'owner' => $owner,
            'group' => $group,
        ));
    }
    
    /**
     * Change permission
     * 
     * @param integer $flags Integer with the new permissions.
     * @return array
     */
    public function chMod($flags) {
        return driverCommand::run('chmodNode', array(
            'nodetype' => 'file',
            'nid' => $this->id,
            'flags' => $flags,
        ));
    }
    
    
    public function changeCRUD($segment, $create, $read, $update, $delete) {
        $me = driverCommand::run('getNodes', array(
            'nodetype' => 'file',
            'fields' => '`access`',
            'where' => '`id` = '.$this->id,
        ));
        $ncrud = decbin($me[$this->id]['access']);
        $require = ($create?1:0).($read?1:0).($update?1:0).($delete?1:0);
        switch ($segment) {
            case driverFileManager::CHANGECRUD_ALL:
                $require = substr($ncrud, 0, 8).$require;
                break;
            case driverFileManager::CHANGECRUD_GROUP:
                $require = substr($ncrud, 0, 4).$require.substr($ncrud, 8);
                break;
            case driverFileManager::CHANGECRUD_OWNER:
                $require = $require.substr($ncrud, 4);
                break;
        }
        $require = bindec($require);
        return $this->chMod($require);
    }
    
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
                    'isfolder' => true,
                    'path' => $this->path.$name.'/',
                    'realpath' => $this->realpath.$name.'/',
                    'parent' => $this->id,
                    'mimetype' => 'inode/directory'
                );
                $nresp = driverCommand::run('addNode', $params);
                if ($nresp['ok']) {
                    $preResp = new stdClass();
                    $preResp->isfolder = $params['isfolder'];
                    $preResp->path = $params['path'];
                    $preResp->realpath = $params['realpath'];
                    $preResp->parent = $params['parent'];
                    $preResp->mimetype = $params['mimetype'];
                    $preResp->id = $nresp['nid'];
                    $resp = new driverFileManagerFile($preResp);
                }
            }
        }
        return $resp;
    }
    
    /**
     * Remove a file or folder
     * @param string/driverFileManagerFile $name File or folder to remove
     * @param boolean $recursive Delete childs recursively. If is a folder and not empty, remove content?
     * @return boolean TRUE if Ok.
     */
    public function rm($name, $recursive = false) {
        if (!is_a($name, 'driverFileManagerFile')) {
            driverFileManager::clearName($name);
            $child = $this->getChilds($name, false);
            if (count($child) == 0) {
                return false;
            } else {
                $child = $child[0];
            }
        } else {
            $child = $name;
            // I only can remove my childs.
            if ($child->getParent() != $this->id) {
                return false;
            }
        }
        $subChildsCnt = $child->getChildsCount(false);
        if ($subChildsCnt > 0) {
            if (!$recursive) {
                return false;
            } else {
                // I get childs that the user can see
                $subChilds = $child->getChilds('*', true);
                foreach($subChilds as $subChild) {
                    // Childs can be files or folders
                    $resp = $child->rm($subChild, $recursive);
                    if (!$resp) return false;
                }
                // At the end, if I have childs then I cant erase it.
                $subChildsCnt = $child->getChildsCount(false);
                if ($subChildsCnt > 0) {
                    return false;
                }
            }
        }
        return driverFileManager::rmFileEntity($child);
    }
    
    // Getters and Setters
    
    /**
     * Return amount of childs
     * @param boolean $secured Default TRUE, it only must be used by programmatic calls
     * @return integer
     */
    public function getChildsCount($secured = true) {
        $node = driverNodes::getNodes(array(
            'nodetype' => 'file',
            'count' => 1,
            'where' => '`parent` = '.$this->getId(),
        ), $secured);
        return $node[0]['amount'];
    }
    
    /**
     * Return child files and folders
     * @param string $pattern Filter pattern
     * @param boolean $secured Default TRUE, it only must be used by programmatic calls
     * @return array of type driverFileManagerFile
     */
    public function getChilds($pattern = "*", $secured = true) {
        $node = driverNodes::getNodes(array(
            'nodetype' => 'file',
            'where' => '`parent` = '.$this->getId(),
        ), $secured);
        $resp = array();
        foreach($node as $key => $file) {
            $eFile = new driverFileManagerFile((object) $file);
            if (fnmatch($pattern, $eFile->getName())) {
                $resp[] = $eFile;
            }
        }
        return $resp;
    }
    
    /**
     * @return string File name
     */
    public function getName() {
        $path = $this->getPath();
        if ($this->isFolder()) {
            if (driverTools::str_end("/", $path)) {
                $path = substr($path, 0, strlen($path) - 1);
            }
        }
        $fInfo = driverTools::pathInfo($path);
        return $fInfo['filename'];
    }
    
    public function getParentPath() {
        $resp = $this->getParentEntity();
        return $resp->getPath();
    }
    
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
    
    /**
     * Get download URL
     * @return string Download URL, or empty string if is a folder.
     */
    public function getDownloadPath() {
        if ($this->isFile()) {
            return CMS_DEFAULT_URL_BASE.'?command=pfmDownload&path='.urlencode($this->getPath()).'&interface=nothing';
        } else {
            return '';
        }
    }
    
    /**
     * @return driverFileManagerFile
     */
    public function getParentEntity() {
        return driverFileManager::getById($this->parent);
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