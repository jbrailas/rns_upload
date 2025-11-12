<?php
/**
 * @package		RNS Upload and Files Display Module for Joomla 5
* @version		1.2.0
* @author		Giannis Brailas (jbrailas@rns-systems.eu)
* @copyright	2025 Giannis Brailas
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

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ModuleHelper;

JLoader::register('modRnsUploadHelper', __DIR__ . '/helper.php');

//get the module class designation
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx', ''));

// Set UTF8 to locale -> read correctly all greek filenames
setlocale(LC_CTYPE, 'en_GB.utf8');

//get upload special permissions
$users_can_write_array = $params->get( 'can_write', array()); 
$user_grant_upload = modRnsUploadHelper::getUploadPermission($users_can_write_array);

//get the id of the menu
$menu_catid = modRnsUploadHelper::getMenuCatID();

$fpath = $_SERVER['HTTP_HOST'];
if (isset($_SERVER['HTTPS'])) {
	if (strpos($fpath,'https://') === false)
		$fpath = 'https://'.$fpath;
}
else {
	if (strpos($fpath,'http://') === false)
		$fpath = 'http://'.$fpath;
}
$jpath = JPATH_SITE;

$doc = Factory::getDocument();

$headData = $doc->getHeadData();
$scripts = $headData['scripts'];

//remove your script, i.e. mootools
unset($scripts[Uri::root(true) . '/modules/mod_rnsupload/assets/js/jquery.mobile-1.4.5.min.js']);
$headData['scripts'] = $scripts;
$doc->setHeadData($headData);
	
//load the CSS and Javascript files
$doc->addStyleSheet(Uri::root(true) . '/modules/mod_rnsupload/assets/css/rnsupload_v1b.css');

//Load Module Upload Folder
$SelectedUploadFolder = trim($params->get( 'selected_upload_folder', ''));
	
//Load Image
$SelectedImage = trim($params->get( 'selected_image', ''));
	
//Load option for auto reload page
$auto_reload_page = $params->get( 'autoReloadPage', 0) == 1 ? TRUE : FALSE;

require(ModuleHelper::getLayoutPath('mod_rnsupload', $params->get('layout', 'default')));
