<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
defined( '_JEXEC' ) or die();
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Utilities\ArrayHelper;
use Phoca\PhocaDownload\MVC\Model\AdminModelTrait;

jimport( 'joomla.application.component.modellist' );
jimport( 'joomla.filesystem.folder' );
jimport( 'joomla.filesystem.file' );

class PhocaDownloadCpModelPhocaDownloadFiles extends ListModel
{
	use AdminModelTrait;
	protected	$option 		= 'com_phocadownload';

	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'a.id',
				'title', 'a.title',
				'alias', 'a.alias',
				'checked_out', 'a.checked_out',
				'checked_out_time', 'a.checked_out_time',
				'category_id', 'category_id',
				'state', 'a.state',
				'access', 'a.access', 'access_level',
				'ordering', 'a.ordering',
				'language', 'a.language',
				'date', 'a.date',
				'hits', 'a.hits',
				'average', 'v.average',
				'published','a.published',
				'filename', 'a.filename',
				'autorized', 'a.approved',
				'uploadusername', 'uploadusername',
				'owner_id', 'a.owner_id'
			);
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = 'a.title', $direction = 'ASC')
	{
		// Initialise variables.
		$app = Factory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$accessId = $app->getUserStateFromRequest($this->context.'.filter.access', 'filter_access', null, 'int');
		$this->setState('filter.access', $accessId);

		$state = $app->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '', 'string');
		$this->setState('filter.published', $state);

		$categoryId = $app->getUserStateFromRequest($this->context.'.filter.category_id', 'filter_category_id', null);
		$this->setState('filter.category_id', $categoryId);

		$language = $app->getUserStateFromRequest($this->context.'.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);

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
		$id	.= ':'.$this->getState('filter.access');
		$id	.= ':'.$this->getState('filter.published');
		//$id	.= ':'.$this->getState('filter.category_id');
		$id .= ':' . serialize($this->getState('filter.category_id'));
		$id	.= ':'.$this->getState('filter.image_id');

		return parent::getStoreId($id);
	}


	protected function getListQuery()
	{
		/*
		SELECT a.*, cc.title AS categorytitle, s.title AS sectiontitle, u.name AS editor, g.name AS groupname, us.id AS ownerid, us.username AS ownername '
			. ' FROM #__phocadownload AS a '
			. ' LEFT JOIN #__phocadownload_categories AS cc ON cc.id = a.catid'
			. ' LEFT JOIN #__phocadownload_sections AS s ON s.id = a.sectionid'
			. ' LEFT JOIN #__groups AS g ON g.id = a.access'
			. ' LEFT JOIN #__users AS u ON u.id = a.checked_out'
			. ' LEFT JOIN #__users AS us ON us.id = a.owner_id'
		*/
		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'a.*'
			)
		);
		$query->from('`#__phocadownload` AS a');

		// Join over the language
		$query->select('l.title AS language_title');
		$query->join('LEFT', '`#__languages` AS l ON l.lang_code = a.language');

		// Join over the users for the checked out user.


		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');

		$query->select('uua.id AS uploaduserid, uua.username AS uploadusername, uua.name AS uploadname');
		$query->join('LEFT', '#__users AS uua ON uua.id=a.userid');

		// Join over the asset groups.
		$query->select('ag.title AS access_level');
		$query->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');

		// Join over the categories.
		$query->select('c.title AS category_title, c.id AS category_id');
		$query->join('LEFT', '#__phocadownload_categories AS c ON c.id = a.catid');

		$query->select('ua.id AS userid, ua.username AS username, ua.name AS usernameno');
		$query->join('LEFT', '#__users AS ua ON ua.id = a.owner_id');

		//$query->select('v.average AS ratingavg');
		//$query->join('LEFT', '#__phocadownload_img_votes_statistics AS v ON v.imgid = a.id');

		// Filter by access level.
		if ($access = $this->getState('filter.access')) {
			$query->where('a.access = '.(int) $access);
		}

		// Filter by published state.
		$published = $this->getState('filter.published');
		if (is_numeric($published)) {
			$query->where('a.published = '.(int) $published);
		}
		else if ($published === '') {
			$query->where('(a.published IN (0, 1))');
		}

		// Filter by category.
		//$categoryId = $this->getState('filter.category_id');

		$categoryId = $this->getState('filter.category_id', []);

        if (!\is_array($categoryId)) {
            $categoryId = $categoryId ? [$categoryId] : [];
        }

		/*if (is_numeric($categoryId)) {
			$query->where('a.catid = ' . (int) $categoryId);
		}*/
		 if (\count($categoryId)) {
            $categoryId       = ArrayHelper::toInteger($categoryId);
            $subCatItemsWhere = [];
            foreach ($categoryId as $k => $v) {
				$categoryWhere = 'a.catid = ' . (int) $v;
                $subCatItemsWhere[] = '(' . $categoryWhere . ')';
            }
            $query->where('(' . implode(' OR ', $subCatItemsWhere) . ')');
        }



		// Filter on the language.
		if ($language = $this->getState('filter.language')) {
			$query->where('a.language = ' . $db->quote($language));
		}

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
				$query->where('( a.title LIKE '.$search.' OR a.filename LIKE '.$search.')');
			}
		}

		//$query->group('a.id, a.catid, a.sectionid, a.sid, a.owner_id, a.title, a.alias, a.filename, a.filename_play, a.filename_preview, a.filesize, a.author, a.author_email');

		// Add the list ordering clause.
		//$orderCol	= $this->state->get('list.ordering');
		//$orderDirn	= $this->state->get('list.direction');
		$orderCol	= $this->state->get('list.ordering', 'title');
		$orderDirn	= $this->state->get('list.direction', 'asc');
		if ($orderCol == 'a.ordering' || $orderCol == 'category_title') {
			$orderCol = 'category_title '.$orderDirn.', a.ordering';
		}
		$query->order($db->escape($orderCol.' '.$orderDirn));

		//echo nl2br(str_replace('#__', 'jos_', $query->__toString()));
		return $query;
	}

	public function getNotApprovedFile() {

		$query = 'SELECT COUNT(a.id) AS count'
			.' FROM #__phocadownload AS a'
			.' WHERE approved = 0';
		$this->_db->setQuery($query, 0, 1);
		$countNotApproved = $this->_db->loadObject();
		return $countNotApproved;
	}
}
?>
