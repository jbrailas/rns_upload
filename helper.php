<?php
/**
* @version		1.1
* @author		Giannis Brailas (jbrailas@rns-systems.eu)
* @copyright	Giannis Brailas
* @license		GNU/GPLv3

RNS Upload and Files Display Module for Joomla!
Copyright (C) 2024  Giannis Brailas (RNS-SYSTEMS)

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

// no direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Filesystem\File;
	
class modRnsUploadHelper {

	public static function movingUploadedFiles($source_path, $dest_path) {
		
		//Create folder if it doesn't exist!
		/* NO! 
		if(!file_exists($dest_path)) {
			mkdir($dest_path, 0755, true);
			error_log('Created new rns_upload folder = ' . $dest_path);
		}
		*/
		
		//search for files
		$files = array();
		
		//Opens directory
		$myDirectory = opendir($source_path);
		
		// Gets each entry
		while ($entryName = readdir($myDirectory)) {
			$is_permitted_format = substr($entryName, -3);
			$is_permitted_format_v4 = substr($entryName, -4);
			if ($entryName != "." && $entryName != ".." && 
			($is_permitted_format == "pdf" || $is_permitted_format == "PDF" 
			|| $is_permitted_format == "zip" || $is_permitted_format == "ZIP"
			|| $is_permitted_format == "doc" || $is_permitted_format == "DOC"
			|| $is_permitted_format == "xls" || $is_permitted_format == "XLS"
			|| $is_permitted_format == "ppt" || $is_permitted_format == "PPT"
			|| $is_permitted_format == "mp4" || $is_permitted_format == "MP4"
			|| $is_permitted_format == "msg" || $is_permitted_format == "MSG"
			|| $is_permitted_format == "png" || $is_permitted_format == "PNG"
			|| $is_permitted_format_v4 == "docx" || $is_permitted_format_v4 == "DOCX"
			|| $is_permitted_format_v4 == "xlsx" || $is_permitted_format_v4 == "XLSX"
			|| $is_permitted_format_v4 == "pptx" || $is_permitted_format_v4 == "PPTX"
			)) {
				//μετακίνησε το αρχείο από τον προσωρινό φάκελο, στον φάκελο που πρέπει.
				rename($source_path.$entryName, $dest_path.$entryName);
				
				//get catid
				//to do

				//τώρα γράψε στη βάση
				$errors = 0;
				$new_file = new stdClass();
				$new_file->catid = 161; //Θέματα Ασφάλειας & Υγείας ΠΡΟΣΟΧΗ έχει μπει καρφωτά!
				$new_file->filename = $entryName;
				$new_file->filepath = $dest_path . $entryName;
				$new_file->created = date('Y-m-d H:i:s');
				//$new_file->created_by =  Factory::getUser()->id; //δεν ξέρουμε ποιος
				$new_file->published = 1;
				try {
					Factory::getDbo()->insertObject('#__rns_upload_files', $new_file);
					}	
				catch(Exception $e) {
					$errors .= $e->getMessage() . "\n";
				}
			}
		}
			
		// Closes directory
		closedir($myDirectory);
	}
	
	
	//Έλεγχος για επιπλέον δικαιώματα -> 
	// Όσοι χρήστες είναι στο group access θα έχουν δικαίωμα να ανεβάζουν και να διαγράφουν αρχεία
	public static function getUploadPermission() { 

		$user_grant_upload = false;
		$cur_user_id = Factory::getUser()->id;
		$db = Factory::getDbo();
		$group_access = '11';  //Access μόνο στα groups IT Department ID 11 και στην Βίκη ID 105
		$distinct_user_access = '105'; //Access και σε συγκεκριμένους χρήστες
        $query = $db->getQuery(true);
		$query->select('distinct user_id')
                ->from('#__user_usergroup_map')
                ->where('group_id IN ('.$group_access.') or user_id IN ('.$distinct_user_access.')');
        $db->setQuery($query);
		foreach($db->loadObjectList() as $granted_ids):		 
			if ($cur_user_id == $granted_ids->user_id) {
				$user_grant_upload = true;
			}
		endforeach;
		return $user_grant_upload;
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
	public static function filetime_callback($a, $b)
	{
	  if (filemtime($a) === filemtime($b)) return 0;
	  return filemtime($a) < filemtime($b) ? -1 : 1; 
	}
		
	
	// Snippet from PHP Share: http://www.phpshare.org
    public static function formatSizeUnits($bytes)
    {
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
			$errors = "";
			$efu_maxsize = $_POST['MaxFileSize'];
			$efu_parent = "images";
			$project_folder = $_POST['PdfUploadFolder'];
			$efu_replace = false;
			$efu_scriptsinarchives = false;
			$efu_filetypes = "application/msword;application/excel;application/pdf;application/powerpoint;application/x-zip;application/x-zip-compressed;application/zip;application/vnd.openxmlformats-officedocument.wordprocessingml.document;application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;application/powerpoint;application/vnd.ms-powerpoint;application/vnd.openxmlformats-officedocument.presentationml.presentation;application/octet-stream;application/CDFV2-unknown;image/png";
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
					$newfiles[$filescount] = new stdClass;
					$newfiles[$filescount]->filename = $filename;
					$newfiles[$filescount]->filepath = $filepath;
					
					$file_params = array($efu_parent, $project_folder, $file, $efu_maxsize, $efu_replace, $efu_scriptsinarchives, 1, $efu_filetypes);
					//Call fileupload function to create folder and upload file	
					$result_file_upload = rnsuploadFileupload::getFileToUpload($file_params);
					
					if (!empty($result_file_upload) && (($result_file_upload['type'] == 'warning') || ($result_file_upload['type'] == 'error'))) {
						$errors .= $result_file_upload['text'];
						//break;
					}
					
					//get total pages count using SetaPDF core
					//$reader = new SetaPDF_Core_Reader_File(JPATH_SITE . DIRECTORY_SEPARATOR . $filepath);
					//$document = SetaPDF_Core_Document::load($reader);
					//$newfiles[$filescount]->total_pages = $document->getCatalog()->getPages()->count();
					
					$filescount++;

					//τώρα γράψε στη βάση
					$new_file = new stdClass();
					$new_file->catid = $_POST['menu_catid'];
					$new_file->filename = $filename;
					$new_file->filepath = $efu_parent . DIRECTORY_SEPARATOR . $project_folder . DIRECTORY_SEPARATOR . $filename;
					$new_file->created = date('Y-m-d H:i:s');
					$new_file->created_by =  Factory::getUser()->id;
					$new_file->published = 1;
					try {
						Factory::getDbo()->insertObject('#__rns_upload_files', $new_file);
						}	
					catch(Exception $e) {
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
		$jpath = JPATH_SITE;
		$jpath = rtrim($jpath, "/\\ \t\n\r\0\x0B");	
		$errors = "";
		if (isset($_POST['id_arxeiou']) && $_POST['id_arxeiou'] != "0") {
			$filepath = "images" . DIRECTORY_SEPARATOR . $_POST['upload_folder'] . DIRECTORY_SEPARATOR . $_POST['filename'];
			$eggrafo_to_del = $jpath .DIRECTORY_SEPARATOR . $filepath ;
			//error_log("file to delete: " . $eggrafo_to_del);
						
			//attempt to delete the file if it exists
			if (file_exists($eggrafo_to_del)) {
				try {
					File::delete($eggrafo_to_del);
					$result = array('error' => '', 'message' => 'ok');
					error_log("Deleted file: " . $eggrafo_to_del );
					
					
					//βρες το αρχείο στη βάση ώστε να ενημέρωσεις
					$db = Factory::getDbo();
					$query = $db->getQuery(true);
					$query->select('id')
							->from('#__rns_upload_files')
							->where('filename = "' . $_POST['filename'] . '"')
							->where('filepath = "' . $filepath . '"')
							->where('published = 1');
					$db->setQuery($query);
					$filename_id = $db->loadResult();
					
					//τώρα ενημέρωσε τη βάση
					if (isset($filename_id)) {
						$file_to_unpublish = new stdClass();
						$file_to_unpublish->id = $filename_id;
						$file_to_unpublish->modified = date('Y-m-d H:i:s');
						$file_to_unpublish->modified_by =  Factory::getUser()->id;
						$file_to_unpublish->published = 0;
						try {
							Factory::getDbo()->updateObject('#__rns_upload_files', $file_to_unpublish, 'id');
							}	
						catch(Exception $e) {
							$errors .= $e->getMessage() . "\n";
						}
					}
					
				}
				catch (Exception $e) {
					$errors .= 'RNS_Upload: delete file error' .  $e->getMessage();
				}
			}
			else
				$result = array("error" => "RNS_Upload: ERROR deleting the file! It doesn't exist!");
		}
		else {
			$result = array("error" => "RNS_Upload: ERROR! Unable to delete file of null id");
			error_log( print_r( $result , TRUE) );
		}
					
		echo new  JsonResponse($result);
	}
	
	//Rename file
	public static function renamePdfAjax() {	
		$jpath = JPATH_SITE;
		$jpath = rtrim($jpath, "/\\ \t\n\r\0\x0B");	
		if (isset($_POST['id_arxeiou']) && $_POST['id_arxeiou'] != "0") {

			$old_eggrafo_name = $jpath .DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR . $_POST['upload_folder'] . DIRECTORY_SEPARATOR . $_POST['old_filename'] ;
			$new_eggrafo_name = $jpath .DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR . $_POST['upload_folder'] . DIRECTORY_SEPARATOR . $_POST['new_filename'] ; 
			//attempt to rename the file if it exists
			if (file_exists($old_eggrafo_name)) {
				try {
					File::move($old_eggrafo_name, $new_eggrafo_name);
					$result = array('error' => '', 'message' => 'ok');
					//error_log("Renamed file: " . $old_eggrafo_name );
				}
				catch (Exception $e) {
					$result = array('RNS_Upload: rename file error: ' => $e->getMessage());
				}
			}
			else
				$result = array("error" => "RNS_Upload: ERROR renaming the file! It doesn't exist!");
		}
		else {
			$result = array("error" => "RNS_Upload: ERROR! Unable to rename file of null id");
			error_log( print_r( $result , TRUE) );
		}
					
		echo new  JsonResponse($result);
	}	
}
?>

