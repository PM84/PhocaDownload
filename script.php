<?php
/*
 * @package		Joomla.Framework
 * @copyright	Copyright (C) Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 * @component Phoca Component
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License version 2 or later;
 */
defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Path;

jimport( 'joomla.filesystem.folder' );

class com_phocadownloadInstallerScript
{

	protected $extension 	= 'com_phocadownload';
	protected $updatetext 	= 'COM_PHOCADOWNLOAD_UPDATE_TEXT';
	protected $installtext 	= 'COM_PHOCADOWNLOAD_INSTALL_TEXT';
	protected $versiontext	= 'COM_PHOCADOWNLOAD_VERSION';
	protected $extensiontext= 'COM_PHOCADOWNLOAD';
	protected $configuretext= 'COM_PHOCADOWNLOAD_CONFIGURE';

	function createFolders() {

		$folder[0][0]	=	'phocadownload'  ;
		$folder[0][1]	= 	JPATH_ROOT . '/' .  $folder[0][0];

		$folder[1][0]	=	'images/phocadownload'  ;
		$folder[1][1]	= 	JPATH_ROOT . '/' .  $folder[1][0];

		$folder[2][0]	=	'phocadownload/userupload';
		$folder[2][1]	= 	JPATH_ROOT . '/' .  $folder[2][0];

		$folder[3][0]	=	'phocadownloadpap';
		$folder[3][1]	= 	JPATH_ROOT . '/' .  $folder[3][0];

		$folder[4][0]	=	'phocadownloadpap/userupload';
		$folder[4][1]	= 	JPATH_ROOT . '/' .  $folder[4][0];

		$msg = '';
		foreach ($folder as $k => $v) {
			if (!is_dir(Path::clean( $v[1]))) {
				if (Folder::create( $v[1], 0755 )) {
					$data = "<html>\n<body bgcolor=\"#FFFFFF\">\n</body>\n</html>";
					File::write($v[1].'/'."index.html", $data);
					$msg .= '<div><b><span style="color:#009933">Folder</span> ' . $v[0]
						 .' <span style="color:#009933">created!</span></b></div>';
				} else {
					$msg .= '<div><b><span style="color:#CC0033">Folder</span> ' . $v[0]
						 .' <span style="color:#CC0033">creation failed!</span></b> Please create it manually.</div>';
				}
			} else {
				// Folder exists
				$msg .= '<div><b><span style="color:#009933">Folder</span> ' . $v[0]
					 .' <span style="color:#009933">exists!</span></b></div>';
			}
		}
		return $msg;
	}

	function install($parent) {
		$this->loadLanguage($parent);
		$msg = $this->createFolders();
		Factory::getApplication()->enqueueMessage($msg, 'message');
		return true;
	}
	function uninstall($parent) {
		//$this->loadLanguage($parent);
		return true;
	}

	function update($parent) {
		//$this->loadLanguage($parent);
		$msg = $this->createFolders();
		Factory::getApplication()->enqueueMessage($msg, 'message');
		return true;
	}

	public function loadLanguage($parent) {
		$extension = $this->extension;
		$lang = Factory::getLanguage();
		$path = $parent->getParent()->getPath('source');
		$lang->load($this->extension, $path, 'en-GB', true);
		$lang->load($this->extension, $path, $lang->getDefault(), true);
		$lang->load($this->extension, $path, null, true);
		$lang->load($this->extension . '.sys', $path, 'en-GB', true);
		$lang->load($this->extension . '.sys', $path, $lang->getDefault(), true);
		$lang->load($this->extension . '.sys', $path, null, true);
		return true;
	}

	function preflight($type, $parent) {
		$this->loadLanguage($parent);
		return true;
	}

	function postflight($type, $parent)  {
		$this->loadLanguage($parent);

		if ($type == 'update' || $type == 'install') {


			if ($type == 'update') {
				$status =  Text::_($this->updatetext);
			} else {
				$status =  Text::_($this->installtext);
			}
			$version 	= Text::_($this->versiontext). ': ' . $parent->getManifest()->version;
			$link 		= 'index.php?option='.$this->extension;
			$component	= Text::_($this->extensiontext);
			$configure	= Text::_($this->configuretext);

			$o = '';
			$o .= $this->getStyle();
			$o .= '<div class="g5i">';
			$o .= ' <h1>';
			$o .= '  <span class="g5-title">'.$status.'</span>';
			$o .= '  <span class="g5-info">'.$version.'</span>';
			$o .= ' </h1>';
			$o .= ' ';
			$o .= ' <div class="g5-actions">';
			$o .= '  <a href="'.$link.'" class="g5-button">'.$configure.' '.$component.'<span class="g5-icon icon-chevron-right"></span></a>';
			$o .= ' </div>';
			$o .= ' <div class="g5-phoca">';
			$o .=    '<a href="https://www.phoca.cz" target="_blank"><span>Phoca</span></a>';
			$o .= ' </div>';
			///$o .= '</div>';

			$upEL = 'https://extensions.joomla.org/extension/phoca-download/';
            $upE = 'Phoca Download';

            $o .= '<div class="upBox">';

            $o .=  '<div class="upItem upItemD">';
            $o .=  '<div class="upItemText">If you find this project useful, please support it with a donation</div>';
            $o .=  '<form action="https://www.paypal.com/donate" method="post" target="_top">';
            $o .=  '<input type="hidden" name="hosted_button_id" value="ZVPH25SQ2DDBY" />';
            $o .=  '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />';
            $o .=  '<img alt="" border="0" src="https://www.paypal.com/en_CZ/i/scr/pixel.gif" width="1" height="1" />';
            $o .=  '</form>';
            $o .=  '</div>';

            $o .=  '<div class="upItem upItemJ">';
            $o .=  '<div class="upItemText">If you find this project useful, please post a rating and review on the Joomla! Extension Directory website</div>';
            $o .=  '<a class="upItemLink" target="_blank" href="'. $upEL.'">'. $upE.' (JED website)</a>';
            $o .=  '</form>';
            $o .=  '</div>';

            $o .=  '<div class="upItem upItemDoc">';
            $o .=  '<div class="upItemText">If you need help, visit</div>';
            $o .=  '<a class="upItemLink" target="_blank" href="https://www.phoca.cz/documentation">Phoca documentation website</a>';
            $o .=  '<div class="upItemText">or ask directly in</div>';
            $o .=  '<a class="upItemLink" target="_blank" href="https://www.phoca.cz/forum">Phoca forum website</a>';
            $o .=  '</div>';

            $o .=  '<div class="upItem upItemPh">';
            $o .=  '<div class="upItemText">There are over a hundred more useful Phoca extensions, discover them on</div>';
            $o .=  '<a class="upItemLink" target="_blank" href="https://www.phoca.cz">Phoca website</a>';
            $o .=  '</div>';

            $o .=  '</div>';


            $o .= '</div>';//g5i
            echo $o;
		}

		return true;
	}


