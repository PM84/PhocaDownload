<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Path;
use Joomla\Filesystem\File;

echo '<div id="phoca-dl-categories-box" class="pd-categories-view'.$this->t['p']->get( 'pageclass_sfx' ).'">';

//if ( $this->t['p']->get( 'show_page_heading' ) ) {
//	echo '<h1>'. $this->escape($this->t['p']->get('page_heading')) . '</h1>';
//}
echo PhocaDownloadRenderFront::renderHeader(array());

if ( $this->t['description'] != '') {
	echo '<div class="pd-desc">'. $this->t['description']. '</div>';
}


if (!empty($this->t['categories'])) {
	//$i = 1;
    echo ' <div class="row row-cols-1 row-cols-md-3 g-4"> ';
    foreach ($this->t['categories'] as $value) {

		// Categories
		$numDoc 	= 0;
		$numSubcat	= 0;
		$catOutput 	= '';

		// We need $numDoc and $numSubcat always - we need to run this foreach even the subcategories will be not displayed
		if (!empty($value->subcategories)) {
			foreach ($value->subcategories as $valueCat) {

				// USER RIGHT - Access of categories - - - - -
				// ACCESS is handled in SQL query, ACCESS USER ID is handled here (specific users)
				$rightDisplay	= 0;
				if (!empty($valueCat)) {
					$rightDisplay = PhocaDownloadAccess::getUserRight('accessuserid', $valueCat->accessuserid, $valueCat->access, $this->t['user']->getAuthorisedViewLevels(), $this->t['user']->get('id', 0), 0);

				}
				// - - - - - - - - - - - - - - - - - - - - - -

				if ($rightDisplay == 1) {

					$catOutput 	.= '<li class="list-group-item"><span class="pd-subcategory"></span><a href="'. Route::_(PhocaDownloadRoute::getCategoryRoute($valueCat->id, $valueCat->alias))
								.'">'. $valueCat->title.'</a>';

					if ($this->t['displaynumdocsecs'] == 1) {
						$catOutput  .=' <small>('.$valueCat->numdoc .')</small>';
					}

					$catOutput .= '</li>';
					$numDoc = (int)$valueCat->numdoc + (int)$numDoc;
					$numSubcat++;



				}
			}
		}

		if ($this->t['display_main_cat_subcategories'] != 1) {
			$catOutput = '';
		}


 		// Don't display parent category
		// - if there is no catoutput
		// - if there is no rigths for it

		// USER RIGHT - Access of parent category - - - - -
		// ACCESS is handled in SQL query, ACCESS USER ID is handled here (specific users)
		$rightDisplay	= 0;
		if (!empty($value)) {
			$rightDisplay = PhocaDownloadAccess::getUserRight('accessuserid', $value->accessuserid, $value->access, $this->t['user']->getAuthorisedViewLevels(), $this->t['user']->get('id', 0), 0);

		}
		// - - - - - - - - - - - - - - - - - - - - - -

		if ($rightDisplay == 1) {
            echo '<div class="col">';
            echo '<div class="card h-100">';

            if (isset($value->image) && $value->image != '') {
				echo '<img src="'.$this->t['cssimgpath'].$value->image.'" class="card-img-top"  alt="'.htmlspecialchars(strip_tags($value->title)).'" />';
			}

            echo '<div class="card-body">';

            echo '<h3 class="card-title">';
			echo '<a href="'. Route::_(PhocaDownloadRoute::getCategoryRoute($value->id, $value->alias)).'">'. $value->title.'</a>';

			/*if ($this->t['displaynumdocsecsheader'] == 1) {
				$numDocAll = (int)$numDoc + (int)$value->numdoc;
				//$numDoc ... only files in subcategories
				//$value->numdoc ... only files in the main category
				//$numDocAll ... files in category and in subcategories
				echo ' <small>('.$numSubcat.'/' . $numDocAll .')</small>';
			}*/
			echo '</h3>';

            if ($this->t['displaymaincatdesc']	 == 1) {
				echo '<p class="card-text">'.$value->description.'</p>';
			}

            if ($catOutput != '') {
            	echo '<ul class="list-group list-group-flush">'.$catOutput. '</ul>';
			}
            echo '</div>'; // end card body

            if ($this->t['displaynumdocsecsheader'] == 1) {
				echo '<div class="card-footer pd-categories-card">';
					echo '<small class="text-muted float-end">';
						if ($this->t['displaynumdocsecsheader'] == 1) {
							$numDocAll = (int)$numDoc + (int)$value->numdoc;
							//$numDoc ... only files in subcategories
							//$value->numdoc ... only files in the main category
							//$numDocAll ... files in category and in subcategories
								echo '<span class="pd-categories-number">'.Text::_('COM_PHOCADOWNLOAD_CATEGORIES').': '.$numSubcat.'</span>';
								echo '<span class="pd-sep-number">&nbsp;/&nbsp;</span>';
								echo '<span class="pd-files-number">'.Text::_('COM_PHOCADOWNLOAD_FILES').': '.$numDocAll.'</span>';
						}
					echo '</small>';
				echo '</div>';
			}


			echo '</div>'; // end card
			echo '</div>'; // end col

		}
	}
	echo '</div>';// end row
}

