<?php
/**
 * @package   Phoca Download
 * @author    Jan Pavelka - https://www.phoca.cz
 * @copyright Copyright (C) Jan Pavelka https://www.phoca.cz
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 and later
 * @cms       Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */
defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\CMS\Version;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Filesystem\File;
use Joomla\CMS\Uri\Uri;

// Frontend editor - button plugin
require_once JPATH_ADMINISTRATOR . '/components/com_phocadownload/libraries/autoloadPhoca.php';

use Phoca\Render\Adminviews;


class PhocaDownloadRenderAdminViews extends AdminViews



{

	public $view        = '';
    public $viewtype    = 1;
    public $option      = '';
    public $optionLang  = '';
    public $tmpl        = '';
    public $compatible  = false;
    public $sidebar     = true;
    protected $document	= false;




	public function __construct(){

        $version = new Version();
        $is42    = $version->isCompatible('4.2.0-beta');

        if ($is42) {
            $this->document = Factory::getDocument();
            $wa             = $this->document->getWebAssetManager();
            $wa->useScript('table.columns')->useScript('multiselect');
        }

		parent::__construct();
	}

/*
	public function startMainContainer() {

		$o = array();

		if ($this->compatible) {

			// Joomla! 4

			$o[] = '<div class="row">';
			if ($this->sidebar) {

				$o[] = '<div id="j-main-container" class="col-md-12">';
			} else {

				$o[] = '<div id="j-sidebar-container" class="col-md-2">'.JHtmlSidebar::render().'</div>';
				$o[] = '<div id="j-main-container" class="col-md-10">';
			}


		} else {
			$o[] = '<div id="j-sidebar-container" class="span2">'.JHtmlSidebar::render().'</div>';
			$o[] = '<div id="j-main-container" class="span10">';
		}

		return implode("\n", $o);
	}

	public function endMainContainer() {
		$o = array();

		$o[] = '</div>';
		if ($this->compatible) {
			$o[] = '</div>';
		}
		return implode("\n", $o);
	}


	public function jsJorderTable($listOrder) {

		$js = 'Joomla.orderTable = function() {' . "\n"
		.'  table = document.getElementById("sortTable");' . "\n"
		.'  direction = document.getElementById("directionTable");' . "\n"
		.'  order = table.options[table.selectedIndex].value;' . "\n"
		.'  if (order != \''. $listOrder.'\') {' . "\n"
		.'    dirn = \'asc\';' . "\n"
		.'	} else {' . "\n"
		.'    dirn = direction.options[direction.selectedIndex].value;' . "\n"
		.'  }' . "\n"
		.'  Joomla.tableOrdering(order, dirn, \'\');' . "\n"
		.'}' . "\n";
		Factory::getDocument()->addScriptDeclaration($js);
	}

	public function startForm($option, $view, $id = 'adminForm', $name = 'adminForm') {
		return '<div id="'.$view.'"><form action="'.Route::_('index.php?option='.$option.'&view='.$view).'" method="post" name="'.$name.'" id="'.$id.'">'."\n";
	}

	public function endForm() {
		return '</form>'."\n".'</div>'."\n";
	}

	public function selectFilterPublished($txtSp, $state) {
		return '<div class="btn-group pull-right ph-select-status">'. "\n"
		.'<select name="filter_published" class="form-control" onchange="this.form.submit()">'."\n"
		. '<option value="">'.Text::_($txtSp).'</option>'
		. HTMLHelper::_('select.options', HTMLHelper::_('jgrid.publishedOptions', array('archived' => 0, 'trash' => 0)), 'value', 'text', $state, true)
		.'</select></div>'. "\n";
	}

	public function selectFilterActived($txtSp, $state) {


		switch($state) {
			case '0':
				$aS = '';
				$nS = 'selected';
				$n = '';

			break;
			case '1':
				$aS = 'selected';
				$nS = '';
				$n = '';
			break;
			default:
				$aS = '';
				$nS = '';
				$n = 'selected';
			break;
		}

		return '<div class="btn-group pull-right ph-select-status">'. "\n"
		.'<select name="filter_actived" class="form-control" onchange="this.form.submit()">'."\n"
		. '<option value="" '.$n.'>- '.Text::_($txtSp).' -</option>'
		. '<option value="0" '.$nS.'>'.Text::_('COM_PHOCAEMAIL_NOT_ACTIVE').'</option>'
		. '<option value="1" '.$aS.'>'.Text::_('COM_PHOCAEMAIL_ACTIVE').'</option>'
		//. JHtml::_('select.options', JHtml::_('jgrid.publishedOptions', array()), 'value', 'text', $state, true)
		.'</select></div>'. "\n";
	}

	public function selectFilterType($txtSp, $type, $typeList) {
		return '<div class="btn-group pull-right">'. "\n"
		.'<select name="filter_type" class="form-control" onchange="this.form.submit()">'."\n"
		. '<option value="">'.Text::_($txtSp).'</option>'
		. HTMLHelper::_('select.options', $typeList, 'value', 'text', $type, true)
		.'</select></div>'. "\n";
	}

	public function selectFilterLanguage($txtLng, $state) {
		return '<div class="btn-group pull-right">'. "\n"
		.'<select name="filter_language" class="form-control" onchange="this.form.submit()">'."\n"
		. '<option value="">'.Text::_($txtLng).'</option>'
		. HTMLHelper::_('select.options', HTMLHelper::_('contentlanguage.existing', true, true), 'value', 'text', $state)
		.'</select></div>'. "\n";
	}

	public function selectFilterCategory($categoryList, $txtLng, $state) {
		return '<div class="btn-group pull-right ">'. "\n"
		.'<select name="filter_category_id" class="form-control" onchange="this.form.submit()">'."\n"
		. '<option value="">'.Text::_($txtLng).'</option>'
		. HTMLHelper::_('select.options', $categoryList, 'value', 'text', $state)
		. '</select></div>'. "\n";
	}

	public function selectFilterLevels($txtLng, $state) {
		$levelList = array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5);
		return
		'<div class="btn-group pull-right">'. "\n"
		.'<select name="filter_level" class="form-control" onchange="this.form.submit()">'."\n"
		. '<option value="">'.Text::_($txtLng).'</option>'
		. HTMLHelper::_('select.options', $levelList, 'value', 'text', $state)
		. '</select></div>'. "\n";
	}

	public function inputFilterSearch($txtSl, $txtSd, $state) {
		return '<div class="filter-search btn-group pull-left">'. "\n"
		.'<label for="filter_search" class="element-invisible">'.Text::_($txtSl).'</label>'. "\n"
		.'<input type="text" name="filter_search" placeholder="'.Text::_($txtSd).'" id="filter_search"'
		.' value="'.$state.'" title="'.Text::_($txtSd).'" />'. "\n"
		.'</div>'. "\n";
	}

	/*public function inputFilterSearchClear($txtFs, $txtFc) {
		return '<div class="btn-group pull-left hidden-phone">'. "\n"
		.'<button class="btn tip hasTooltip" type="submit" title="'.Text::_($txtFs).'"><i class="icon-search"></i></button>'. "\n"
		.'<button class="btn tip hasTooltip" type="button" onclick="document.id(\'filter_search\').value=\'\';this.form.submit();"'
		.' title="'.Text::_($txtFc).'"><i class="icon-remove"></i></button>'. "\n"
		.'</div>'. "\n";
	}*//*

	public function inputFilterSearchClear($txtFs, $txtFc) {
		return '<div class="btn-group pull-left hidden-phone">'. "\n"
		.'<button class="btn tip hasTooltip" type="submit" title="'.Text::_($txtFs).'"><i class="icon-search"></i></button>'. "\n"
		.'<button class="btn tip hasTooltip" type="button" onclick="document.getElementById(\'filter_search\').value=\'\';this.form.submit();"'
		.' title="'.Text::_($txtFc).'"><i class="icon-remove"></i></button>'. "\n"
		.'</div>'. "\n";
	}

	public function inputFilterSearchLimit($txtSl, $paginationLimitBox) {
		return '<div class="btn-group pull-right hidden-phone">'. "\n"
		.'<label for="limit" class="element-invisible">'.Text::_($txtSl).'</label>'. "\n"
		.$paginationLimitBox ."\n" . '</div>'. "\n";
	}

	public function selectFilterDirection($txtOd, $txtOasc, $txtOdesc, $listDirn) {
		$ascDir = $descDir = '';
		if ($listDirn == 'asc') {$ascDir = 'selected="selected"';}
		if ($listDirn == 'desc') {$descDir = 'selected="selected"';}
		return '<div class="btn-group pull-right hidden-phone">'. "\n"
		.'<label for="directionTable" class="element-invisible">' .Text::_('JFIELD_ORDERING_DESC').'</label>'. "\n"
		.'<select name="directionTable" id="directionTable" class="input-medium" onchange="Joomla.orderTable()">'. "\n"
		.'<option value="">' .Text::_('JFIELD_ORDERING_DESC').'</option>'. "\n"
		.'<option value="asc" '.$ascDir.'>' . Text::_('JGLOBAL_ORDER_ASCENDING').'</option>'. "\n"
		.'<option value="desc" '.$descDir.'>' . Text::_('JGLOBAL_ORDER_DESCENDING').'</option>'. "\n"
		.'</select>'. "\n"
		.'</div>'. "\n";
	}

	public function selectFilterSortBy($txtSb, $sortFields, $listOrder) {
		return '<div class="btn-group pull-right">'. "\n"
		.'<label for="sortTable" class="element-invisible">'.Text::_($txtSb).'</label>'. "\n"
		.'<select name="sortTable" id="sortTable" class="input-medium" onchange="Joomla.orderTable()">'. "\n"
		.'<option value="">'.Text::_($txtSb).'</option>'. "\n"
		. HTMLHelper::_('select.options', $sortFields, 'value', 'text', $listOrder). "\n"
		.'</select>'. "\n"
		.'</div>'. "\n";
	}

	public function startTable($id) {
		return '<table class="table table-striped" id="'.$id.'">'. "\n";
	}

	public function endTable() {
		return '</table>'. "\n";
	}
	public function tblFoot($listFooter, $columns) {
		return '<tfoot>' . "\n" . '<tr><td colspan="'.(int)$columns.'">'.$listFooter.'</td></tr>'. "\n".'</tfoot>'. "\n";
	}

	public function startTblHeader() {
		return 	'<thead>'."\n".'<tr>'."\n";
	}

	public function endTblHeader() {
		return 	'</tr>'."\n".'</thead>'."\n";
	}

	public function thOrdering($txtHo, $listDirn, $listOrder ) {
		return '<th class="nowrap center hidden-phone ph-ordering">'. "\n"
		. HTMLHelper::_('searchtools.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', $txtHo). "\n"
		. '</th>';
	}

	public function thOrderingXML($txtHo, $listDirn, $listOrder, $prefix = 'a', $empty = false ) {

		if ($empty) {
			return '<th class="nowrap center text-center ph-ordering"></th>'. "\n";
		}

		return '<th class="nowrap center text-center ph-ordering">'. "\n"
		. HTMLHelper::_('searchtools.sort', '', strip_tags($prefix).'.ordering', $listDirn, $listOrder, null, 'asc', $txtHo, 'icon-menu-2'). "\n"
		. '</th>';
		//JHtml::_('searchtools.sort', $this->t['l'].'_IN_STOCK', 'a.stock', $listDirn, $listOrder ).'</th>'."\n";

	}

	public function thCheck($txtCh) {
		return '<th class="hidden-phone ph-check">'. "\n"
		.'<input type="checkbox" name="checkall-toggle" value="" title="'.Text::_($txtCh).'" onclick="Joomla.checkAll(this)" />'. "\n"
		.'</th>'. "\n";
	}

	public function tdOrder($canChange, $saveOrder, $orderkey, $ordering = 0){

		$o = '<td class="order nowrap center hidden-phone">'. "\n";
		if ($canChange) {
			$disableClassName = '';
			$disabledLabel    = '';
			if (!$saveOrder) {
				$disabledLabel    = Text::_('JORDERINGDISABLED');
				$disableClassName = 'inactive tip-top';
			}
			$o .= '<span class="sortable-handler hasTooltip '.$disableClassName.'" title="'.$disabledLabel.'"><i class="icon-menu"></i></span>'."\n";
		} else {
			$o .= '<span class="sortable-handler inactive"><i class="icon-menu"></i></span>'."\n";
		}
		$orderkeyPlus = $ordering;//$orderkey + 1;
		$o .= '<input type="text" style="display:none" name="order[]" size="5" value="'.$orderkeyPlus.'" />'. "\n"
		.'</td>'. "\n";
		return $o;
	}
/*
	public function tdRating($ratingAvg) {
		$o = '<td class="small hidden-phone">';
		$voteAvg 		= round(((float)$ratingAvg / 0.5)) * 0.5;
		$voteAvgWidth	= 16 * $voteAvg;
		$o .= '<ul class="star-rating-small">'
		.'<li class="current-rating" style="width:'.$voteAvgWidth.'px"></li>'
		.'<li><span class="star1"></span></li>';

		for ($ir = 2;$ir < 6;$ir++) {
			$o .= '<li><span class="stars'.$ir.'"></span></li>';
		}
		$o .= '</ul>';
		$o .='</td>'. "\n";
		return $o;
	}

	public function tdLanguage($lang, $langTitle, $langTitleE ) {

		$o = '<td class="small nowrap hidden-phone">';
		if ($lang == '*') {
			$o .= Text::_('JALL');
		} else {
			if ($langTitle) {
				$o .= $langTitleE;
			} else {
				$o .= Text::_('JUNDEFINED');;
			}
		}
		$o .= '</td>'. "\n";
		return $o;
	}

	/*public function formInputs($listOrder, $originalOrders) {

		return '<input type="hidden" name="task" value="" />'. "\n"
		.'<input type="hidden" name="boxchecked" value="0" />'. "\n"
		.'<input type="hidden" name="filter_order" value="'.$listOrder.'" />'. "\n"
		.'<input type="hidden" name="filter_order_Dir" value="" />'. "\n"
		. HTMLHelper::_('form.token'). "\n"
		.'<input type="hidden" name="original_order_values" value="'. implode(',', $originalOrders).'" />'. "\n";
	}*/
/*
	public function formInputs($listOrder, $listDirn, $originalOrders) {

		return '<input type="hidden" name="task" value="" />'. "\n"
		.'<input type="hidden" name="boxchecked" value="0" />'. "\n"
		.'<input type="hidden" name="filter_order" value="'.$listOrder.'" />'. "\n"
		.'<input type="hidden" name="filter_order_Dir" value="'.$listDirn.'" />'. "\n"
		. HTMLHelper::_('form.token'). "\n"
		.'<input type="hidden" name="original_order_values" value="'. implode(',', $originalOrders).'" />'. "\n";
	}

	public function formInputsXml($listOrder, $listDirn, $originalOrders) {

		return '<input type="hidden" name="task" value="" />'. "\n"
		.'<input type="hidden" name="boxchecked" value="0" />'. "\n"
		//.'<input type="hidden" name="filter_order" value="'.$listOrder.'" />'. "\n"
		//.'<input type="hidden" name="filter_order_Dir" value="'.$listDirn.'" />'. "\n"
		. HTMLHelper::_('form.token'). "\n"
		.'<input type="hidden" name="original_order_values" value="'. implode(',', $originalOrders).'" />'. "\n";
	}

	public function td($value, $class = '') {
		if ($class != ''){
			return '<td class="'.$class.'">'. $value.'</td>'. "\n";
		} else {
			return '<td>'. $value.'</td>'. "\n";
		}
	}




	public function tdPublishDownUp ($publishUp, $publishDown, $langPref) {

		$o				= '';
		$db				= Factory::getDBO();
		//$app			= JFactory::getApplication();
		$nullDate 		= $db->getNullDate();
		$now			= Factory::getDate();
		$config			= Factory::getConfig();
		$publish_up 	= Factory::getDate($publishUp);
		$publish_down 	= Factory::getDate($publishDown);
		$tz 			= new DateTimeZone($config->get('offset'));
		$publish_up->setTimezone($tz);
		$publish_down->setTimezone($tz);


		if ( $now->toUnix() <= $publish_up->toUnix() ) {
			$text = Text::_( $langPref . '_PENDING' );
		} else if ( ( $now->toUnix() <= $publish_down->toUnix() || $publishDown == $nullDate ) ) {
			$text = Text::_( $langPref . '_ACTIVE' );
		} else if ( $now->toUnix() > $publish_down->toUnix() ) {
			$text = Text::_( $langPref . '_EXPIRED' );
		}

		$times = '';
		if (isset($publishUp)) {
			if ($publishUp == $nullDate) {
				$times .= "\n". Text::_( $langPref . '_START') . ': '.Text::_( $langPref . '_ALWAYS' );
			} else {
				$times .= "\n". Text::_( $langPref . '_START') .": ". $publish_up->format("D, d M Y H:i:s");
			}
		}
		if (isset($publishDown)) {
			if ($publishDown == $nullDate) {
				$times .= "\n". Text::_( $langPref . '_FINISH'). ': '. Text::_( $langPref . '_NO_EXPIRY' );
			} else {
				$times .= "\n". Text::_( $langPref . '_FINISH') .": ". $publish_down->format("D, d M Y H:i:s");
			}
		}

		if ( $times ) {
			$o .= '<td align="center">'
				.'<span class="editlinktip hasTip" title="'. Text::_( $langPref . '_PUBLISH_INFORMATION' ).': '. $times.'">'
				.'<a href="javascript:void(0);" >'. $text.'</a></span>'
				.'</td>'. "\n";
		} else {
			$o .= '<td></td>'. "\n";
		}
		return $o;
	}

	public function saveOrder($t, $listDirn) {

		$saveOrderingUrl = 'index.php?option=' . $t['o'] . '&task=' . $t['tasks'] . '.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
		if ($this->compatible) {
			HTMLHelper::_('draggablelist.draggable');
		} else {
			HTMLHelper::_('sortablelist.sortable', 'categoryList', 'adminForm', strtolower($listDirn), $saveOrderingUrl, false, true);
		}

		return $saveOrderingUrl;
	}

	public function firstColumnHeader($listDirn, $listOrder, $prefix = 'a', $empty = false) {
		if ($this->compatible) {
			// to do empty
			return '<th class="w-1 text-center ph-check">'. HTMLHelper::_('grid.checkall').'</td>';
		} else {
			return $this->thOrderingXML('JGRID_HEADING_ORDERING', $listDirn, $listOrder, $prefix, $empty);
		}
	}

	public function secondColumnHeader($listDirn, $listOrder, $prefix = 'a', $empty = false) {
		if ($this->compatible) {
			return $this->thOrderingXML('JGRID_HEADING_ORDERING', $listDirn, $listOrder, $prefix, $empty);
		} else {
			// to do empty
			return $this->thCheck('JGLOBAL_CHECK_ALL');
		}
	}

	public function startTblBody($saveOrder, $saveOrderingUrl, $listDirn) {

		$o = array();

		if ($this->compatible) {
			$o[] = '<tbody';
			if ($saveOrder){
				$o[] = ' class="js-draggable" data-url="'. $saveOrderingUrl.'" data-direction="'. strtolower($listDirn).'" data-nested="true"';
			}
			$o[] = '>';

		} else {
			$o[] = '<tbody>'. "\n";
		}

		return implode("", $o);
	}

	public function endTblBody() {
		return '</tbody>'. "\n";
	}

	public function startTr($i, $catid = 0){
		$iD = $i % 2;
		if ($this->compatible) {
			return '<tr class="row'.$iD.'" data-draggable-group="'. $catid.'">'. "\n";
		} else {

			return '<tr class="row'.$iD.'" sortable-group-id="'.$catid.'" >'. "\n";
		}
	}

	public function endTr() {
		return '</tr>'."\n";
	}

	public function firstColumn($i, $itemId, $canChange, $saveOrder, $orderkey, $ordering) {
		if ($this->compatible) {
			return $this->td( HTMLHelper::_('grid.id', $i, $itemId), 'text-center');
		} else {
			return $this->tdOrder($canChange, $saveOrder, $orderkey, $ordering);
		}
	}

	public function secondColumn($i, $itemId, $canChange, $saveOrder, $orderkey, $ordering) {

		if ($this->compatible) {

			$o = array();
			$o[] = '<td class="text-center d-none d-md-table-cell">';

			$iconClass = '';
			if (!$canChange) {
				$iconClass = ' inactive';
			} else if (!$saveOrder) {
				$iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
			}

			$o[] = '<span class="sortable-handler'. $iconClass.'"><span class="fas fa-ellipsis-v" aria-hidden="true"></span></span>';

			if ($canChange && $saveOrder) {
				$o[] = '<input type="text" name="order[]" size="5" value="' . $ordering . '" class="width-20 text-area-order hidden">';
			}

			$o[] = '</td>';

			return implode("", $o);

		} else {
			return $this->td(HTMLHelper::_('grid.id', $i, $itemId), "small ");
		}
	}


	public function startFilter($txtFilter = ''){
		$o = '<div id="j-sidebar-container" class="span2">'."\n". JHtmlSidebar::render()."\n";

		if ($txtFilter != '') {



			$o .= '<hr />'."\n" . '<div class="filter-select ">'."\n"
			. '<h4 class="page-header">'. Text::_($txtFilter).'</h4>'."\n";
		} else {
			$o .= '<div>';

		}

		return $o;
	}

	public function endFilter() {
		return '</div>' . "\n" . '</div>' . "\n";
	}

	public function startFilterBar($id = 0) {
		if ((int)$id > 0) {
			return '<div id="filter-bar'.$id.'" class="btn-toolbar ph-btn-toolbar-'.$id.'">'. "\n";
		} else {
			return '<div id="filter-bar'.$id.'" class="btn-toolbar">'. "\n";
		}

	}

	public function endFilterBar() {
		return '</div>' . "\n" . '<div class="clearfix"> </div>'. "\n";
	}



	public function tdImage($item, $button, $txtE, $class = '', $avatarAbs = '', $avatarRel = '') {
		$o = '<td class="'.$class.'">'. "\n";
		$o .= '<div class="pg-msnr-container"><div class="phocagallery-box-file">'. "\n"
			.' <center>'. "\n"
			.'  <div class="phocagallery-box-file-first">'. "\n"
			.'   <div class="phocagallery-box-file-second">'. "\n"
			.'    <div class="phocagallery-box-file-third">'. "\n"
			.'     <center>'. "\n";

		if ($avatarAbs != '' && $avatarRel != '') {
			// AVATAR
			if (PhocaDownloadFile::exists($avatarAbs.$item->avatar)){
				$o .= '<a class="'. $button->methodname.'"'
				//.' title="'. $button->text.'"'
				.' href="'.Uri::root().$avatarRel.$item->avatar.'" '
				//.' rel="'. $button->options.'"'
				. ' >'
				.'<img src="'.Uri::root().$avatarRel.$item->avatar.'?imagesid='.md5(uniqid(time())).'" alt="'.Text::_($txtE).'" />'
				.'</a>';
			} else {
				$o .= HTMLHelper::_( 'image', '/media/com_phocagallery/images/administrator/phoca_thumb_s_no_image.gif', '');
			}
		} else {
			// PICASA
			if (isset($item->extid) && $item->extid !='') {

				$resW				= explode(',', $item->extw);
				$resH				= explode(',', $item->exth);
				$correctImageRes 	= PhocaGalleryImage::correctSizeWithRate($resW[2], $resH[2], 50, 50);
				$imgLink			= $item->extl;

				//$o .= '<a class="'. $button->modalname.'" title="'.$button->text.'" href="'. $imgLink .'" rel="'. $button->options.'" >'
				$o .= '<a class="'. $button->methodname.'"  href="'. $imgLink .'" >'
				. '<img src="'.$item->exts.'?imagesid='.md5(uniqid(time())).'" width="'.$correctImageRes['width'].'" height="'.$correctImageRes['height'].'" alt="'.Text::_($txtE).'" />'
				.'</a>'. "\n";
			} else if (isset ($item->fileoriginalexist) && $item->fileoriginalexist == 1) {

				$imageRes			= PhocaGalleryImage::getRealImageSize($item->filename, 'small');
				$correctImageRes 	= PhocaGalleryImage::correctSizeWithRate($imageRes['w'], $imageRes['h'], 50, 50);
				$imgLink			= PhocaGalleryFileThumbnail::getThumbnailName($item->filename, 'large');

				//$o .= '<a class="'. $button->modalname.'" title="'. $button->text.'" href="'. JUri::root(). $imgLink->rel.'" rel="'. $button->options.'" >'
				$o .= '<a class="'. $button->methodname.'"  href="'. Uri::root(). $imgLink->rel.'"  >'
				. '<img src="'.Uri::root().$item->linkthumbnailpath.'?imagesid='.md5(uniqid(time())).'" width="'.$correctImageRes['width'].'" height="'.$correctImageRes['height'].'" alt="'.Text::_($txtE).'" itemprop="thumbnail" />'
				.'</a>'. "\n";
			} else {
				$o .= HTMLHelper::_( 'image', 'media/com_phocagallery/images/administrator/phoca_thumb_s_no_image.gif', '');
			}
		}
		$o .= '     </center>'. "\n"
			.'    </div>'. "\n"
			.'   </div>'. "\n"
			.'  </div>'. "\n"
			.' </center>'. "\n"
			.'</div></div>'. "\n";
		$o .=  '</td>'. "\n";
		return $o;
	}*/
}
?>