	function getStyle() {

		return "<style>
.g5i h1 {
	color: white;
	background: rgba(0, 0, 0, 0.3);
	padding: 1rem;
	text-align: center;
	left: 50%;
	position: relative;
	/*margin: 1rem -0.5rem 0;*/
	margin: 1rem 0 0 0;
	transform: translateX(-50%);
	line-height: 1.5;
	border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.g5i p {
	font-size: 1rem;
	color: white;
	margin: 1rem 10%;
	text-shadow: 0 0 9px black;
	text-align: center;
	background: rgba(0, 0, 0, 0.1);
	padding: 0.5rem;
	border-radius: 3px;
	line-height: 1.5;
}

.g5i .g5-info {
	display: block;
	font-size: 1rem;
	font-weight: normal;
}

.g5i .g5-actions {
	text-align: center;
	border-top: 1px solid rgba(255, 255, 255, 0.1);
	padding: 1rem;
}

.g5i .g5-button {
	display: inline-block;
	font-size: 1rem;
	color: white;
	border: 2px solid rgba(255, 255, 255, 0.8);
	border-radius: 3px;
	padding: 0.5rem 1rem;
	background: rgba(82, 195, 255, 0.1);
	vertical-align: middle;
	transition: background .2s;
	text-decoration: none;
}

.g5i .g5-button:hover {
	background-color: rgba(255, 255, 255, 0.2);
	transition: background .2s;
}

.g5i .g5-icon {
	line-height: 11px;
	vertical-align: middle;
}

.g5i .g5-phoca {
        background: url('data:image/png;base64,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');
	width: 130px;
	height: 31px;
	background-repeat: no-repeat;
	position: absolute;
	bottom: 5px;
	right: 5px;
	background-size: contain;
}

.g5i .g5-phoca a {
	display: block;
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	font-size: 0;
}

/* container */
.g5i {
	position: relative;
	margin: 15px 15px 15px -15px;
	background: url('data:image/jpeg;base64,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');
	padding: 1rem;
	background-repeat: no-repeat;
	background-size: cover;
	border-radius: 6px;
}

.upBox {
    display: flex;
    flex-wrap: wrap;
    margin-top:1em;
    margin-bottom: 2em;
}

.upItemText {
    margin-bottom: 1em;
}

.upItem {
    padding: 1em;
    text-align: center;
    width: calc(50% - 0.4em);
    margin: 0.2em;
    border-radius: 0.3em;
}

.upItemD {
    background: #F5D042;
    color: #000;
}
.upItemPh {
    background: rgba(255,255,255,0.7);
    color: #000;
}
.upItemDoc {
    background: rgba(255,255,255,0.7);
    color: #000;
}
.upItemJ {
    background: rgba(255,255,255,0.7);
    color: #000;
}

a.upItemLink {
    padding: 0.5em 1em;
    border-radius: 9999px;
    margin: 1em;
    display: inline-block;
}

a.upItemLink::before {
    content: none;
}
.upItemPh a.upItemLink {
    background: #000;
    color: #fff;
}
.upItemDoc a.upItemLink {
    background: #000;
    color: #fff;
}
.upItemJ a.upItemLink {
    background: #000;
    color: #fff;
}

.phTemplateItems {
    display: flex;
    flex-wrap: wrap;
    margin-top:1em;
    margin-bottom: 2em;
}

.phTemplateItem {
    padding: 1em;
    text-align: center;
    width: calc(33% - 0.4em);
    margin: 0.2em;
    border-radius: 0.3em;
}

.phTemplateItem img{
    width: 100%;
    height: auto;
}

.phTemplateItemsInfo {
    margin: 1em auto;
}
.phTemplateItemTitle {
    font-size: small;
}

.phTemplateItem a::before {
    content: none;
}

.phTemplateItemTitle,
.phTemplateItemsInfo {
    color: #fff;
}

.g5-actions a.g5-button {
	color: #fff;
}

</style>";
	}
}
