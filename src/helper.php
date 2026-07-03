<?php
/**
 * @package		RNS Upload and Files Display Module for Joomla 5+
* @version		1.3.2
* @author		Giannis Brailas (ioannis@brailas.gr)
* @license		GNU/GPLv3

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Path;
use Joomla\Registry\Registry;
	
class modRnsUploadHelper {
	
	//Έλεγχος για επιπλέον δικαιώματα -> 
	// Όσοι χρήστες είναι στο group access θα έχουν δικαίωμα να ανεβάζουν και να διαγράφουν αρχεία
	public static function getUploadPermission(array $usersCanWrite): bool { 

		//$user    = Factory::getUser(); //Deprecated!
		$user = Factory::getApplication()->getIdentity();
    
		// If no user is logged in, $user is null
		if (!$user || empty($user->id)) {
			return false;
		}

		// Check if current user ID exists within the module's backend settings array
		return in_array($user->id, $usersCanWrite);
	}
	
	
	// Snippet from PHP.NET:  http://php.net/manual/en/function.filemtime.php
	public static function mostRecentModifiedFileTime($dirName,$doRecursive) {
		$d = dir($dirName);
		$lastModified = 0;
		$currentModified = 0;
		while($entry = $d->read()) {
			if ($entry != "." && $entry != "..") {
				if (!is_dir($dirName."/".$entry)) {
					$currentModified = filemtime($dirName."/".$entry);
				} else if ($doRecursive && is_dir($dirName."/".$entry)) {
					$currentModified = mostRecentModifiedFileTime($dirName."/".$entry,true);
				}
				if ($currentModified > $lastModified){
					$lastModified = $currentModified;
				}
			}
		}
		$d->close();
		$lastM1 = date("d-m-Y", $lastModified);
		$lastM2 = DateTime::createFromFormat('d-m-Y', $lastM1);
		return $lastM2->format("d-m-Y");
	}
	
	// Snippet from https://stackoverflow.com/questions/7948300/order-this-array-by-date-modified
	public static function filetime_callback($a, $b) {
	  if (filemtime($a) === filemtime($b)) return 0;
	  return filemtime($a) < filemtime($b) ? -1 : 1; 
	}
		
	
	// Snippet from PHP Share: http://www.phpshare.org
    public static function formatSizeUnits($bytes) {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
	}

	//Upload files to specific folder
	public static function uploadPdfsAjax() {
		
		require_once JPATH_SITE.'/modules/mod_rnsupload/fileupload.helper.php';
		
		$app     = Factory::getApplication();
        $input   = $app->getInput();
		$session = Factory::getSession();
		$user    = $app->getIdentity();
		$db = Factory::getDbo();

		// AT FIRST DO Anti-CSRF Token Validation (it saves server resources) 
        if (!$session->checkToken('post')) {
            http_response_code(403);
            echo new JsonResponse(null, 'Invalid token.', true);
            $app->close();
        }
    
		// Fetch the module parameters using helper function
		$moduleId = $input->get('rns_module_id', 0, 'int');
    	$params   = self::getModuleParamsById($moduleId, 'mod_rnsupload');

		//Check if module config exists
		if ($moduleId <= 0 || count($params->toArray()) === 0) {
			http_response_code(500);
			echo new JsonResponse(null, 'Module configuration not found.', true);
			$app->close();
		}

		// Parse the module parameters string
		$users_can_write_array = $params->get('can_write', array());

		// Run permission check with the securely fetched array
		$user_grant_upload = self::getUploadPermission($users_can_write_array);

		// Security check
		if (!$user_grant_upload) {
			http_response_code(403);
			echo new JsonResponse(null, 'Unauthorized access. You do not have permission.', true);
			$app->close();
		}

		$errors = "";
		$result_file_upload = "";
		$filesToUpload  = $_FILES['userPDF'];
		$fileexist = 0;
		$filescount = 0;
		$newfiles = array();

		foreach ($filesToUpload as $key => $values) {
			if (!empty(array_filter(($values))) && $values[0] != 4 && $values[0] != 0 )
				$fileexist = 1;
		}
		
		//Έλεγχος αν ο χρήστης επέλεξε να ανεβάσει αρχεία
		if(!empty(array_filter($filesToUpload)) && ($fileexist != 0)) {

			//$efu_maxsize = $_POST['MaxFileSize'];
			//$efu_maxsize = $input->getInt('MaxFileSize', 0); //No, bad user can alter maxfilesize to crash the program
			$efu_maxsize = 25165824;
			$efu_parent = "images";
			//$project_folder = $_POST['PdfUploadFolder'];
			$project_folder = $input->getString('PdfUploadFolder', '');
			$efu_replace = false;
			$efu_scriptsinarchives = false;
			$efu_filetypes = "application/msword;application/excel;application/pdf;application/powerpoint;application/x-zip;application/x-zip-compressed;application/zip;application/vnd.openxmlformats-officedocument.wordprocessingml.document;application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;application/vnd.ms-excel.sheet.binary.macroEnabled.12;application/powerpoint;application/vnd.ms-powerpoint;application/vnd.openxmlformats-officedocument.presentationml.presentation;application/octet-stream;application/CDFV2-unknown;image/png";
			$files = array();
			foreach ($filesToUpload as $key => $values)	{
				if (is_array($values)) {
					foreach ($values as $k => $v) {
						if (!isset($files[$k])) {
							$files[$k] = array();
						}
						$files[$k][$key] = $v;
					}
				}
				else {
					$files[0][$key] = $values;
				}
			}
			if (!empty($files)) {
				foreach ($files as $file) {
					$filename = trim(preg_replace('/[&\/\\#,+()$~%:*?<>{}]/', '', $file['name']), '()');
					$filepath = $efu_parent . DIRECTORY_SEPARATOR . $project_folder . DIRECTORY_SEPARATOR . $filename;
					$newfiles[$filescount] = new \stdClass;
					$newfiles[$filescount]->filename = $filename;
					$newfiles[$filescount]->filepath = $filepath;
					
					$file_params = array($efu_parent, $project_folder, $file, $efu_maxsize, $efu_replace, $efu_scriptsinarchives, 1, $efu_filetypes);
					
					//Call fileupload function to create folder and upload file	
					$result_file_upload = rnsuploadFileupload::getFileToUpload($file_params);
					
					if (!empty($result_file_upload) && (($result_file_upload['type'] == 'warning') || ($result_file_upload['type'] == 'error'))) {
						$errors .= $result_file_upload['text'];
						//break;
					}
					
					$filescount++;

					//τώρα γράψε στη βάση
					$new_file = new \stdClass();
					//$new_file->catid = $_POST['menu_catid'];
					$new_file->catid = $input->getInt('menu_catid', 0);
					$new_file->filename = $filename;
					$new_file->filepath = $efu_parent . DIRECTORY_SEPARATOR . $project_folder . DIRECTORY_SEPARATOR . $filename;
					$new_file->created = date('Y-m-d H:i:s');
					$new_file->created_by =  $user->id;
					$new_file->published = 1;
					try {
						$db->insertObject('#__rns_upload_files', $new_file);
					}	
					catch(\Exception $e) {
						$errors .= $e->getMessage() . "\n";
					}
				}
					//finally pass all the variables to user environment
					$result = array('error' => $errors, 'newfiles' => $newfiles);
			}
		}
		else {
			$result = array('error' => "ERROR uploading file! Cannot parse form data."); 
			error_log('RNS_upload: ERROR uploading file! Cannot parse form data.');
		}
		echo new JsonResponse($result);
		$app->close();
	}
	
	public static function getMenuCatID() {
		//επιστρέφει το id από το url: com_content&view=article&id=XX
		$app = Factory::getApplication();
		$menu = $app->getMenu();
		$current_item = $menu->getActive()->query['id'];
		return $current_item;
	}	
	
	//Delete file
	public static function deletePdfAjax() {	
		
		$app     = Factory::getApplication();
        $input   = $app->getInput();
		$session = Factory::getSession();
		$user    = $app->getIdentity();
		$db = Factory::getDbo();

		// AT FIRST DO Anti-CSRF Token Validation (it saves server resources) 
        if (!$session->checkToken('post')) {
            http_response_code(403);
            echo new JsonResponse(null, 'Invalid token.', true);
            $app->close();
        }
    
		// Fetch the module parameters using helper function
		$moduleId = $input->get('rns_module_id', 0, 'int');
    	$params   = self::getModuleParamsById($moduleId, 'mod_rnsupload');

		//Check if module config exists
		if ($moduleId <= 0 || count($params->toArray()) === 0) {
			http_response_code(500);
			echo new JsonResponse(null, 'Module configuration not found.', true);
			$app->close();
		}

		// Permission verification
		$users_can_write_array = $params->get('can_write', array());
		$user_grant_upload = self::getUploadPermission($users_can_write_array);

		if (!$user_grant_upload) {
			http_response_code(403);
			echo new JsonResponse(null, 'Unauthorized access. You do not have permission.', true);
			$app->close();
		}

		$id_arxeiou = $input->getInt('id_arxeiou', 0);
		$upload_folder = $input->get('upload_folder', '', 'string');
		$filename = $input->getString('filename', '');
		$errors = "";
		
		if ($id_arxeiou > 0 && !empty($upload_folder) && !empty($filename)) {
			
			if (!Path::check($filename) || !Path::check($upload_folder)) {
				http_response_code(400);
				echo new JsonResponse(null, 'Illegal path manipulation attempt.', true);
				$app->close();
			}

			$relative_filepath = Path::clean('images/' . $upload_folder . '/' . $filename);
        	$eggrafo_to_del    = Path::clean(JPATH_SITE . '/' . $relative_filepath);

			error_log("RNS_Upload: relative_filepath to delete: " . $relative_filepath);
			error_log("RNS_Upload: eggrafo_to_del: " . $eggrafo_to_del);
		
			//attempt to delete the file if it exists
			if (file_exists($eggrafo_to_del)) {
				try {

					File::delete($eggrafo_to_del);
					error_log("RNS_Upload: Deleted file: " . $eggrafo_to_del );
				}
				catch (\Exception $e) {
					$errors .= 'RNS_Upload: delete file error' .  $e->getMessage();
				}

					//βρες το αρχείο στη βάση ώστε να ενημέρωσεις για έξτρα ασφάλεια
					$query = $db->getQuery(true);
					$query->select('id')
							->from($db->quoteName('#__rns_upload_files'))
							->where($db->quoteName('filename') . ' = ' . $db->quote($filename))
							->where($db->quoteName('filepath') . ' = ' . $db->quote($relative_filepath))
							->where($db->quoteName('published') . ' = 1');
					$db->setQuery($query);
					$filename_id = $db->loadResult();
					
					//τώρα ενημέρωσε τη βάση
					if (isset($filename_id)) {
						$file_to_unpublish = new \stdClass();
						$file_to_unpublish->id = $filename_id;
						$file_to_unpublish->modified = date('Y-m-d H:i:s');
						$file_to_unpublish->modified_by =  $user->id;
						$file_to_unpublish->published = 0;
						try {
							$db->updateObject('#__rns_upload_files', $file_to_unpublish, 'id');
						}	
						catch(\Exception $e) {
							$errors .= "DB error during update: " . $e->getMessage() . "\n";
						}
					}
					else {
						$errors .= "DB error, cannot find filename_id.\n";
					}
					
				
			}
			else
				$errors .= "RNS_Upload: ERROR deleting the file! It doesn't exist!";
			
		}
		else {
			$errors .= "RNS_Upload: ERROR! Unable to delete file of null id" ;
		}
		
		if ($errors) {
			error_log( print_r( $errors , TRUE) );
		}
		
		$result = array("errors" => $errors);
		echo new  JsonResponse($result);
		$app->close();
	}
	
	//Rename file
	public static function renamePdfAjax() {
		
		$app     = Factory::getApplication();
        $input   = $app->getInput();
		$session = Factory::getSession();
		$user    = $app->getIdentity();
		$db = Factory::getDbo();

		// AT FIRST DO Anti-CSRF Token Validation (it saves server resources) 
        if (!$session->checkToken('post')) {
            http_response_code(403);
            echo new JsonResponse(null, 'Invalid token.', true);
            $app->close();
        }
    
		// Fetch the module parameters using helper function
		$moduleId = $input->get('rns_module_id', 0, 'int');
    	$params   = self::getModuleParamsById($moduleId, 'mod_rnsupload');

		//Check if module config exists
		if ($moduleId <= 0 || count($params->toArray()) === 0) {
			http_response_code(500);
			echo new JsonResponse(null, 'Module configuration not found.', true);
			$app->close();
		}

		//  Run permission check
		$users_can_write_array = $params->get('can_write', array());
		$user_grant_upload = self::getUploadPermission($users_can_write_array);

		if (!$user_grant_upload) {
			http_response_code(403);
			echo new JsonResponse(null, 'Unauthorized access. You do not have permission.', true);
			$app->close();
		}

		$id_arxeiou = $input->getInt('id_arxeiou', 0);
		$upload_folder = $input->get('upload_folder', '', 'string');
		$old_filename = $input->getString('old_filename', '');
		$new_filename = $input->getString('new_filename', '');

		$errors = "";
		$result = array('error' => '', 'message' => 'ok');

		if ($id_arxeiou > 0 && !empty($upload_folder) && !empty($old_filename) && !empty($new_filename)) {

			$old_relative_filepath = Path::clean('images/' . $upload_folder . '/' . $old_filename);
			$old_eggrafo_fullpath    = Path::clean(JPATH_SITE . '/' . $old_relative_filepath);

			$new_relative_filepath = Path::clean('images/' . $upload_folder . '/' . $new_filename);
			$new_eggrafo_fullpath    = Path::clean(JPATH_SITE . '/' . $new_relative_filepath);

			// SECURITY FIX: Perform path checking BEFORE evaluating file_exists
			if (!Path::check($old_relative_filepath) || !Path::check($new_relative_filepath)) {
				http_response_code(400);
				echo new JsonResponse(null, 'Illegal path manipulation attempt.', true);
				$app->close();
			}

			// Attempt to rename the file if it exists
			if (file_exists($old_eggrafo_fullpath)) {
				$fileMoveSuccess = false;
				try {
					// If File::move fails, it throws an exception or returns false
					if (File::move($old_eggrafo_fullpath, $new_eggrafo_fullpath)) {
						$fileMoveSuccess = true;
					} else {
						$errors .= "RNS_Upload: Filesystem rejected moving the file.\n";
					}
				}
				catch (\Exception $e) {
					$errors .= 'RNS_Upload: rename file error: ' . $e->getMessage() . "\n";
				}

				// CRITICAL FIX: Only touch the DB if the physical file move succeeded
				if ($fileMoveSuccess) {
					// Find the specific file entry using old text references
					$query = $db->getQuery(true);
					$query->select($db->quoteName('id'))
							->from($db->quoteName('#__rns_upload_files'))
							->where($db->quoteName('filename') . ' = ' . $db->quote($old_filename))
							->where($db->quoteName('filepath') . ' = ' . $db->quote($old_relative_filepath))
							->where($db->quoteName('published') . ' = 1');
					
					$db->setQuery($query);
					$filename_id = $db->loadResult();
						
					// Update DB entry with new file properties
					if (!empty($filename_id)) {
						$file_to_update = new \stdClass();
						$file_to_update->id          = $filename_id;
						$file_to_update->modified    = date('Y-m-d H:i:s');
						$file_to_update->modified_by = $user->id;
						$file_to_update->filename    = $new_filename;
						$file_to_update->filepath    = $new_relative_filepath;
						
						try {
							$db->updateObject('#__rns_upload_files', $file_to_update, 'id');
						}
						catch(\Exception $e) {
							$errors .= "DB error during update: " . $e->getMessage() . "\n";
						}
					}
					else {
						$errors .= "DB error, cannot find a matching filename_id for historical file reference.\n";
					}
				}
			}
			else {
				$errors .= "RNS_Upload: ERROR renaming the file! It doesn't exist on disk!\n";
			}
		}
		else {
			$errors .= "RNS_Upload: ERROR! Missing payload parameters or invalid container ID\n";
		}
					
		// 5. Unify JSON Response tracking based cleanly on the $errors string
		if (!empty($errors)) {
			error_log($errors);
			$result = array("error" => $errors, "message" => "failed");
			echo new JsonResponse($result, 'Operation encountered execution warnings.', true);
		} else {
			$result = array('error' => '', 'message' => 'ok');
			echo new JsonResponse($result, 'File renamed successfully.', false);
		}

		$app->close();
	}

	public static function getJoomlaArticle($joomla_alias) {
		
		$app     = Factory::getApplication();
		$user    = $app->getIdentity();
		$db = Factory::getDbo();

		$have_access = true; // should change this to specific users

		if (!$have_access || $user->guest) {
			echo 'Unauthorized access. You do not have permission.';
			return [];
		}

		if (empty($joomla_alias)) return null;

		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__content'))
			->where($db->quoteName('alias') . ' = ' . $db->quote($joomla_alias))
			->where($db->quoteName('state') . ' = 1');
		$db->setQuery($query);

		$article = $db->loadObject();

		return $article;

	}

	public static function getFilesByCategory($catid)
	{
		$app     = Factory::getApplication();
		$user    = $app->getIdentity();
		$db = Factory::getDbo();

		$have_access = true; // should change this to specific users

		if (!$have_access || $user->guest) {
			echo 'Unauthorized access. You do not have permission.';
			return [];
		}
		
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__rns_upload_files'))
			->where($db->quoteName('catid') . ' = ' . (int) $catid)
			->where($db->quoteName('published') . ' = 1')
			->order('created');
		$db->setQuery($query);
		$files = $db->loadObjectList();

		return $files;
	}

	//The ideal is not letting the user know the pdf path because it is public!
 	public static function downloadFileAjax() {

		$app  = Factory::getApplication();
		$user = $app->getIdentity();
		$db = Factory::getDbo();

		// Must be logged in
		if ($user->guest) {
			http_response_code(403);
			echo 'Forbidden';
			$app->close();
		}

		$file_id = $app->input->getInt('id');
		if (!$file_id) {
			http_response_code(400);
			echo 'Invalid file';
			$app->close();
		}

		// Fetch file from DB
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__rns_upload_files'))
			->where($db->quoteName('id') . ' = ' . (int) $file_id)
			->where($db->quoteName('published') . ' = 1');
		$db->setQuery($query);
		$file = $db->loadObject();

		if (!$file) {
			http_response_code(404);
			echo 'File not found';
			$app->close();
		}

		// OPTIONAL: ACL check here
		// if (!$user->authorise('core.view', 'com_content.catid.' . $file->catid)) { ... }

		$fullPath = JPATH_SITE . '/' . $file->filepath;

		if (!file_exists($fullPath)) {
			http_response_code(404);
			echo 'Missing file';
			$app->close();
		}

		//Clean output buffer
		while (ob_get_level()) {
			ob_end_clean();
		}

		header('Content-Type: application/octet-stream');
		header('Content-Length: ' . filesize($fullPath));
		header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
		header('X-Content-Type-Options: nosniff');

		readfile($fullPath);
		$app->close();
	}

	public static function getModuleParamsById(int $moduleId): Registry
	{
		if ($moduleId <= 0) {
			return new Registry();
		}

		$moduleName = 'mod_rnsupload';

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__modules'))
			->where($db->quoteName('id') . ' = ' . (int) $moduleId)
			->where($db->quoteName('module') . ' = ' . $db->quote($moduleName));

		$db->setQuery($query);
		$paramsString = $db->loadResult();

		// If the module instance exists, parse and return its parameters
		return $paramsString ? new Registry($paramsString) : new Registry();
	}

}
?>

