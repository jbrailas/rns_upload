<?php
/**
* @version		1.1.2
* @author		Giannis Brailas (jbrailas@rns-systems.eu)
* @copyright	Giannis Brailas
* @license		GNU/GPLv3

RNS Upload and Files Display Module for Joomla!
Copyright (C) 2024  Giannis Brailas

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
defined( '_JEXEC' ) or die( 'Restricted access' );
?>

<script>
(function(b){b.support.touch="ontouchend" in document;if(!b.support.touch){return;}var c=b.ui.mouse.prototype,e=c._mouseInit,a;function d(g,h){if(g.originalEvent.touches.length>1){return;}g.preventDefault();var i=g.originalEvent.changedTouches[0],f=document.createEvent("MouseEvents");f.initMouseEvent(h,true,true,window,1,i.screenX,i.screenY,i.clientX,i.clientY,false,false,false,false,0,null);g.target.dispatchEvent(f);}c._touchStart=function(g){var f=this;if(a||!f._mouseCapture(g.originalEvent.changedTouches[0])){return;}a=true;f._touchMoved=false;d(g,"mouseover");d(g,"mousemove");d(g,"mousedown");};c._touchMove=function(f){if(!a){return;}this._touchMoved=true;d(f,"mousemove");};c._touchEnd=function(f){if(!a){return;}d(f,"mouseup");d(f,"mouseout");if(!this._touchMoved){d(f,"click");}a=false;};c._mouseInit=function(){var f=this;f.element.bind("touchstart",b.proxy(f,"_touchStart")).bind("touchmove",b.proxy(f,"_touchMove")).bind("touchend",b.proxy(f,"_touchEnd"));e.call(f);};})(jQuery);

	var pdfNames = [];
	var pdfIds = [];
</script>
<script type="text/javascript">
 jQuery(document).ready(function($){
	
	
	//hide icons for print, email and edit
	$(".icons").hide();
	
	// When the user clicks on <span> (x), close the modal
	$(".popup_pages_close").click(function(){
		document.getElementById('pagesModal').style.display = "none";
	});	
	
	
	$("#editBtn").click(function(){
		if(!$('.edit_elements').is(':visible'))
			$(".edit_elements").show();
		else
			$(".edit_elements").hide();
	});
	
	// When the user clicks the button, open the modal 
	$("#helpBtn").click(function(){
		document.getElementById('helpModal').style.display = "block";
	});

	// When the user clicks on <span> (x), close the modal
	$(".help_close").click(function(){
		document.getElementById('helpModal').style.display = "none";
	});	
	
	// When the user clicks anywhere outside of the modal, close it
	window.onclick = function(event) {
		if (event.target == document.getElementById('helpModal')) 
			document.getElementById('helpModal').style.display = "none";
		else if (event.target == document.getElementById('renameModal')) 
			document.getElementById('renameModal').style.display = "none"; 
	}
	
	// When the user clicks on <span> (x), close the modal
	$(".rename_close").click(function(){
		document.getElementById('renameModal').style.display = "none";
	});	
		
	$("#rns_pdf_files_drop_area").on('dragenter', function (e){
		e.preventDefault();
		$(this).css('background', '#BBD5B8');
	});

	$("#rns_pdf_files_drop_area").on('dragover', function (e){
		e.preventDefault();
	});

	$("#rns_pdf_files_drop_area").on('drop', function (e){
		  $(this).css('background', '#D8F9D3');
		  e.preventDefault();
		  var pdf = e.originalEvent.dataTransfer.files;
		  createFormData(pdf);
	});
	
	$("#rns_pdf_files_drop_area").click(function(){	
		 $('#eggrafo').click();
	});
	
	$("#eggrafo").change(function(){
		 $("#rns_pdf_files_drop_area").css('background', '#D8F9D3');
         var pdf = $('#eggrafo')[0].files;
         createFormData(pdf);
	});		
	
	//Default sorting of the table
	sortInteger('table_arxeia', 2,'date');
	sortInteger('table_arxeia', 2,'date');
 });
</script>
<?php if ($user_grant_upload == true && $auto_reload_page) : ?>
<script>
	var IDLE_TIMEOUT = 14; //seconds
	var _idleSecondsCounter = 0;
	document.onclick = function() {
		_idleSecondsCounter = 0;
	};
	document.onmousemove = function() {
		_idleSecondsCounter = 0;
	};
	document.onkeypress = function() {
		_idleSecondsCounter = 0;
	};
	document.onwheel = function() {
		_idleSecondsCounter = 0;
	};
	var myInterval = window.setInterval(CheckIdleTime, 1000);
		
	function CheckIdleTime() {
			_idleSecondsCounter++;
			var oPanel = document.getElementById("SecondsUntilExpire");
			if (oPanel)
				oPanel.innerHTML = (IDLE_TIMEOUT - _idleSecondsCounter) + '"';
			if (_idleSecondsCounter >= IDLE_TIMEOUT) {
				window.clearInterval(myInterval);
				oPanel.innerHTML = ("Επαναφόρτωση...");
				location.href = location.href;
			}
	}
</script>
<?php endif; ?>
<?php if ($user_grant_upload == true) : ?>
<script>
	function sleep(ms) {
		return new Promise(resolve => setTimeout(resolve, ms));
	}
	
	function reverse(d) {
		if (document.getElementById(d).style.display == 'none')
			document.getElementById(d).style.display = "block";
		else 
			document.getElementById(d).style.display = "none";
	}
	
	function createFormData(pdf) {
		var formPDF = new FormData();
		for (i = 0; i < pdf.length; i++) {
			//formPDF.append('userPDF', pdf[0]);
			formPDF.append('userPDF[]', pdf[i]);
		}
		//console.log('pdf length is ' + pdf.length);
		formPDF.append('PdfUploadFolder', document.getElementById("pdfuploadfolder").value);
		formPDF.append('MaxFileSize', 25165824);
		formPDF.append('menu_catid', document.getElementById("menu_catid").value);
		uploadFormData(formPDF);
	}

	function uploadFormData(formData) {
		var ajax_uploadpdf_url = "index.php?option=com_ajax&module=rnsupload&method=uploadPdfs&format=raw";
		//δειξε το loading image
		document.getElementById("loading").style.display ="block";
		sleep(500).then(() => {
			jQuery.ajax({
				url: ajax_uploadpdf_url,
				type: "POST",
				data: formData,
				contentType:false,
				cache: false,
				processData: false,
				success: function(data){
					document.getElementById("loading").style.display ="none";
					try {
						var ssdata = JSON.parse(data).data;
						//console.log(ssdata);
					} catch (e) {
						alert("Το παρακάτω σφάλμα βρέθηκε:\n" + data);
						return;
					}
					if (ssdata.error == "") {	
						//Reload page if there is no error
						location.href = location.href;
					}
					else
						alert("Βρέθηκε σφάλμα!\n" + ssdata.error);
				},
				error: function(xhr, status, error){
					document.getElementById("loading").style.display = "none";
					alert( "Σφάλμα: Αδυναμία καταχώρησης νέων αρχείων\n" + error);
					console.log(xhr);
				}
			});
		});
	}

	function delete_file(id_arxeiou, upload_folder, filename) {
		//Ρώτησε για επιβεβαίωση
		if (!confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε το συγκεκριμένο αρχείο (' + filename +');\n')) {
			console.log('Ο χρήστης επέλεξε να μην διαγράψει το αρχείο ' + filename + ' | upload_folder: ' + upload_folder + '.');
			return;
		}
		
		console.log('Προς διαγραφή: Αρχείο ' + filename + ' | id: ' + id_arxeiou + ' | upload_folder: ' + upload_folder);	
		var ajaxdeleteurl = "index.php?option=com_ajax&module=rnsupload&method=deletePdf&format=raw";
		
			//δειξε το loading image
			document.getElementById("loading").style.display ="block";
			sleep(500).then(() => {	
				jQuery.ajax({
					type: 'POST',
					url: ajaxdeleteurl, 
					data: {id_arxeiou: id_arxeiou, filename : filename, upload_folder: upload_folder},
					success: function(data) {
						//hide loading image
						document.getElementById("loading").style.display = "none";
						try {
							var ssdata = JSON.parse(data).data;
						} catch (e) {
							alert("Το παρακάτω σφάλμα βρέθηκε:\n" + data);
							return;
						}
									
						//Εμφάνισε στον χρήστη τις αλλαγές
						if (ssdata.error == "") {
							//remove the row
							jQuery("#" + id_arxeiou).remove();
						}
						else
							alert("Βρέθηκε σφάλμα!\n" + ssdata.error);
					},
					error: function(xhr, status, error) {
						//hide loading image
						document.getElementById("loading").style.display = "none";
						//show the error
						alert( "Σφάλμα: " + error + "\nΑδυναμία διαγραφής αρχείου!" );
					}
				});
			}); //end sleep	
	}
	
	function rename_file(id_arxeiou, upload_folder, filename) {
		//φόρτωσε τα δεδομένα στο popup
		jQuery("#popup_old_filename").val(filename);
		jQuery("#popup_new_filename").val(filename);
		jQuery("#selected_id_arxeiou").val(id_arxeiou);
		
		//εμφάνισε το Popup
		jQuery("#renameModal").show();
	}

	function SaveNow(editing) {
		//Ο χρήστης πάτησε αποθήκευση στην μετονομασία του αρχείου
		var new_filename = jQuery("#popup_new_filename").val();
		var old_filename = jQuery("#popup_old_filename").val();
		var id_arxeiou = jQuery("#selected_id_arxeiou").val();
		var upload_folder = jQuery("#pdfuploadfolder").val();
		console.log('Προς μετονομασία: Αρχείο ' + old_filename + ' | id: ' + id_arxeiou + ' | upload_folder: ' + upload_folder);	
		console.log('Νέο όνομα αρχείου: ' + new_filename);
		var ajaxrenameurl = "index.php?option=com_ajax&module=rnsupload&method=renamePdf&format=raw";
			//δειξε το loading image
			document.getElementById("loading").style.display ="block";
			sleep(500).then(() => {	
				jQuery.ajax({
					type: 'POST',
					url: ajaxrenameurl, 
					data: {id_arxeiou: id_arxeiou, old_filename : old_filename, new_filename : new_filename, upload_folder: upload_folder},
					success: function(data) {
						//hide loading image
						document.getElementById("loading").style.display = "none";
						//ενημέρωσε το filename να το δει ο χρήστης
						//jQuery("#" + id_arxeiou + ".filename a").html("<a href='' target='blank_'>" + jQuery("#popup_new_filename").val() + "</a>");
						//jQuery("#" + id_arxeiou + " .filename").get(0).lastChild.nodeValue = jQuery("#popup_new_filename").val();
						jQuery("#" + id_arxeiou + " .filename").html("<a href='" + "images/" + upload_folder + "/" + jQuery("#popup_new_filename").val() + "' target='blank_'>" + jQuery("#popup_new_filename").val() + "</a>");
						//κλείσε το Popup
						jQuery("#renameModal").hide();
					},
					error: function(xhr, status, error) {
						//hide loading image
						document.getElementById("loading").style.display = "none";
						//show the error
						alert( "Σφάλμα: " + error + "\nΑδυναμία μετονομασίας αρχείου!" );
					}
				});
			}); //end sleep			
	}
	
	function enableSaveChangesBtn() {
		document.getElementById("SaveChangesBtn").disabled = false;
	}	
</script>

<div id="loading" style="display:none;">
	<img id="loading-image" src="<?php echo $fpath.'/modules/mod_rnsupload/assets/images/ajax-loading.svg'?>" alt="Loading..." />
</div>

<!-- Rename Modal -->
<div id="renameModal" class="modal_window">
	<!-- Modal content -->
	<div class="modal_content">
		<span class="rename_close modal_close_btn">&times;</span>
		<h3>Μετονομασία αρχείου</h3>
		<div class="control-group" style="padding-bottom: 5px;">  
			<textarea style="float:left; height: 74px; margin-bottom: 10px;" type="text" id="popup_new_filename" name="popup_new_filename" class="input-xlarge input-large-text inputbox" onclick = 'enableSaveChangesBtn();' ></textarea>
			<input type="hidden" id="popup_old_filename" name="popup_old_filename" class="inputbox" placeholder="Το παλιό όνομα του αρχείου">
			<input type="hidden" id="selected_id_arxeiou" name="selected_id_arxeiou" class="inputbox" placeholder="Το id του επιλεγμένου αρχείου">
		</div>
		<div style="clear:both;"></div>
		<button id="SaveChangesBtn" type="submit" class="btn btn-success" style="margin:10px 0 10px 0;" onclick = 'SaveNow(true);' disabled>
			<i class="icon-ok icon-white"></i> <?php echo 'Μετονομασία'; ?>
		</button>
		<button class="btn btn-danger rename_close" style="margin:10px 0 10px 0;">
			<i class="icon-cancel icon-white"></i> <?php echo 'Ακύρωση'; ?>
		</button>
	</div>
</div>

<div class="btn-group pull-right">
	<label style="float:left; padding-top: 7px;"><span class="icon-stopwatch" onclick="window.location.href=window.location.href" style="cursor: pointer; color: #b31111; font-size: 20px;" ></span></label>
	<div id="SecondsUntilExpire" style="float:right; padding-top: 9px; color: #b31111; font-size: 20px;"><span class="icon-infinite"></span></div>
</div>

<div class="helpp" style="float:right; padding: 0 10px 10px 0;">
<!-- Trigger/Open The Modal -->
	<button id="helpBtn" class="btn btn-help" style="border: 1px solid gray;"><span class="icon-help"></span>Βοήθεια</button>
	<!-- The Modal -->
	<div id="helpModal" class="modal_window">
		<!-- Modal content -->
		<div class="modal_content">
			<span class="help_close modal_close_btn">&times;</span>
				<?php echo "{article efarmogi_upload}{/article}" ?>
		</div>
	</div>
	
</div style="float:right; padding: 0 10px 10px 0;">
	<button id="editBtn" class="btn btn-warning" style="border: 1px solid gray;">
		<i class="fa fa-edit"></i>
		Προσθήκη/Επεξεργασία
	</button>
</div>

<div style="float"

<div class="clearfix"></div>

<?php endif; ?>
	
<div class="<?php echo $moduleclass_sfx;?>">
	<?php if ($SelectedImage) { ?>
	<div style="float:left; padding-right: 10px;">
		<img src="<?php echo $SelectedImage; ?>" alt="<?php echo basename($SelectedImage); ?>" width="500" height="231">
	</div>
	<?php } ?>
	
	<?php
	//Check for new files when user uses SAMBA for uploading files!
	//TODO: Ο φάκελος SambaFolder να αλλάζει κάθε φορά.
	//Τώρα προς το παρόν να είναι ο safety_and_heath
	$SambaFolder = "images" . DIRECTORY_SEPARATOR . "safety_and_heath" . DIRECTORY_SEPARATOR;
	$source_path = "files" . DIRECTORY_SEPARATOR . "rns_upload" . DIRECTORY_SEPARATOR;
		
	modRnsUploadHelper::movingUploadedFiles($source_path, $SambaFolder);

	$efu_parent = "images" . DIRECTORY_SEPARATOR . $SelectedUploadFolder;
	$fileonlypath = $fpath .DIRECTORY_SEPARATOR . $efu_parent;
	$jpath = JPATH_SITE;
	$onlypath = $jpath .DIRECTORY_SEPARATOR . $efu_parent . DIRECTORY_SEPARATOR;	
		
	//now search for files to display
	$files = array();	
		
	//μέχρι πόσο παλιά να εμφανίζει
	$days = 3650; //3650 έως 10 χρόνια πριν 365 * 10
	
	$current_date = DateTime::createFromFormat('d-m-Y', date("d-m-Y"));
		
	//Μετρητής για να εμφανίσει μέχρι συγκεκριμένο αριθμό αρχείων
	$files_counter = 1;
	$numfilestodisplay = 50;

	//Opens directory
	if ($myDirectory = opendir($onlypath)) {
		
	// Gets each entry
	while (false !== ($entryName = readdir($myDirectory))) {
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
			|| $is_permitted_format_v4 == "xlsb" || $is_permitted_format_v4 == "XLSB"
			|| $is_permitted_format_v4 == "pptx" || $is_permitted_format_v4 == "PPTX"
		)) {
			$dirArray[]=$entryName;
				$files[]= $onlypath.$entryName;
		}
	}
		
	// Closes directory
	closedir($myDirectory);
	
	}
			
	// sort files
	usort($files, "modRnsUploadHelper::filetime_callback");
		
	// Counts elements in array
	$indexCount=count($files);
	//error_log("indexCount is: " . $indexCount);
		
	//προχώρα μόνο εφόσον βρέθηκαν αρχεία
	if ($indexCount) : ?>

	<script>
	function sortTable(element, n) {
	  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
	  table = document.getElementById(element);
	  switching = true;
	  //Set the sorting direction to ascending:
	  dir = "asc";
	  /*Make a loop that will continue until
	  no switching has been done:*/
	  while (switching) {
		//start by saying: no switching is done:
		switching = false;
		rows = table.getElementsByTagName("TR");
		/*Loop through all table rows (except the
		first, which contains table headers):*/
		for (i = 1; i < (rows.length - 1); i++) {
		  //start by saying there should be no switching:
		  shouldSwitch = false;
		  /*Get the two elements you want to compare,
		  one from current row and one from the next:*/
		  x = rows[i].getElementsByTagName("TD")[n];
		  y = rows[i + 1].getElementsByTagName("TD")[n];
		  /*check if the two rows should switch place,
		  based on the direction, asc or desc:*/
		  if (dir == "asc") {
			//if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
			  if (jQuery(x).text() > jQuery(y).text()) {
			  //if so, mark as a switch and break the loop:
			  shouldSwitch= true;  
			  break;
			}
		  } else if (dir == "desc") {
			//if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
				if (jQuery(x).text() < jQuery(y).text()) {
			  //if so, mark as a switch and break the loop:
			  shouldSwitch= true;
			  break;
			}
		  }
		}
		if (shouldSwitch) {
		  /*If a switch has been marked, make the switch
		  and mark that a switch has been done:*/
		  rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
		  switching = true;
		  //Each time a switch is done, increase this count by 1:
		  switchcount ++;
		} else {
		  /*If no switching has been done AND the direction is "asc",
		  set the direction to "desc" and run the while loop again.*/
		  if (switchcount == 0 && dir == "asc") {
			dir = "desc";
			switching = true;
		  }
		}
	  }
  
		//Βάλε το εικονίδιο της ταξινόμησης
		var headers_length = table.getElementsByTagName("TH").length;
		//var headers_length = table.getElementsByClassName("rTableHead").length;
		
		for (header = 1; header < headers_length ; header++) {
		var rheader = table.getElementsByTagName("TH")[header];
			if (dir == "asc") {
				if (header == n) {
					if ((rheader.innerHTML).substr(-4, 4) != "</i>")
						rheader.innerHTML  = rheader.innerHTML  + '<i class="icon-arrow-down2"></i>';
				else
						rheader.innerHTML  = (rheader.innerHTML).substr(0, (rheader.innerHTML).indexOf('<')) + '<i class="icon-arrow-down2"></i>';
				}
				else {
					if ((rheader.innerHTML).substr(-4, 4) === "</i>")
						rheader.innerHTML  = (rheader.innerHTML).substr(0, (rheader.innerHTML).indexOf('<'));
				}
			}
			else if (dir == "desc") {
				if (header == n) {
					if ((rheader.innerHTML).substr(-4, 4) != "</i>")
						rheader.innerHTML  = rheader.innerHTML  + '<i class="icon-arrow-up2"></i>';
					else
						rheader.innerHTML  = (rheader.innerHTML).substr(0, (rheader.innerHTML).indexOf('<')) + '<i class="icon-arrow-up2"></i>';
				}
				else {
					if ((rheader.innerHTML).substr(-4, 4) === "</i>")
						rheader.innerHTML  = (rheader.innerHTML).substr(0, (rheader.innerHTML).indexOf('<'));
				}
			}
			//console.log("innerHTML is " + rheader.innerHTML);
		}
	}

	function sortInteger(element, n, type) {
	  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
	  table = document.getElementById(element);
	  switching = true;
	  //Set the sorting direction to ascending:
	  dir = "asc";
	  /*Make a loop that will continue until
	  no switching has been done:*/
	  while (switching) {
		//start by saying: no switching is done:
		switching = false;
		rows = table.getElementsByTagName("TR");
		/*Loop through all table rows (except the
		first, which contains table headers):*/
		for (i = 1; i < (rows.length - 1); i++) {
		  //start by saying there should be no switching:
		  shouldSwitch = false;
		  /*Get the two elements you want to compare,
		  one from current row and one from the next:*/
		  x = rows[i].getElementsByTagName("TD")[n];
		//console.log("x = " + x.innerText);
		  y = rows[i + 1].getElementsByTagName("TD")[n];

		
		  //if the user sorts date fields
		  if (type == "date") {
			  var xx =(x.innerHTML).split('-');
			  var xxx = "";
			  var yy =(y.innerHTML).split('-');
			  var yyy = "";
			 if (xx != "") {var xxx = new Date(xx[2],xx[1],xx[0]-1); }// YY, mm, dd
			 if (yy != "") {var yyy = new Date(yy[2],yy[1],yy[0]-1); } 
			   if (dir == "asc") {
				if (xxx > yyy) {
				  //if so, mark as a switch and break the loop:
				  shouldSwitch= true;
				  //alert ("x=" + xx + " and y =" +yy);
				  break;
				}
			  } else if (dir == "desc") {
				if (xxx < yyy) {
				  //if so, mark as a switch and break the loop:
				  shouldSwitch= true;
				  break;
				}
			  }
		  }
		  else if (type == "dec") {
			//Zuerst ersetzen sie leer mit null    -->  0
			var xx = (x.innerHTML).replace('', '0');
			var yy = (y.innerHTML).replace('', '0');

			//Weiter ersetzen sie der Punkt mit leer  . --> 
			var xxxx = xx.replace(/\./g, '');
			var yyyy = yy.replace(/\./g, '');
			
			//Letzte ersetzen sie das Koma mit dem Punkt  , --> .
			var xxx = parseFloat(xxxx.replace(/,/g, '.'));
			var yyy = parseFloat(yyyy.replace(/,/g, '.')); 
			
			 if (dir == "asc") {
				if (xxx > yyy) {
				  //if so, mark as a switch and break the loop:
				  shouldSwitch= true;
				  //alert ("x=" + xx + " and y =" +yy);
				  break;
				}
			  } else if (dir == "desc") {
				if (xxx < yyy) {
				  //if so, mark as a switch and break the loop:
				  shouldSwitch= true;
				  break;
				}
			  }
		  }
		  else {
			   /*check if the two rows should switch place,
				based on the direction, asc or desc:*/
			  if (dir == "asc") {
				if (x.innerText - y.innerText > 0) {
				  //if so, mark as a switch and break the loop:
				  shouldSwitch= true;
				  break;
				}
			  } else if (dir == "desc") {
				if (x.innerText - y.innerText < 0) {
				  //if so, mark as a switch and break the loop:
				  shouldSwitch= true;
				  break;
				}
			  }
		  }
		}
		if (shouldSwitch) {
		  /*If a switch has been marked, make the switch
		  and mark that a switch has been done:*/
		  rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
		  switching = true;
		  //Each time a switch is done, increase this count by 1:
		  switchcount ++;
		} else {
		  /*If no switching has been done AND the direction is "asc",
		  set the direction to "desc" and run the while loop again.*/
		  if (switchcount == 0 && dir == "asc") {
			dir = "desc";
			switching = true;
		  }
		}
	  }
		//Βάλε το εικονίδιο της ταξινόμησης
		var headers_length = table.getElementsByTagName("TH").length;
		//var headers_length = table.getElementsByClassName("rTableHead").length;
		
		for (header = 1; header < headers_length ; header++) {
			var rheader = table.getElementsByTagName("TH")[header];
			if (dir == "asc") {
				if (header == n) {
					if ((rheader.innerHTML).substr(-4, 4) != "</i>")
						rheader.innerHTML  = rheader.innerHTML  + '<i class="icon-arrow-down2"></i>';
				else
						rheader.innerHTML  = (rheader.innerHTML).substr(0, (rheader.innerHTML).indexOf('<')) + '<i class="icon-arrow-down2"></i>';
				}
				else {
					if ((rheader.innerHTML).substr(-4, 4) === "</i>")
						rheader.innerHTML  = (rheader.innerHTML).substr(0, (rheader.innerHTML).indexOf('<'));
				}
			}
			else if (dir == "desc") {
				if (header == n) {
					if ((rheader.innerHTML).substr(-4, 4) != "</i>")
						rheader.innerHTML  = rheader.innerHTML  + '<i class="icon-arrow-up2"></i>';
					else
						rheader.innerHTML  = (rheader.innerHTML).substr(0, (rheader.innerHTML).indexOf('<')) + '<i class="icon-arrow-up2"></i>';
				}
				else {
					if ((rheader.innerHTML).substr(-4, 4) === "</i>")
						rheader.innerHTML  = (rheader.innerHTML).substr(0, (rheader.innerHTML).indexOf('<'));
				}
			}
			//console.log("innerHTML is " + rheader.innerHTML);
		}
	}
	</script>

		
	<?php if ($user_grant_upload == true) : ?>
		<?php /*
		<div id="rns_pdf_files_wrapper" class="edit_elements" style="display:none;float:left">
			<input id="eggrafo" type="file" accept="application/pdf" multiple="multiple">
			<div id="rns_pdf_files_drop_area">
				<h3 class="rns_pdf_files_drop_text">Σύρετε και αφήστε εδώ τα έγγραφα</h3>
			</div>
		</div>
		*/ ?>
		<div id="rns_pdf_files_wrapper" class="edit_elements" style="display:none;">
			<div style="display:none;">
				<input id="eggrafo" type="file" accept=".xlsx, .xlsb, .xls, .doc, .docx, .ppt, .pptx, .txt, .pdf, .zip, .png, .jpg, .mp4, .msg" multiple="multiple">
			</div>
			<div id="rns_pdf_files_drop_area">
				<div class="rns_pdf_files_drop_inner_area">
					<div class="rns_pdf_files_drop_text">
						<i class="fa-solid fa-4x fa-file-arrow-up"></i>
						<h4>Επιλέξτε αρχεία ή σύρετέ τα εδώ</h4>
					</div>
				</div>
			</div>
		</div>
		<input type="hidden" id="menu_catid" name="menu_catid" class="inputbox" value="<?php echo $menu_catid; ?>">
		<input type="hidden" id="pdfuploadfolder" name="pdfuploadfolder" class="inputbox" value="<?php echo $SelectedUploadFolder; ?>" placeholder="Ο φάκελος που θα ανέβουν τα έγγραφα">
	<?php endif; ?>
	<div class="clearfix"></div>
	<div class="row-fluid">
		<div class="eggrafa_table" style="padding: 0 10px 0px 0;">
			<table id="table_arxeia" class='table table-striped table-hover'>
				<thead>
					<tr>
						<th style="display:none;"></th>
						
						<th onclick="sortTable('table_arxeia',   1)">Όνομα Εγγράφου</th>
						<th onclick="sortInteger('table_arxeia', 2,'date')">Ημερομηνία εγγράφου</th>
						
						<?php //have to change javascript code in order for sorting tooltip to work
							/*
						<th>
							<span onclick="sortTable('table_arxeia',   1)" data-content="<?php echo JText::_('JGLOBAL_CLICK_TO_SORT_THIS_COLUMN');?>" data-placement="top" data-toggle="popover" data-trigger="hover" title="Όνομα Εγγράφου" class="hasPopover">Όνομα Εγγράφου
							</span>
						<th>
							<span onclick="sortInteger('table_arxeia', 2,'date')" data-content="<?php echo JText::_('JGLOBAL_CLICK_TO_SORT_THIS_COLUMN');?>" data-placement="top" data-toggle="popover" data-trigger="hover" title="Ημερομηνία εγγράφου" class="hasPopover">Ημερομηνία εγγράφου
							</span>
						</th>
						*/ ?>
						
						<th>Μέγεθος Αρχείου</th>  
						<?php if ($user_grant_upload) :  ?>
							<th class="edit_elements" style="display:none;">Επεξ.</th>
							<th class="edit_elements" style="display:none;">Διαγρ.</th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
	<?php 
	
		// find the last file
		$reallyLastModified = end($files);
		
		// find the last modified date
		$reallyLas_d = date("d-m-Y", filemtime($reallyLastModified));
		$reallyLastModified_d = DateTime::createFromFormat('d-m-Y', $reallyLas_d);
		//echo "LAST " . $reallyLastModified_d->format("d-m-Y");

		// Loops through the array of files	in reverse order
		for($index = $indexCount -1; $index >= 0; $index--) {
			
			// Gets File Name
			$filename = basename($files[$index]);
			$file_icon = '';

			//get file type
			$file_type = substr($filename, -3);
			$file_type_v4 = substr($filename, -4);
			if ($file_type == "pdf" || $file_type == "PDF")
				$file_icon = '<i class="far fa-w-14 fa-file-pdf" style="color: #d12020; font-size: 22px;"></i>';
			elseif ($file_type == "doc" || $file_type_v4 == "docx" ||
					$file_type == "DOC" || $file_type_v4 == "DOCX")
				$file_icon = '<i class="far fa-w-14 fa-file-word" style="color: #2e53d7; font-size:22px;"></i>';
			elseif ($file_type == "xls" || $file_type == "XLS" ||
					$file_type_v4 == "xlsx" || $file_type_v4 == "XLSX" ||
					$file_type_v4 == "xlsb" || $file_type_v4 == "XLSB")
				$file_icon = '<i class="far fa-w-14 fa-file-excel" style="color: #1b9f43; font-size:22px;"></i>';
			elseif ($file_type == "zip" || $file_type_v4 == "zipx" ||
					$file_type == "ZIP" || $file_type_v4 == "ZIPX")
				$file_icon = '<i class="fas fa-w-14 fa-file-archive" style="color: #d7bf18; font-size:22px;"></i>';
			elseif ($file_type == "mp4" || 	$file_type == "MP4")
				$file_icon = '<i class="fas fa-w-14 fa-file-video" style="color: #000; font-size:22px;"></i>';
			elseif ($file_type == "ppt" || $file_type_v4 == "pptx" ||
					$file_type == "PPT" || $file_type_v4 == "PPTX")
				$file_icon = '<i class="fas fa-w-14 fa-file-powerpoint" style="color: #ff7626; font-size:22px;"></i>';
			elseif ($file_type == "msg" || $file_type == "MSG")
				$file_icon = '<i class="fas fa-w-14 fa-envelope-square" style="color: #a4afa4; font-size:22px;"></i>';
			elseif ($file_type == "png" || $file_type == "PNG")
				$file_icon = '<i class="fas fa-w-14 fa-file-image" style="color: #359716; font-size:22px;"></i>';	
			//error_log("filename: " . $filename . " and index: " . $files[$index]);
			$filepath = $fpath .DIRECTORY_SEPARATOR . $efu_parent . DIRECTORY_SEPARATOR . $filename . '?t=' .  time();	
			//$ff = $files[$index];
			
			// Gets Date Modified
			$moddd = date("d-m-Y", filemtime($files[$index]));
			$modtime_d = DateTime::createFromFormat('d-m-Y', $moddd);
			$modtime = $modtime_d->format("d-m-Y");
			//error_log("| date: " . $modtime);

			// Separates directories, and performs operations on those directories
			if(is_dir($files[$index])) {
				$size="&lt;Directory&gt;";
				$filelink = "";
			}
			// File-only operations
			else {
				 $size = modRnsUploadHelper::formatSizeUnits(filesize($files[$index]));
				if ($size != "4096") {
					$filelink = $file_icon . '<a href="'. $filepath . 
										'" target="blank_" download="' . $filename . '"> ' . $filename . '</a> ';
					$filelink_withsize = $file_icon . '<a href="'. $filepath . 
										'" target="blank_">' . $filename . ' (' . $size . ')</a> ';
										
					$filelink_first = $file_icon . '<a href="'. $filepath . 
										'" target="blank_">' . $filename . '</a> ';					
				}
				else {
					$filelink = "";
				}
			}
			
			if (($filelink != "") && (date_diff($current_date,$modtime_d)->format('%R%a days') < $days) && ($files_counter < $numfilestodisplay)) { // σύγκριση με πιο πρόσφατη ημερομηνία και μετρητή
			?>
				<tr id="<?php echo "arxeio_" . $files_counter; ?>" class="cool">
					<td style="display:none;"></td>
					<td class="filename"><?php echo $filelink; ?></td>
					<td><?php echo $modtime ; ?></td>
					<td><?php echo $size; ?></td>
					<?php if ($user_grant_upload) :  ?>
						<td class="edit_elements" style="display:none;">
							<span onclick="rename_file('<?php echo "arxeio_" . $files_counter . "','" . $SelectedUploadFolder . "','" . $filename; ?>')" style="clear:both; padding-left: 2px;" class="edit_button">
								<i class="fas fa-2x fa-edit"></i>
							</span>
						</td>
						<td class="edit_elements" style="display:none;">
							<span onclick="delete_file('<?php echo "arxeio_" . $files_counter . "','" . $SelectedUploadFolder . "','" . $filename; ?>')" style="clear:both; padding-left: 2px;" class="close_button">
								<i class="fa fa-2x fa-times"></i>
							</span>
						</td>	
						<?php endif; ?>
				</tr>
			<?php 
			$files_counter++;
			}
		}
	?>
			</tbody>
		</table>
		<br>
	</div>
	<?php else : //didn't find any files ?>
	<script>
		function sortInteger(element, n, type) {
			//don't do anything
		}
	</script>
	<?php if ($user_grant_upload == true) : ?>
		<?php
		/* <div id="rns_pdf_files_wrapper" class="edit_elements" style="display:none;float:left">
			<input id="eggrafo" type="file" accept="application/pdf" multiple="multiple">
			<div id="rns_pdf_files_drop_area">
				<h3 class="rns_pdf_files_drop_text">Σύρετε και αφήστε εδώ τα έγγραφα</h3>
			</div>
		</div>
		*/ ?>
		
		<div id="rns_pdf_files_wrapper" class="edit_elements" style="display:none;">
			<div style="display:none;">
				<input id="eggrafo" type="file" accept="application/pdf" multiple="multiple">
			</div>
			<div id="rns_pdf_files_drop_area">
				<div class="rns_pdf_files_drop_inner_area">
					<div class="rns_pdf_files_drop_text">
						<i class="fa-solid fa-4x fa-file-arrow-up"></i>
						<h4>Επιλέξτε αρχεία ή σύρετέ τα εδώ</h4>
					</div>
				</div>
			</div>
		</div>		
		
		<input type="hidden" id="pdfuploadfolder" name="pdfuploadfolder" class="inputbox" value="<?php echo $SelectedUploadFolder; ?>" placeholder="Ο φάκελος που θα ανέβουν τα έγγραφα">
	<?php endif; ?>
	<div class="clearfix"></div>
	<div class="row-fluid">
		<div class="eggrafa_table" style="padding: 0 10px 0px 0;">
			<table id="table_arxeia" class='table table-striped table-hover'>
			</table>
		</div>
	</div>
	<?php endif; ?>
</div>