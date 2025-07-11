<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
defined('_JEXEC') or die();
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Phoca\PhocaDownload\MVC\Model\AdminModelTrait;
jimport('joomla.application.component.modellist');
jimport( 'joomla.filesystem.folder' );
jimport( 'joomla.filesystem.file' );

class PhocaDownloadCpModelPhocaDownloadUploads extends ListModel
{

	use AdminModelTrait;
	protected	$option 		= 'com_phocadownload';
	public 		$context		= 'com_phocadownload.phocadownloaduploads';

	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'a.id',
				'username', 'ua.username',
				'usernameno', 'ua.usernameno',
				'checked_out', 'a.checked_out',
				'checked_out_time', 'a.checked_out_time',
				'category_id', 'category_id',
				'state', 'a.state',
				'access', 'a.access', 'access_level',
				'ordering', 'a.ordering',
				'language', 'a.language',
				'hits', 'a.hits',
				'published','a.published',
				'authorized','a.approved'
			);
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = 'username', $direction = 'ASC')
	{
		// Initialise variables.
		$app = Factory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);
/*
		$accessId = $app->getUserStateFromRequest($this->context.'.filter.access', 'filter_access', null, 'int');
		$this->setState('filter.access', $accessId);
*/
		$state = $app->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '', 'string');
		$this->setState('filter.published', $state);
/*
		$categoryId = $app->getUserStateFromRequest($this->context.'.filter.category_id', 'filter_category_id', null);
		$this->setState('filter.category_id', $categoryId);

		$language = $app->getUserStateFromRequest($this->context.'.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);
*/
		// Load the parameters.
		$params = ComponentHelper::getParams('com_phocadownload');
		$this->setState('params', $params);

		// List state information.
		parent::populateState($ordering, $direction);
	}

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id	.= ':'.$this->getState('filter.search');
		//$id	.= ':'.$this->getState('filter.access');
		$id	.= ':'.$this->getState('filter.published');

		return parent::getStoreId($id);
	}

	protected function getListQuery()
	{
		/*
		$query = ' SELECT a.*, fa.countfaid, fn.countfnid, 0 AS checked_out'
			. ' FROM #__users AS a'
			. ' LEFT JOIN #__phocadownload AS f ON f.owner_id = a.id '
			. ' LEFT JOIN #__phocadownload_categories AS cc ON cc.id = f.catid '
			. ' LEFT JOIN #__phocadownload_sections AS s ON s.id = f.sectionid '
			. ' LEFT JOIN #__groups AS g ON g.id = f.access '
			. ' LEFT JOIN #__users AS aa ON aa.id = f.checked_out '


			. ' LEFT JOIN (SELECT  fa.owner_id, fa.id, count(*) AS countfaid'
			. ' FROM #__phocadownload AS fa'
			. ' WHERE fa.approved = 1'
			. ' GROUP BY fa.owner_id) AS fa '
			. ' ON a.id = fa.owner_id'

			. ' LEFT JOIN (SELECT  fn.owner_id, fn.id, count(*) AS countfnid'
			. ' FROM #__phocadownload AS fn'
			. ' WHERE fn.approved = 0'
			. ' GROUP BY fn.owner_id) AS fn '
			. ' ON a.id = fn.owner_id'

			. $where
			. ' GROUP by a.id'
			. $orderby;
		*/
		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'a.id'
			)
		);
		$query->from('#__users AS a');

		// Join over the language
		//$query->select('l.title AS language_title');
		//$query->join('LEFT', '#__languages AS l ON l.lang_code = a.language');


		$query->select('GROUP_CONCAT(DISTINCT f.id) AS file_id');
		$query->join('LEFT', '#__phocadownload AS f ON f.owner_id = a.id');

		$query->select('cc.id as category_id');
		$query->join('LEFT', '#__phocadownload_categories AS cc ON cc.id = f.catid');

		// Join over the users for the checked out user.
		//$query->select('ua.id AS userid, ua.username AS username, ua.name AS usernameno');
		//$query->join('LEFT', '#__users AS ua ON ua.id=a.userid');

		$query->select('ua.id AS userid, ua.username AS username, ua.name AS usernameno');
		$query->join('LEFT', '#__users AS ua ON ua.id=f.owner_id');

		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id=f.checked_out');

		$query->select('fa.countfaid');
		$query->join('LEFT', '(SELECT  fa.owner_id, count(*) AS countfaid'
			. ' FROM #__phocadownload AS fa'
			. ' WHERE fa.approved = 1'
			. ' GROUP BY fa.owner_id) AS fa '
			. ' ON a.id = fa.owner_id ');


		$query->select('fn.countfnid');
		$query->join('LEFT', '(SELECT fn.owner_id, count(*) AS countfnid'
			. ' FROM #__phocadownload AS fn'
			. ' WHERE fn.approved = 0'
			. ' GROUP BY fn.owner_id) AS fn '
			. ' ON a.id = fn.owner_id');



/*		// Join over the asset groups.
		$query->select('ag.title AS access_level');
		$query->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');
*/

		// Filter by access level.
	/*	if ($access = $this->getState('filter.access')) {
			$query->where('a.access = '.(int) $access);
		}*/

		// Filter by published state.
		/*$published = $this->getState('filter.published');
		if (is_numeric($published)) {
			$query->where('a.published = '.(int) $published);
		}
		else if ($published === '') {
			$query->where('(a.published IN (0, 1))');
		}*/

		$query->where('a.id > 0');
		$query->where('(fa.countfaid > 0 OR fn.countfnid > 0)');

		// Filter by category.
		/*$categoryId = $this->getState('filter.category_id');
		if (is_numeric($categoryId)) {
			$query->where('a.catid = ' . (int) $categoryId);
		}*/

		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0) {
				$query->where('a.id = '.(int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%'.$db->escape($search, true).'%');
				$query->where('( ua.username LIKE '.$search.' OR ua.name LIKE '.$search.')');
			}
		}

		$query->group('a.username, ua.id, a.id, a.name, cc.id, ua.username, ua.name, uc.name, fa.countfaid, fn.countfnid');

		// Add the list ordering clause.
		//$orderCol	= $this->state->get('list.ordering');
		//$orderDirn	= $this->state->get('list.direction');
		$orderCol	= $this->state->get('list.ordering', 'username');
		$orderDirn	= $this->state->get('list.direction', 'asc');


		if ($orderCol == 'a.id' || $orderCol == 'username') {
			$orderCol = 'a.username '.$orderDirn.', a.id';
		}
		$query->order($db->escape($orderCol.' '.$orderDirn));

	//	echo nl2br(str_replace('#__', 'jos_', $query->__toString()));
		return $query;
	}
}
?>
