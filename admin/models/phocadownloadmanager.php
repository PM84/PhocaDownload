<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Object\CMSObject;
use Joomla\Filesystem\Path;
use Phoca\PhocaDownload\MVC\Model\AdminModelTrait;
jimport('joomla.application.component.modeladmin');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class PhocaDownloadCpModelPhocaDownloadManager extends AdminModel
{

    use AdminModelTrait;

	protected	$option 		= 'com_phocadownload';
	protected $text_prefix 		= 'com_phocadownload';
	public 		$typeAlias 		= 'com_phocadownload.phocadownloadmanager';

	public function getTable($type = 'PhocaDownload', $prefix = 'Table', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}


	public function getForm($data = array(), $loadData = true) {

		$form 	= $this->loadForm('com_phocadownload.phocadownloadmanager', 'phocadownloadmanager', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}
		return $form;
	}

	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_phocadownloadm.edit.phocadownloadm.data', array());

		if (empty($data)) {
			$data = $this->getItem();
		}

		return $data;
	}

	function getFolderState($property = null) {
		static $set;

		if (!$set) {
			$folder		= Factory::getApplication()->input->get( 'folder', '', '', 'path' );
			$upload		= Factory::getApplication()->input->get( 'upload', '', '', 'int' );
			$manager	= Factory::getApplication()->input->get( 'manager', '', '', 'path' );

			$this->setState('folder', $folder);
			$this->setState('manager', $manager);

			$parent = str_replace("\\", "/", dirname($folder));
			$parent = ($parent == '.') ? null : $parent;
			$this->setState('parent', $parent);

			$set = true;
		}
		return parent::getState($property);
	}

	function getFiles() {
		$list = $this->getList();
		return $list['files'];
	}

	function getFolders() {
		$list = $this->getList();
		return $list['folders'];
	}

	function getList() {
		static $list;

		//Params
		$params	= ComponentHelper::getParams( 'com_phocadownload' );

		// Only process the list once per request
		if (is_array($list)) {
			return $list;
		}

		// Get current path from request
		$current = $this->getState('folder');

		// If undefined, set to empty
		if ($current == 'undefined') {
			$current = '';
		}

		// File Manager, Icon Manager
		$manager = $this->getState('manager');
		if ($manager == 'undefined') {
			$manager = '';
		}
		$path 	= PhocaDownloadPath::getPathSet($manager);
		$group	= PhocaDownloadSettings::getManagerGroup($manager);

		//$path = PhocaDownloadPath::getPathSet();

		// Initialize variables
		if (strlen($current) > 0) {
			$orig_path = $path['orig_abs_ds'].$current;
		} else {
			$orig_path = $path['orig_abs_ds'];
		}
		$orig_path_server 	= str_replace('\\', '/', $path['orig_abs'] .'/');


		// Absolute Path defined by user
		$absolutePath	= $params->get('absolute_path', '');
		$absolutePath	= str_replace('\\', '/', $absolutePath);
		// Be aware - absolute path is not set for images folder and for preview and play folder - see documentation
		if ($absolutePath != '' && $group['f'] == 1) {
			$orig_path_server 		= str_replace('\\', '/', JPath::clean($absolutePath .'/') );//$absolutePath ;
		}

		$files 		= array ();
		$folders 	= array ();



		// Get the list of files and folders from the given folder
		$file_list 		= Folder::files($orig_path);
		$folder_list 	= Folder::folders($orig_path, '', false, false, array());

		// Iterate over the files if they exist
		//file - abc.img, file_no - folder/abc.img
		if ($file_list !== false) {
			foreach ($file_list as $file) {
				if (is_file($orig_path.'/'.$file) && substr($file, 0, 1) != '.' && strtolower($file) !== 'index.html') {
						$tmp 							= new CMSObject();
						$tmp->name 						= basename($file);
						$tmp->path_with_name 			= str_replace('\\', '/', Path::clean($orig_path . '/' . $file));
						$tmp->path_without_name_relative= $path['orig_rel_ds'] . str_replace($orig_path_server, '', $tmp->path_with_name);
						$tmp->path_with_name 			= str_replace('\\', '/', Path::clean($orig_path . '/' . $file));
						$tmp->path_with_name_relative_no= str_replace($orig_path_server, '', $tmp->path_with_name);
						$files[] = $tmp;

				}
			}
		}

		// Iterate over the folders if they exist
		if ($folder_list !== false) {
			foreach ($folder_list as $folder)
			{
				$tmp 							= new CMSObject();
				$tmp->name 						= basename($folder);
				$tmp->path_with_name 			= str_replace('\\', '/', Path::clean($orig_path . '/' . $folder));
				$tmp->path_without_name_relative= $path['orig_rel_ds'] . str_replace($orig_path_server, '', $tmp->path_with_name);
				$tmp->path_with_name_relative_no= str_replace($orig_path_server, '', $tmp->path_with_name);

				$folders[] = $tmp;
			}
		}

		$list = array('folders' => $folders, 'files' => $files);
		return $list;
	}
}
?>
