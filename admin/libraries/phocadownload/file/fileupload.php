<?php
/*
 * @package		Joomla.Framework
 * @copyright	Copyright (C) 2005 - 2010 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 *
 * @component Phoca Component
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License version 2 or later;
 */
defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Client\ClientHelper;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
jimport( 'joomla.filesystem.folder' );
jimport( 'joomla.filesystem.file' );

class PhocaDownloadFileUpload
{
	public static function realMultipleUpload( $frontEnd = 0) {

		$app 	= Factory::getApplication();
		$paramsC 		= ComponentHelper::getParams('com_phocadownload');
		$chunkMethod 	= $paramsC->get( 'multiple_upload_chunk', 0 );
		$uploadMethod 	= $paramsC->get( 'multiple_upload_method', 4 );

		$overwriteExistingFiles 	= $paramsC->get( 'overwrite_existing_files', 0 );

		$app->allowCache(false);

		// Chunk Files
		header('Content-type: text/plain; charset=UTF-8');
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		// Invalid Token
		Session::checkToken( 'request' ) or jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'error', 'code' => 100,
				'message' => Text::_('COM_PHOCADOWNLOAD_ERROR').': ',
				'details' => Text::_('COM_PHOCADOWNLOAD_INVALID_TOKEN'))));

		// Set FTP credentials, if given
		$ftp = ClientHelper::setCredentialsFromRequest('ftp');



		$file			= Factory::getApplication()->input->files->get( 'file', null, 'raw');
		//$file 			= JFactory::getApplication()->input->files->get( 'file', null );
		$chunk 			= Factory::getApplication()->input->get( 'chunk', 0, '', 'int' );
		$chunks 		= Factory::getApplication()->input->get( 'chunks', 0, '', 'int' );
		$folder			= Factory::getApplication()->input->get( 'folder', '', '', 'path' );
		$manager		= Factory::getApplication()->input->get( 'manager', 'file', '', 'string' );


		$path	= PhocaDownloadPath::getPathSet($manager);// we use viewback to get right path

		// Make the filename safe
		if (isset($file['name'])) {
			$file['name']	= File::makeSafe($file['name']);
		}
		if (isset($folder) && $folder != '') {
			$folder	= $folder . '/';
		}

		$chunkEnabled = 0;
		// Chunk only if is enabled and only if flash is enabled
		if (($chunkMethod == 1 && $uploadMethod == 1) || ($frontEnd == 0 && $chunkMethod == 0 && $uploadMethod == 1)) {
			$chunkEnabled = 1;
		}


		if (isset($file['name'])) {


			// - - - - - - - - - -
			// Chunk Method
			// - - - - - - - - - -
			// $chunkMethod = 1, for frontend and backend
			// $chunkMethod = 0, only for backend
			if ($chunkEnabled == 1) {

				// If chunk files are used, we need to upload parts to temp directory
				// and then we can run e.g. the condition to recognize if the file already exists
				// We must upload the parts to temp, in other case we get everytime the info
				// that the file exists (because the part has the same name as the file)
				// so after first part is uploaded, in fact the file already exists
				// Example: NOT USING CHUNK
				// If we upload abc.jpg file to server and there is the same file
				// we compare it and can recognize, there is one, don't upload it again.
				// Example: USING CHUNK
				// If we upload abc.jpg file to server and there is the same file
				// the part of current file will overwrite the same file
				// and then (after all parts will be uploaded) we can make the condition to compare the file
				// and we recognize there is one - ok don't upload it BUT the file will be damaged by
				// parts uploaded by the new file - so this is why we are using temp file in Chunk method
				$stream 				= Factory::getStream();// Chunk Files
				$tempFolder				= 'pdpluploadtmpfolder'.'/';
				//$filepathImgFinal 		= JPath::clean($path['orig_abs_ds'].$folder.strtolower($file['name']));
				//$filepathImgTemp 		= JPath::clean($path['orig_abs_ds'].$folder.$tempFolder.strtolower($file['name']));
				$filepathImgFinal 		= Path::clean($path['orig_abs_ds'].$folder.$file['name']);
				$filepathImgTemp 		= Path::clean($path['orig_abs_ds'].$folder.$tempFolder.$file['name']);
				$filepathFolderFinal 	= Path::clean($path['orig_abs_ds'].$folder);
				$filepathFolderTemp 	= Path::clean($path['orig_abs_ds'].$folder.$tempFolder);
				$maxFileAge 			= 60 * 60; // Temp file age in seconds
				$lastChunk				= $chunk + 1;
				$realSize				= 0;




				// Get the real size - if chunk is uploaded, it is only a part size, so we must compute all size
				// If there is last chunk we can computhe the whole size
				if ($lastChunk == $chunks) {
					if (PhocaDownloadFile::exists($filepathImgTemp) && PhocaDownloadFile::exists($file['tmp_name'])) {
						$realSize = filesize($filepathImgTemp) + filesize($file['tmp_name']);
					}
				}

				// 5 minutes execution time
				@set_time_limit(5 * 60);// usleep(5000);

				// If the file already exists on the server:
				// - don't copy the temp file to final
				// - remove all parts in temp file
				// Because some parts are uploaded before we can run the condition
				// to recognize if the file already exists.

				// Files should be overwritten
				if ($overwriteExistingFiles == 1) {
					File::delete($filepathImgFinal);
				}

				if (PhocaDownloadFile::exists($filepathImgFinal)) {
					if($lastChunk == $chunks){
						@Folder::delete($filepathFolderTemp);
					}


						jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'error', 'code' => 108,
							'message' => Text::_('COM_PHOCADOWNLOAD_ERROR').': ',
							'details' => Text::_('COM_PHOCADOWNLOAD_FILE_ALREADY_EXISTS'))));

				}

				if (!PhocaDownloadFileUpload::canUpload( $file, $errUploadMsg, $manager, $frontEnd, $chunkEnabled, $realSize )) {

					// If there is some error, remove the temp folder with temp files
					if($lastChunk == $chunks){
						@Folder::delete($filepathFolderTemp);
					}
					jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'error', 'code' => 104,
								'message' => Text::_('COM_PHOCADOWNLOAD_ERROR').': ',
								'details' => Text::_($errUploadMsg))));
				}

				// Ok create temp folder and add chunks
				if (!PhocaDownloadFile::folderExists($filepathFolderTemp)) {
					@Folder::create($filepathFolderTemp);
				}

				// Remove old temp files
				if (PhocaDownloadFile::folderExists($filepathFolderTemp)) {
					$dirFiles = Folder::files($filepathFolderTemp);
					if (!empty($dirFiles)) {
						foreach ($dirFiles as $fileS) {
							$filePathImgS = $filepathFolderTemp . $fileS;
							// Remove temp files if they are older than the max age
							if (preg_match('/\\.tmp$/', $fileS) && (filemtime($filepathImgTemp) < time() - $maxFileAge)) {
								@File::delete($filePathImgS);
							}
						}
					}
				} else {
					jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'error', 'code' => 100,
							'message' => Text::_('COM_PHOCADOWNLOAD_ERROR').': ',
							'details' => Text::_('COM_PHOCADOWNLOAD_ERROR_FOLDER_UPLOAD_NOT_EXISTS'))));
				}

				// Look for the content type header
				if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
					$contentType = $_SERVER["HTTP_CONTENT_TYPE"];

				if (isset($_SERVER["CONTENT_TYPE"]))
					$contentType = $_SERVER["CONTENT_TYPE"];

				if (strpos($contentType, "multipart") !== false) {
					if (isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {

						// Open temp file
						$out = $stream->open($filepathImgTemp, $chunk == 0 ? "wb" : "ab");
						//$out = fopen($filepathImgTemp, $chunk == 0 ? "wb" : "ab");
						if ($out) {
							// Read binary input stream and append it to temp file
							$in = fopen($file['tmp_name'], "rb");
							if ($in) {
								while ($buff = fread($in, 4096)) {
									$stream->write($buff);
									//fwrite($out, $buff);
								}
							} else {
								jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'error', 'code' => 101,
								'message' => Text::_('COM_PHOCADOWNLOAD_ERROR').': ',
								'details' => Text::_('COM_PHOCADOWNLOAD_ERROR_OPEN_INPUT_STREAM'))));
							}
							$stream->close();
							//fclose($out);
							@File::delete($file['tmp_name']);
						} else {
							jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'error', 'code' => 102,
							'message' => Text::_('COM_PHOCADOWNLOAD_ERROR').': ',
							'details' => Text::_('COM_PHOCADOWNLOAD_ERROR_OPEN_OUTPUT_STREAM'))));
						}
					} else {
						jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'error', 'code' => 103,
							'message' => Text::_('COM_PHOCADOWNLOAD_ERROR').': ',
							'details' => Text::_('COM_PHOCADOWNLOAD_ERROR_MOVE_UPLOADED_FILE'))));
					}
				} else {
					// Open temp file
					$out = $stream->open($filepathImgTemp, $chunk == 0 ? "wb" : "ab");
					//$out = JFile::read($filepathImg);
					if ($out) {
						// Read binary input stream and append it to temp file
						$in = fopen("php://input", "rb");

						if ($in) {
							while ($buff = fread($in, 4096)) {
								$stream->write($buff);
							}
						} else {
							jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'error', 'code' => 101,
								'message' => Text::_('COM_PHOCADOWNLOAD_ERROR').': ',
								'details' => Text::_('COM_PHOCADOWNLOAD_ERROR_OPEN_INPUT_STREAM'))));
						}
						$stream->close();
						//fclose($out);
					} else {
						jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'error', 'code' => 102,
						'message' => Text::_('COM_PHOCADOWNLOAD_ERROR').': ',
						'details' => Text::_('COM_PHOCADOWNLOAD_ERROR_OPEN_OUTPUT_STREAM'))));
					}
				}


				// Rename the Temp File to Final File
				if($lastChunk == $chunks){

					/*if(($imginfo = getimagesize($filepathImgTemp)) === FALSE) {
						Folder::delete($filepathFolderTemp);
						jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'error', 'code' => 110,
						'message' => Text::_('COM_PHOCADOWNLOAD_ERROR').': ',
						'details' => Text::_('COM_PHOCADOWNLOAD_WARNING_INVALIDIMG'))));
					}*/

					// Files should be overwritten
					if ($overwriteExistingFiles == 1) {
						File::delete($filepathImgFinal);
					}

					if(!File::move($filepathImgTemp, $filepathImgFinal)) {

						Folder::delete($filepathFolderTemp);

						jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'error', 'code' => 109,
						'message' => Text::_('COM_PHOCADOWNLOAD_ERROR').': ',
						'details' => Text::_('COM_PHOCADOWNLOAD_ERROR_UNABLE_TO_MOVE_FILE') .'<br />'
						. Text::_('COM_PHOCADOWNLOAD_CHECK_PERMISSIONS_OWNERSHIP'))));
					}


					Folder::delete($filepathFolderTemp);
				}

				if ((int)$frontEnd > 0) {
					return $file['name'];
				}

				jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'OK', 'code' => 200,
				'message' => Text::_('COM_PHOCADOWNLOAD_SUCCESS').': ',
				'details' => Text::_('COM_PHOCADOWNLOAD_FILES_UPLOADED'))));


			} else {
				// No Chunk Method
				$filepathImgFinal 		= Path::clean($path['orig_abs_ds'].$folder.strtolower($file['name']));
				$filepathImgFinal 		= Path::clean($path['orig_abs_ds'].$folder.$file['name']);
				$filepathFolderFinal 	= Path::clean($path['orig_abs_ds'].$folder);



				if (!PhocaDownloadFileUpload::canUpload( $file, $errUploadMsg, $manager, $frontEnd, $chunkMethod, 0 )) {
					jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'error', 'code' => 104,
					'message' => Text::_('COM_PHOCADOWNLOAD_ERROR').': ',
					'details' => Text::_($errUploadMsg))));
				}

				if (PhocaDownloadFile::exists($filepathImgFinal) && $overwriteExistingFiles == 0) {
					jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'error', 'code' => 108,
					'message' => Text::_('COM_PHOCADOWNLOAD_ERROR').': ',
					'details' => Text::_('COM_PHOCADOWNLOAD_FILE_ALREADY_EXISTS'))));
				}


				if(!File::upload($file['tmp_name'], $filepathImgFinal, false, true)) {

					jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'error', 'code' => 109,
					'message' => Text::_('COM_PHOCADOWNLOAD_ERROR').': ',
					'details' => Text::_('COM_PHOCADOWNLOAD_ERROR_UNABLE_TO_UPLOAD_FILE') .'<br />'
					. Text::_('COM_PHOCADOWNLOAD_CHECK_PERMISSIONS_OWNERSHIP'))));
				}

				if ((int)$frontEnd > 0) {
					return $file['name'];
				}

				jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'OK', 'code' => 200,
				'message' => Text::_('COM_PHOCADOWNLOAD_SUCCESS').': ',
				'details' => Text::_('COM_PHOCADOWNLOAD_IMAGES_UPLOADED'))));


			}
		} else {
			// No isset $file['name']

			jexit(json_encode(array( 'jsonrpc' => '2.0', 'result' => 'error', 'code' => 104,
			'message' => Text::_('COM_PHOCADOWNLOAD_ERROR').': ',
			'details' => Text::_('COM_PHOCADOWNLOAD_ERROR_UNABLE_TO_UPLOAD_FILE'))));
		}

	}


	public static function realSingleUpload( $frontEnd = 0 ) {

		$paramsC 		= ComponentHelper::getParams('com_phocadownload');
	//	$chunkMethod 	= $paramsC->get( 'multiple_upload_chunk', 0 );
	//	$uploadMethod 	= $paramsC->get( 'multiple_upload_method', 4 );

		$overwriteExistingFiles 	= $paramsC->get( 'overwrite_existing_files', 0 );

		$app			= Factory::getApplication();
		Session::checkToken( 'request' ) or jexit( 'ERROR: '. Text::_('COM_PHOCADOWNLOAD_INVALID_TOKEN'));
		$app->allowCache(false);


		$file 			= Factory::getApplication()->input->files->get( 'Filedata', null, 'raw');
		$folder			= Factory::getApplication()->input->get( 'folder', '', '', 'path' );
		$format			= Factory::getApplication()->input->get( 'format', 'html', '', 'cmd');
		$return			= Factory::getApplication()->input->get( 'return-url', null, 'post', 'base64' );//includes field
		$viewBack		= Factory::getApplication()->input->get( 'viewback', '', '', '' );
		$manager		= Factory::getApplication()->input->get( 'manager', 'file', '', 'string' );
		$tab			= Factory::getApplication()->input->get( 'tab', '', '', 'string' );
		$field			= Factory::getApplication()->input->get( 'field' );
		$errUploadMsg	= '';
		$folderUrl 		= $folder;
		$tabUrl			= '';
		$component		= Factory::getApplication()->input->get( 'option', '', '', 'string' );

		$path	= PhocaDownloadPath::getPathSet($manager);// we use viewback to get right path


		// In case no return value will be sent (should not happen)
		if ($component != '' && $frontEnd == 0) {
			$componentUrl 	= 'index.php?option='.$component;
		} else {
			$componentUrl	= 'index.php';
		}
		if ($tab != '') {
			$tabUrl = '&tab='.(string)$tab;
		}

		$ftp = ClientHelper::setCredentialsFromRequest('ftp');

		// Make the filename safe
		if (isset($file['name'])) {
			$file['name']	= File::makeSafe($file['name']);
		}


		if (isset($folder) && $folder != '') {
			$folder	= $folder . '/';
		}


		// All HTTP header will be overwritten with js message
		if (isset($file['name'])) {
			$filepath = Path::clean($path['orig_abs_ds'].$folder.strtolower($file['name']));
			$filepath = Path::clean($path['orig_abs_ds'].$folder.$file['name']);

			if (!PhocaDownloadFileUpload::canUpload( $file, $errUploadMsg, $manager, $frontEnd )) {

				if ($errUploadMsg == 'COM_PHOCADOWNLOAD_WARNING_FILE_TOOLARGE') {
					$errUploadMsg 	= Text::_($errUploadMsg) . ' ('.PhocaDownloadFileUpload::getFileSizeReadable($file['size']).')';
				} /* else if ($errUploadMsg == 'COM_PHOCADOWNLOAD_WARNING_FILE_TOOLARGE_RESOLUTION') {
					$imgSize		= phocadownloadImage::getImageSize($file['tmp_name']);
					$errUploadMsg 	= Text::_($errUploadMsg) . ' ('.(int)$imgSize[0].' x '.(int)$imgSize[1].' px)';
				} */ else {
					$errUploadMsg 	= Text::_($errUploadMsg);
				}


				if ($return) {
					$app->enqueueMessage( $errUploadMsg, 'error');
					$app->redirect(base64_decode($return).'&manager='.(string)$manager.'&folder='.$folderUrl);
					exit;
				} else {
					$app->enqueueMessage( $errUploadMsg, 'error');
					$app->redirect($componentUrl);
					exit;
				}
			}

			if (PhocaDownloadFile::exists($filepath) && $overwriteExistingFiles == 0) {
				if ($return) {
					$app->enqueueMessage(Text::_("COM_PHOCADOWNLOAD_FILE_ALREADY_EXISTS"), 'error');
					$app->redirect(base64_decode($return).'&manager='.(string)$manager.'&folder='.$folderUrl);
					exit;
				} else {
					$app->enqueueMessage( Text::_('COM_PHOCADOWNLOAD_FILE_ALREADY_EXISTS'), 'error');
					$app->redirect($componentUrl);
					exit;
				}
			}

			if (!File::upload($file['tmp_name'], $filepath, false, true)) {
				if ($return) {

					$app->enqueueMessage( Text::_('COM_PHOCADOWNLOAD_ERROR_UNABLE_TO_UPLOAD_FILE'), 'error');
					$app->redirect(base64_decode($return).'&manager='.(string)$manager.'&folder='.$folderUrl);
					exit;
				} else {

					$app->enqueueMessage( Text::_('COM_PHOCADOWNLOAD_ERROR_UNABLE_TO_UPLOAD_FILE'), 'error');
					$app->redirect($componentUrl);
					exit;
				}
			} else {

				if ((int)$frontEnd > 0) {
					return $file['name'];
				}

				if ($return) {
					$app->enqueueMessage( Text::_('COM_PHOCADOWNLOAD_SUCCESS_FILE_UPLOAD'));
					$app->redirect(base64_decode($return).'&manager='.(string)$manager.'&folder='.$folderUrl);
					exit;
				} else {
					$app->enqueueMessage( Text::_('COM_PHOCADOWNLOAD_SUCCESS_FILE_UPLOAD'));
					$app->redirect($componentUrl);
					exit;
				}
			}
		} else {
			$msg = Text::_('COM_PHOCADOWNLOAD_ERROR_UNABLE_TO_UPLOAD_FILE');

			if ($return) {
				$app->enqueueMessage( $msg, 'error');
				$app->redirect(base64_decode($return).'&manager='.(string)$manager.'&folder='.$folderUrl);
				exit;
			} else {
				if($viewBack != '') {
					$group 	= PhocaDownloadSettings::getManagerGroup($manager);
					$link	= 'index.php?option=com_phocadownload&view=phocadownloadmanager&manager='.(string)$manager
							.str_replace('&amp;', '&', $group['c']).'&'.$tabUrl.'&folder='.$folder.'&field='.$field;
					$app->enqueueMessage( $msg, 'error');
					$app->redirect($link);
				} else {
					$app->enqueueMessage( $msg, 'error');
					$app->redirect('index.php?option=com_phocadownload');
				}

			}
		}

	}



	public static function canUpload( $file, &$err, $manager = '', $frontEnd = 0, $chunkEnabled = 0, $realSize = 0) {

		$paramsC 	= ComponentHelper::getParams( 'com_phocadownload' );

		$enable_xss_check = $paramsC->get( 'enable_xss_check', 1);

		if ($frontEnd == 1) {
			$aft = $paramsC->get( 'allowed_file_types_upload', PhocaDownloadSettings::getDefaultAllowedMimeTypesUpload() );
			$dft = $paramsC->get( 'disallowed_file_types_upload', '' );
			$allowedMimeType 	= PhocaDownloadFile::getMimeTypeString($aft);
			$disallowedMimeType = PhocaDownloadFile::getMimeTypeString($dft);

			$ignoreUploadCh = 0;
			// 1 ... upload all
			// 2 ... upload admin only
			// 4 ... upload and download all
			// 5 ... upload and download admin only
			$ignoreUploadCheck = $paramsC->get( 'ignore_file_types_check', 2 );
			if ($ignoreUploadCheck == 1 || $ignoreUploadCheck == 4 ) {
				$ignoreUploadCh = 1;
			}

		} else {

			$aft = $paramsC->get( 'allowed_file_types_download', PhocaDownloadSettings::getDefaultAllowedMimeTypesDownload() );
			$dft = $paramsC->get( 'disallowed_file_types_download', '' );
			$allowedMimeType 	= PhocaDownloadFile::getMimeTypeString($aft);
			$disallowedMimeType = PhocaDownloadFile::getMimeTypeString($dft);

			$ignoreUploadCh = 0;
			$ignoreUploadCheck = $paramsC->get( 'ignore_file_types_check', 2 );
			if ($ignoreUploadCheck == 1 || $ignoreUploadCheck == 4 || $ignoreUploadCheck == 2 || $ignoreUploadCheck == 5 ) {
				$ignoreUploadCh = 1;
			}
		}



		$paramsL = array();
		$group = PhocaDownloadSettings::getManagerGroup($manager);
		if ($group['f'] == 2) {
			$paramsL['upload_extensions'] 	= 'gif,jpg,png,jpeg';
			$paramsL['image_extensions'] 	= 'gif,jpg,png,jpeg';
			$paramsL['upload_mime']			= 'image/jpeg,image/gif,image/png';
			$paramsL['upload_mime_illegal']	='application/x-shockwave-flash,application/msword,application/excel,application/pdf,application/powerpoint,text/plain,application/x-zip,text/html';
			$paramsL['upload_ext_illegal']	= $disallowedMimeType['ext'];
		} else {
			$paramsL['upload_extensions'] 	= $allowedMimeType['ext'];
			$paramsL['image_extensions'] 	= 'bmp,gif,jpg,png,jpeg';
			$paramsL['upload_mime']			= $allowedMimeType['mime'];
			$paramsL['upload_mime_illegal']	= $disallowedMimeType['mime'];
			$paramsL['upload_ext_illegal']	= $disallowedMimeType['ext'];
		}


		// The file doesn't exist
		if(empty($file['name'])) {
			$err = 'COM_PHOCADOWNLOAD_WARNING_INPUT_FILE_UPLOAD';
			return false;
		}
		// Not safe file
		jimport('joomla.filesystem.file');
		if ($file['name'] !== File::makesafe($file['name'])) {
			$err = 'COM_PHOCADOWNLOAD_WARNFILENAME';
			return false;
		}

		$format 		= strtolower(File::getExt($file['name']));
		if ($ignoreUploadCh == 1) {

		} else {

			$allowable 		= explode( ',', $paramsL['upload_extensions']);
			$notAllowable 	= explode( ',', $paramsL['upload_ext_illegal']);
			if(in_array($format, $notAllowable)) {
				$err = 'COM_PHOCADOWNLOAD_WARNFILETYPE_DISALLOWED';
				return false;
			}


			//if (!in_array($format, $allowable)) {
			if ($format == '' || $format == false || (!in_array($format, $allowable))) {
				$err = 'COM_PHOCADOWNLOAD_WARNFILETYPE_NOT_ALLOWED';
				return false;
			}
		}


		// Max size of image
		// If chunk method is used, we need to get computed size
		$maxSize = $paramsC->get( 'upload_maxsize', 3145728 );
		if ((int)$frontEnd > 0) {
			$maxSize = $paramsC->get( 'user_file_upload_size', 3145728 );
		} else {
			$maxSize = $paramsC->get( 'upload_maxsize', 3145728 );
		}

		if ($chunkEnabled == 1) {
			if ((int)$maxSize > 0 && (int)$realSize > (int)$maxSize) {
				$err = 'COM_PHOCADOWNLOAD_WARNFILETOOLARGE';

				return false;
			}
		} else {
			if ((int)$maxSize > 0 && (int)$file['size'] > (int)$maxSize) {
				$err = 'COM_PHOCADOWNLOAD_WARNFILETOOLARGE';

				return false;
			}
		}


		// User (only in ucp) - Check the size of all files by users
		if ($frontEnd == 2) {
			$user 				= Factory::getUser();
			$maxUserUploadSize 	= (int)$paramsC->get( 'user_files_max_size', 20971520 );
			$maxUserUploadCount	= (int)$paramsC->get( 'user_files_max_count', 5 );
			$allFile	= PhocaDownloadUser:: getUserFileInfo($file, $user->id);

			if ($chunkEnabled == 1) {
				$fileSize = $realSize;
			} else {
				$fileSize = $file['size'];
			}

			if ((int)$maxUserUploadSize > 0 && (int) $allFile['size'] > $maxUserUploadSize) {
				$err = Text::_('COM_PHOCADOWNLOAD_WARNUSERFILESTOOLARGE');
				return false;
			}

			if ((int) $allFile['count'] > $maxUserUploadCount) {
				$err = Text::_('COM_PHOCADOWNLOAD_WARNUSERFILESTOOMUCH');
				return false;
			}
		}




		// Image check
		$imginfo	= null;
		$images		= explode( ',', $paramsL['image_extensions']);

		if(in_array($format, $images)) { // if its an image run it through getimagesize

			$group = PhocaDownloadSettings::getManagerGroup($manager);
			if($group['i'] == 1) {
				if ($chunkEnabled != 1) {
					if(($imginfo = getimagesize($file['tmp_name'])) === FALSE) {
						$err = 'COM_PHOCADOWNLOAD_WARNINVALIDIMG';
						$err = $imginfo[0];
						return false;
					}
				}
			}
		} else if(!in_array($format, $images)) { // if its not an image...and we're not ignoring it
			$allowed_mime = explode(',', $paramsL['upload_mime']);
			$illegal_mime = explode(',', $paramsL['upload_mime_illegal']);
			if(function_exists('finfo_open')) {// We have fileinfo
				$finfo	= finfo_open(FILEINFO_MIME);
				$type	= finfo_file($finfo, $file['tmp_name'], FILEINFO_MIME_TYPE);
				if(strlen($type) && !in_array($type, $allowed_mime) && in_array($type, $illegal_mime)) {
					$err = 'COM_PHOCADOWNLOAD_WARNINVALIDMIME';
					return false;
				}
				finfo_close($finfo);
			} else if(function_exists('mime_content_type')) { // we have mime magic
				$type = mime_content_type($file['tmp_name']);
				if(strlen($type) && !in_array($type, $allowed_mime) && in_array($type, $illegal_mime)) {
					$err = 'COM_PHOCADOWNLOAD_WARNINVALIDMIME';
					return false;
				}
			}
		}

		// XSS Check
		if ((int)$enable_xss_check == 3 || ((int)$enable_xss_check == 1 && $frontEnd > 0) || ((int)$enable_xss_check == 2 && $frontEnd == 0)) {
			$xss_check = file_get_contents($file['tmp_name'], false, null, -1, 256);
			$html_tags = PhocaDownloadSettings::getHTMLTagsUpload();
			foreach($html_tags as $tag) { // A tag is '<tagname ', so we need to add < and a space or '<tagname>'
				if(stristr($xss_check, '<'.$tag.' ') || stristr($xss_check, '<'.$tag.'>')) {
					$err = 'COM_PHOCADOWNLOAD_WARNIEXSS';
					return false;
				}
			}
		}

		return true;
	}


	public static function renderFTPaccess() {

		$ftpOutput = '<fieldset title="'.Text::_('COM_PHOCADOWNLOAD_FTP_LOGIN_LABEL'). '">'
		.'<legend>'. Text::_('COM_PHOCADOWNLOAD_FTP_LOGIN_LABEL').'</legend>'
		.Text::_('COM_PHOCADOWNLOAD_FTP_LOGIN_DESC')
		.'<table class="adminform nospace">'
		.'<tr>'
		.'<td width="120"><label for="username">'. Text::_('JGLOBAL_USERNAME').':</label></td>'
		.'<td><input type="text" id="username" name="username" class="input_box" size="70" value="" /></td>'
		.'</tr>'
		.'<tr>'
		.'<td width="120"><label for="password">'. Text::_('JGLOBAL_PASSWORD').':</label></td>'
		.'<td><input type="password" id="password" name="password" class="input_box" size="70" value="" /></td>'
		.'</tr></table></fieldset>';
		return $ftpOutput;
	}

	public static function renderCreateFolder($sessName, $sessId, $currentFolder, $viewBack, $attribs = '') {

		if ($attribs != '') {
			$attribs = '&amp;'.$attribs;
		}

		$link = Uri::base()
			.'index.php?option=com_phocadownload&task=phocadownloadupload.createfolder&amp;'. $sessName.'='.$sessId.'&amp;'
			.Session::getFormToken().'=1&amp;viewback='.$viewBack.'&amp;'
			.'folder='.PhocaDownloadUtils::filterValue($currentFolder, 'folderpath').$attribs;

		$link = str_replace('&amp;', '&', $link);

		$folderOutput = '<form action="'. Route::_($link) .'" name="folderForm" id="folderForm" method="post" class="form-inline" >'."\n"

		.'<h4>'.Text::_('COM_PHOCADOWNLOAD_FOLDER').'</h4>'."\n"
		.'<div class="path">'
		.'<input class="form-control" type="text" id="foldername" name="foldername"  />'
		.'<input class="update-folder" type="hidden" name="folderbase" id="folderbase" value="'.PhocaDownloadUtils::filterValue($currentFolder, 'folderpath').'" />'
		.' <button type="submit" class="btn btn-primary">'. Text::_( 'COM_PHOCADOWNLOAD_CREATE_FOLDER' ).'</button>'
		.'</div>'."\n"
		.HTMLHelper::_( 'form.token' )
		.'</form>';
		return $folderOutput;
	}
}
?>