echo '</div>';
echo '<div class="pd-cb"></div>';


// - - - - - - - - - -
// Most viewed docs (files)
// - - - - - - - - - -
$outputFile		= '';

if (!empty($this->t['mostvieweddocs']) && $this->t['displaymostdownload'] == 1) {
	$l = new PhocaDownloadLayout();
	foreach ($this->t['mostvieweddocs'] as $value) {
		// USER RIGHT - Access of categories (if file is included in some not accessed category) - - - - -
		// ACCESS is handled in SQL query, ACCESS USER ID is handled here (specific users)
		$rightDisplay	= 0;
		if (!empty($value)) {
			$rightDisplay = PhocaDownloadAccess::getUserRight('accessuserid', $value->cataccessuserid, $value->cataccess, $this->t['user']->getAuthorisedViewLevels(), $this->t['user']->get('id', 0), 0);
		}
		// - - - - - - - - - - - - - - - - - - - - - -

		if ($rightDisplay == 1) {
			// FILESIZE
			if ($value->filename !='') {
				$absFile = str_replace('/', '/', Path::clean($this->t['absfilepath'] . $value->filename));
				if (PhocaDownloadFile::exists($absFile)) {
					$fileSize = PhocaDownloadFile::getFileSizeReadable(filesize($absFile));
				} else {
					$fileSize = '';
				}
			}

			// IMAGE FILENAME
			//$imageFileName = '';
			//if ($value->image_filename !='') {
				$imageFileName = $l->getImageFileName($value->image_filename, $value->filename, 2);
				/*$thumbnail = false;
				$thumbnail = preg_match("/phocathumbnail/i", $value->image_filename);
				if ($thumbnail) {
					$imageFileName 	= '';
				} else {
					$imageFileName = 'style="background: url(\''.$this->t['cssimgpath'].$value->image_filename.'\') 0 center no-repeat;"';
				}*/
			//}

			//$outputFile .= '<div class="pd-document'.$this->t['file_icon_size_md'].'" '.$imageFileName.'>';

			$outputFile .= '<div class="pd-filename">'. $imageFileName['filenamethumb']
					. '<div class="pd-document'.$this->t['file_icon_size_md'].'" '
					. $imageFileName['filenamestyle'].'>';

			$outputFile .= '<a href="'
						. Route::_(PhocaDownloadRoute::getCategoryRoute($value->categoryid,$value->categoryalias))
						.'">'. $value->title.'</a>'
						.' <small>(' .$value->categorytitle.')</small>';

			$outputFile .= PhocaDownloadRenderFront::displayNewIcon($value->date, $this->t['displaynew']);
			$outputFile .= PhocaDownloadRenderFront::displayHotIcon($value->hits, $this->t['displayhot']);

			$outputFile .= '</div></div>' . "\n";
		}
	}

	if ($outputFile != '') {
		echo '<div class="pd-hr ph-cb">&nbsp;</div>';
		echo '<div id="phoca-dl-most-viewed-box">';
		echo '<div class="pd-documents"><h3>'. Text::_('COM_PHOCADOWNLOAD_MOST_DOWNLOADED_FILES').'</h3>';
		echo $outputFile;
		echo '</div></div>';
	}
}
echo '<div class="pd-cb">&nbsp;</div>';
echo PhocaDownloadUtils::getInfo();
?>
