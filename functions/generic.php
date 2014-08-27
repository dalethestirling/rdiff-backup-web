<?

###################################################################
#
# rdiff-backup-web
#
# Created by: David Evans (goodevans@gmail.com)
#
# (c) 2007 Dale Stirling (rdiffbackupweb@puredistortion.com)
#
# Useless without rdiff-backup created by Ben Escoto
#
# rdiff-backup-web is a web-based interface for rdiff-backup
# designed to greatly simplify backup administration and restoring.
#
# rdiff-backup-web is free software and is released under the 
# GNU General Public Licence. See licence.txt for details.
# 
###################################################################

// The default function. Gets users to type in their username and password.

function login($message=""){

	$output = "<h1>Backup and Restore System</h1>";
	if($message){$output.= "<p>$message</p>";}
	$output.= "<h2>Login</h2>";
	$output.= "<form action=\"./\" method=post>";
	$output.= "<input type=hidden name=op value=\"process_login\">";
	$output.= "<p> Username: <input type=text name=username><br \><br \>";
	$output.= "Password: <input type=password name=userpass></p>";
	$output.= "<input type=submit></form>";

	echo $output;

}

/* 

Make the login process more secure. DONE 2004-07-29

Login now uses a cookie to make sure you are who you say you are.

*/

function process_login(){

	include("./config.php");

	if($_POST['op'] == "process_login"){

		$output = "<h2>Login Successful</h2>";
		$output.= "<p>Click <a href=./?op=logged_in>here</a> to continue.</p>";
		echo $output;
	
	} else {

		$output = "<h2>Logout</h2>";
		$output.= "<p>Click <a href=./>here</a> to continue.</p>";
		echo $output;

	}

}

function logged_in(){
	
	include("./config.php");

	if(check_login()){

		$output = "Choose backup:";
		$output.="<ul class=\"vert-two\">";

		$query = "select backupsetup.id, backupsetup.path_to_file from backupsetup inner join access on backupsetup.id=access.backupsetupid where access.usersid=".$_COOKIE['userid'];

		$result = run_query($query);
		
		while($row = mysql_fetch_assoc($result)){

			extract($row);

			$output.="<li><a href=\"./?op=browse_files&userid=".$_COOKIE['user_id']."&backupid=".$id."\">".$path_to_file."</a></li>";
		}
		$output.="</ul>";	
		echo $output;

		mysql_free_result($result);

	}

}

/*

Prettify the browse_files function. Proper collapsable tree structure, make sure that it handles full directories gracefully: DONE: 2005-02-18 

The list now shows all directories only, until you click on one. then it will list the files in that directory. Clicking on the - beside the directory will collapse the dir, clicking on the dir name will 
the entire dir (including subdirs), and clicking on one of the file names will prepare that file only.

*/

function browse_files(){

	include("./config.php");

	if($_POST['browse_at']){$browseat=$_POST['browse_at'];} elseif($_GET['browse_at']){$browseat=$_GET['browse_at'];} else {$browseat="now";}
	if($_GET['backupid']){$backup_id = $_GET['backupid']; } elseif($_POST['backupid']){$backup_id = $_POST['backupid'];}

	
	echo "<p><a href=./?op=logged_in>Return to top</a></p>";
	echo "<p>View files as of: <form action=./ method=post>";
	echo "<input type=hidden name=op value=browse_files>";
	echo "<input type=hidden name=backupid value=$backup_id>";
	echo "<select name=browse_at>";
	echo "<option value=\"now\"";
	if($browseat == "now"){ echo " selected";}
	echo ">Today</option>";
	echo "<option value=\"1D\"";
	if($browseat == "1D"){ echo " selected";}
	echo ">Yesterday</option>";
	echo "<option value=\"2D\"";
	if($browseat == "2D"){ echo " selected";}
	echo ">2 Days Ago</option>";
	echo "<option value=\"3D\"";
	if($browseat == "3D"){ echo " selected";}
	echo ">3 Days Ago</option>";
	echo "<option value=\"4D\"";
	if($browseat == "4D"){ echo " selected";}
	echo ">4 Days Ago</option>";
	echo "<option value=\"1W\"";
	if($browseat == "1W"){ echo " selected";}
	echo ">1 Week Ago</option>";
	echo "</select></p>";
	echo "<input type=submit value=\"Refresh\"></form>";

    $query = "select path_to_backup from backupsetup where id=$backup_id";

    $result = run_query($query);

    while($row = mysql_fetch_assoc($result)){

		extract($row);

	}

	if(substr($path_to_backup,0,1) == "/"){$backup_location=$path_to_backup; } else {$backup_location=$BACKUP_STORE_DIR.$path_to_backup; }
	$command = "$RDIFF_BACKUP --list-at-time '$browseat' $backup_location 2>&1"; 
	
	exec ($command, $rdiff_backup_list);

	echo $rdiff_backup_list[0];

	
	$output .= "<table border=0 cellpadding=0 cellspacing=0 width=100%>\n";

	for($i=0; $i<count($rdiff_backup_list); $i++){

		$this_line_count = count(split("/", $rdiff_backup_list[$i]));
		if($this_line_count>$max_levels){$max_levels = $this_line_count; }

	}

	$output.="<tr><td width=17px><img src=./images/folder.gif border=0></td>";

	for($i=2; $i<$max_levels; $i++){

		$output.="<td width=17px>&nbsp;</td>";

	}

	$output.="<td width=99%>&nbsp;</td></tr>";

	for($i=0; $i<count($rdiff_backup_list); $i++){

		if(substr($rdiff_backup_list[$i],0,1) == "."){$hidden_file=1;} else {$hidden_file = 0; } // Hidden file.
	
		$level = count(split("/", $rdiff_backup_list[$i])); // number of levels in this line

		$level_spaces="";

		if($level>=2){

			for($j = 2; $j<=$level; $j++){

				$level_spaces.="<td width=17px>&nbsp;</td>";

			}

		}

		$remaining_cols = $max_levels-$level;
		$remaining_cols_short=$remaining_cols-1;
	
		$last_slash = strrpos($rdiff_backup_list[$i], "/"); // position of the last slash on this line

		$this_dir = substr($rdiff_backup_list[$i], 0, $last_slash); // the directory that this file is in
	
		$next_line = $i+1;

		$last_slash_next_line = strrpos($rdiff_backup_list[$next_line], "/"); // position of the last slash in the next line

		if(substr($rdiff_backup_list[$next_line], 0, $last_slash_next_line) == $rdiff_backup_list[$i]){$is_dir = 1;} else {$is_dir = 0; }
		
		if($level == 1){

			$filename = substr($rdiff_backup_list[$i],$last_slash,strlen($rdiff_backup_list[$i]));

		} else {

			$filename = substr($rdiff_backup_list[$i],$last_slash+1,strlen($rdiff_backup_list[$i]));

		}
		
		if($hidden_file != 1){

			if($is_dir){

				if($_GET['browse_dir'] == $rdiff_backup_list[$i]){

					if(strlen($this_dir)>0){
					
						$output.= "<tr id=$i><td width=17px><img src=./images/vline.gif border=0></td>".substr($level_spaces,26,strlen($level_spaces))."<td valign=middle width=17px><a href=\"./?op=browse_files&browse_at=$browseat&backupid=$backup_id#$i\"><img src=./images/dash.gif border=0></a></td><td colspan=$remaining_cols><a href=\"./?op=prepare_backup&browse_at=$browseat&backupid=$backup_id&link_to_file=$rdiff_backup_list[$i]\"><img src=./images/folder.gif border=0>$filename</a></td></tr>\n";

					} else {

						$output.= "<tr id=$i>$level_spaces<td valign=middle width=17px><a href=\"./?op=browse_files&browse_at=$browseat&backupid=$backup_id#$i\"><img src=./images/dash.gif border=0></a></td><td colspan=$remaining_cols><a href=\"./?op=prepare_backup&browse_at=$browseat&backupid=$backup_id&link_to_file=$rdiff_backup_list[$i]\"><img src=./images/folder.gif border=0>$filename</a></td></tr>\n";

					}
					
				} else {
		
					if(strlen($this_dir)>0){
					
						$output.= "<tr id=$i><td width=17px><img src=./images/vline.gif border=0></td>".substr($level_spaces,26,strlen($level_spaces))."<td valign=middle width=17px><a href=\"./?op=browse_files&browse_at=$browseat&backupid=$backup_id&browse_dir=$rdiff_backup_list[$i]#$i\"><img src=./images/plus.gif border=0></a></td><td colspan=$remaining_cols><a href=\"./?op=browse_files&browse_at=$browseat&backupid=$backup_id&browse_dir=$rdiff_backup_list[$i]#$i\"><img src=./images/folder.gif border=0>$filename</a></td></tr>\n";

					} else {

						$output.= "<tr id=$i>$level_spaces<td valign=middle width=17px><a href=\"./?op=browse_files&browse_at=$browseat&backupid=$backup_id&browse_dir=$rdiff_backup_list[$i]#$i\"><img src=./images/plus.gif border=0></a></td><td colspan=$remaining_cols><a href=\"./?op=browse_files&browse_at=$browseat&backupid=$backup_id&browse_dir=$rdiff_backup_list[$i]#$i\"><img src=./images/folder.gif border=0>$filename</a></td></tr>\n";

					}

				}

			} elseif($this_dir == $_GET['browse_dir'] && strlen($this_dir)>0) {

				$output.="<tr><td width=17px><img src=./images/vline.gif border=0></td>".substr($level_spaces,26,strlen($level_spaces))."<td valign=middle width=17px><img src=./images/file.gif border=0></td><td colspan=$remaining_cols><a href=\"./?op=prepare_backup&backupid=$backup_id&browse_at=$browseat&link_to_file=$rdiff_backup_list[$i]\">$filename</a></td></tr>\n";

			} elseif($this_dir == $_GET['browse_dir'] && strlen($this_dir)==0) {

				$output.="<tr>$level_spaces<td valign=middle width=17px><img src=./images/file.gif border=0></td><td colspan=$remaining_cols><a href=\"./?op=prepare_backup&backupid=$backup_id&browse_at=$browseat&link_to_file=$rdiff_backup_list[$i]\">$filename</a></td></tr>\n";

			}

		}
		
	}

	$output.="</table>";
	echo $output;

}

/* when a link is clicked in the browse_files function, this function copies the file to be restored, as of the required date, into a temporary storage location where it can simply be clicked on and downloaded.

At the moment, the file remains there until the cron job is run which empties the folder.

TODO: Redo prepare_backup so that when you click on the link, it opens a new page which then starts the download, like download.com et al. This might let us gracefully remove the file once download is complete.

*/

function prepare_backup(){

	include("./config.php");

    $filename = split("/", $_GET['link_to_file']);

    $filename_in_array = sizeof($filename);
    $filename_in_array --;

    $query = "select id from access where backupsetupid=".$_GET['backupid']." and usersid=".$_COOKIE['userid'];

    $result = run_query($query);

    while($row = mysql_fetch_assoc($result)){

		extract($row);

		$query2 = "select * from backupsetup where id=".$_GET['backupid'];

		$result2 = run_query($query2);

		while($row2 = mysql_fetch_assoc($result2)){

			extract($row2);

		}

		if(substr($path_to_backup,0,1) == "/"){$backup_location = $path_to_backup; } else {$backup_location=$BACKUP_STORE_DIR.$path_to_backup;}

		$restore_command = "$SUDO $RDIFF_BACKUP --restore-as-of '".$_GET['browse_at']."' $backup_location".$_GET['path_to_backup']."/".str_replace(" ", "\ ", $_GET['link_to_file'])." $DOWNLOAD_LOCATION".str_replace(" ", "\ ", $filename[$filename_in_array]);
		system($restore_command);
		
		echo "<p><a href=./?op=logged_in>Return to top</a></p>";
		echo "<h1>Backup File Download</h1><h2>File Details</h2><p>Original file: ".$_GET['link_to_file']."<br>File age: ".$_GET['browse_at']."</p>";
		echo "<p><a href=\"./tmp/".str_replace(" ", "%20", $filename[$filename_in_array])."\">Download file here</a></p>";
		
	}


}

/* The admin section allows authorised users to set up backups, disable them, and to attach users willy nilly.

When redoing the security, put in proper links to the admin functions DONE 29/7/04 
have to change everything to allow other methods of connection. Maybe another table, which lists backup id, switch name, switch value. Will also have to remove the hardcoding to the samba mounting. DONE:2005-02-18

You can now use backups over SSH, which also handle local backups. But you need to follow the instructions for unattended backups on the rdiff-backup page first!

*/

function admin(){

	include('./config.php');

	$output = "<h1>Backup Administration</h1>";
	$output.= "<p><a href=./?op=add_user>Administer Users</a></p>";
	$output.= "<h2>Current Backups</h2>";
	$output.= "<table border=1 cellpadding=0 cellspacing=0 width=100%>";
	$output.= "<tr>";
	$output.= "	<td>ID</td>";
	$output.= "	<td>Sequence</td>";
	$output.= "	<td>Path to Orig Files</td>";
	$output.= "	<td>Path to Backup</td>";
	$output.= "	<td>Duration</td>";
	$output.= "     <td>Connection Type</td>";
	$output.= "	<td>Active</td>";
	$output.= "	<td>Access</td>";
	$output.= "     <td>Delete</td>";
	$output.= "</tr>";

	$query = "select * from backupsetup order by sequence";

	$result = run_query($query);

	while($row = mysql_fetch_assoc($result)){

		extract($row);

		$output.= "<tr>";
		$output.= "<td><a href=\"./?op=edit_backup&backupid=$id\">$id</a></td>";
		$output.= "<td>$sequence</td>";
		$output.= "<td>$path_to_file</td>";
		$output.= "<td>$path_to_backup</td>";
		$output.= "<td>$keep_for</td>";
		$output.= "<td>$connect_type</td>";
		$output.= "<td><a href=\"./?op=change_active_status&id=$id&active=$active\">$active</a></td>";
		$output.= "<td><a href=\"./?op=setup_access&id=$id\">Users</a></td>";
		$output.= "<td><a href=\"./?op=process_delete_backup&backupid=$id\">Delete";
		$output.= "</tr>";

	}

	$sequence++;

	$output.= "</table>";
	$output.= "<br />";
	$output.= "<form action=./ method=post><input type=hidden name=op value=process_add_backup>";
	$output.= "<table width=100% border=0 cellpadding=0 cellspacing=0>";
	$output.= "<tr><td width=45% align=right>Sequence No.</td>    <td width=10%>&nbsp;</td>      <td width=45%><input type=text name=sequence value=".$sequence."></td></tr>";
	$output.= "<tr><td align=right>Path to Original Files</td>    <td>&nbsp;</td>                <td><input type=text name=path_to_file></td></tr>";
	$output.= "<tr><td align=right>Path to backup</td>            <td>&nbsp;</td>                <td><input type=text name=path_to_backup></td></tr>";
	$output.= "<tr><td align=right>--include String(just the dirs, separated by ;'s)</td>          <td>&nbsp;</td>                <td><input type=text name=includes></td></tr>";
	$output.= "<tr><td align=right>--exclude String(just the dirs, separated by ;'s)</td>          <td>&nbsp;</td>                <td><input type=text name=excludes></td></tr>";
	$output.= "<tr><td align=right>Time to keep for</td>          <td>&nbsp;</td>                <td><input type=text name=keep_for></td></tr>";
	$output.= "<tr><td align=right>Connection Type</td>	      <td>&nbsp;</td>		     <td><select name=connect_type><option>SSH</option><option>Samba</option><option>NFS</option></select></td></tr>";
	$output.= "<tr><td align=right>Mount Options</td>             <td>&nbsp;</td>                <td><input type=text name=mountoptions></td></tr>";
	$output.= "<tr><td align=right>Active</td>                    <td>&nbsp;</td>                <td><input type=text name=active value=1></td></tr>";
	$output.= "<tr><td align=center colspan=3><input type=submit value=Submit></td></tr>";
	$output.= "</table></form>";


	echo $output;

}

// quick on-off switch for backups

function change_active_status(){

	include('./config.php');

	$query = "update backupsetup set active=";

	if($_GET['active'] == "1"){$query.="0";} else {$query.="1";}

	$query .= " where id={$_GET['id']}";

	$result = run_query($query);

	admin();

}

// processes the form on the admin page to add a backup to the database.

function process_add_backup(){
	
	include('./config.php');
	
	$include_string = "";
	if($_POST['includes']){
		
		$include_paths = split(";", $_POST['includes']);
		$no_of_includes = sizeof($include_paths);

		for($i=0; $i<$no_of_includes; $i++){

			$include_string.= " --include ";
			if($_POST['connect_type']=="SAMBA"){ $include_string.= $MOUNT_TEMP_DIR; }
			$include_string.= $include_paths[$i];

		}

	}

	$exclude_string = "";
	if($_POST['excludes']){

		if($_POST['excludes'] == "**"){

			$exclude_string = "--exclude \'**\'";

		} else {

			$exclude_paths = split(";", $_POST['excludes']);
			$no_of_excludes = sizeof($exclude_paths);
	
			for($i=0; $i<$no_of_excludes; $i++){

				$exclude_string = " --exclude ";
				if($_POST['connect_type']=="SAMBA"){ $exclude_string.= $MOUNT_TEMP_DIR; }
				$exclude_string.= $exclude_paths[$i];

			}

		}

	}

	$query = "insert into backupsetup values('', ".$_POST['sequence'].", '".$_POST['path_to_file']."', '".$_POST['path_to_backup']."', '".$include_string."', '".$exclude_string."', '".$_POST['keep_for']."', '".$_POST['connect_type']."', '".$_POST['mountoptions']."', '".$_POST['active']."')";

	$result = run_query($query);

	mkdir_p($BACKUP_STORE_DIR.$_POST['path_to_backup']);	
	
	admin();

}

/* Deletes a backup from the database

TODO: add an "Are you sure" question to process_delete_backup. Javascript maybe?

*/

function process_delete_backup(){

	$query = "delete from backupsetup where id=".$_GET['backupid'];

	$result = run_query($query);

	admin();

}

// calls out one of the backup entries into a form and allows you to edit the details.

function edit_backup(){

        $output = "<h1>Edit Backup ".$_GET['backupid']."</h1>";

	$query = "select * from backupsetup where id=".$_GET['backupid'];

	$result = run_query($query);

	while($row = mysql_fetch_array($result)){
	
		extract($row);

		if($connect_type=="Samba"){

			$options = "<option>SSH</option><option>NFS</option><option selected>Samba</option";

		} elseif($connect_type=="SSH"){

			$options = "<option selected>SSH</option><option>NFS</option><option>Samba</option>";

		} else {
			$options = "<option selected>NFS</option><option>Samba</option><option>SSH</option>";
		}
	
	        $output.= "<form action=./ method=post><input type=hidden name=op value=process_edit_backup>";
	        $output.= "<table width=100% border=0 cellpadding=0 cellspacing=0>";
		$output.= "<tr><td align=right>Backup ID</td>                 <td>&nbsp;</td>                <td><input type=hidden name=backup_id value=".$_GET['backupid'].">".$_GET['backupid']."</td></tr>";
	        $output.= "<tr><td width=45% align=right>Sequence No.</td>    <td width=10%>&nbsp;</td>      <td width=45%><input type=text name=sequence value=".$sequence."></td></tr>";
	        $output.= "<tr><td align=right>Path to Original Files</td>    <td>&nbsp;</td>                <td><input type=text name=path_to_file value=\"$path_to_file\"></td></tr>";
	        $output.= "<tr><td align=right>Path to backup</td>            <td>&nbsp;</td>                <td><input type=text name=path_to_backup value=\"$path_to_backup\"></td></tr>";
	        $output.= "<tr><td align=right>--include String(just the dirs, separated by ;'s)</td>          <td>&nbsp;</td>                <td><input type=text name=includes value=\"$includes\"></td></tr>";
	        $output.= "<tr><td align=right>--exclude String(just the dirs, separated by ;'s)</td>          <td>&nbsp;</td>                <td><input type=text name=excludes value=\"$exception\"></td></tr>";
	        $output.= "<tr><td align=right>Time to keep for</td>          <td>&nbsp;</td>                <td><input type=text name=keep_for value=\"$keep_for\"></td></tr>";
		$output.= "<tr><td align=right>Connection Type</td>	      <td>&nbsp;</td>		     <td><select name=connect_type>$options</select></td></tr>";
	        $output.= "<tr><td align=right>Mount Options</td>             <td>&nbsp;</td>                <td><input type=text name=mountoptions value=\"$mountoptions\"></td></tr>";
	        $output.= "<tr><td align=right>Active</td>                    <td>&nbsp;</td>                <td><input type=text name=active value=\"$active\"></td></tr>";
	        $output.= "<tr><td align=center colspan=3><input type=submit value=Submit></td></tr>";
	        $output.= "</table></form>";

	}

	echo $output;

}

// writes the changes back into the database from the previous function

function process_edit_backup(){

	$query = "update backupsetup set sequence=".$_POST['sequence'].", path_to_file='".$_POST['path_to_file']."', path_to_backup='".$_POST['path_to_backup']."', includes='".$_POST['includes']."', exception='".$_POST['excludes']."', keep_for='".$_POST['keep_for']."', connect_type='".$_POST['connect_type']."', mountoptions='".$_POST['mountoptions']."', active=".$_POST['active']." where id=".$_POST['backup_id'];

	$result = run_query($query);

	admin();

}

/* Lists users and lets you add new users and change their passwords

TODO: Allow deletion of users from add_user

*/

function add_user(){

	$output = "<p><a href=./?op=admin>Admin Home</a></p>";
	$output.= "<table width=100% border=1 cellpadding=0 cellspacing=0>";
	$output.= "<tr>";
	$output.= "<td>ID</td>";
	$output.= "<td>Name</td>";
	$output.= "<td>Email</td>";
	$output.= "<td>Username</td>";
	$output.= "<td>Password</td>";
	$output.= "<td>Admin</td>";
	$output.= "</tr>";

	$query = "select * from users order by id";

	$result = run_query($query);

	while($row = mysql_fetch_assoc($result)){

		extract($row);

		$output.= "<tr>";
		$output.= "<td>$id</td>";
		$output.= "<td>$name</td>";
		$output.= "<td>$email</td>";
		$output.= "<td>$username</td>";
		$output.= "<td><a href=./?op=chpass&userid=$id>Change Password</a></td>";
		$output.= "<td>$administrator</td>";
		$output.= "</tr>";

	}

	$output.= "</table>";
	$output.= "<br \>";

	$output.= "<form action=./ method=post><input type=hidden name=op value=process_add_user><table width=100% border=0 cellpadding=0 cellspacing=0>";
	$output.= "<tr><td align=right width=48%>Name</td>      <td width=4%>&nbsp</td>  <td width=48%><input type=text name=name></td></tr>";
	$output.= "<tr><td align=right>Email</td>               <td>&nbsp;</td>          <td><input type=text name=email></td></tr>";
	$output.= "<tr><td align=right>Username</td>            <td>&nbsp;</td>          <td><input type=text name=username></td></tr>";
	$output.= "<tr><td align=right>Password</td>            <td>&nbsp;</td>          <td><input type=password name=password></td></tr>";
	$output.= "<tr><td align=right>Administrator? (1 or 0)</td>	<td>&nbsp;</td>		<td><input type=text name=administrator></td></tr>";
	$output.= "<tr><td align=center colspan=3><input type=submit></td></tr>";
	$output.= "</table></form>";

	echo $output;

}

// writes the new user into the database from the form in the previous function

function process_add_user(){

	$query = "insert into users values('', '".$_POST['name']."', '".$_POST['email']."', '".$_POST['username']."', MD5('".$_POST['password']."'), '".$_POST['administrator']."')";

	$result = run_query($query);

	add_user();

}

/* Allows you to attach a user to a backup, allowing them access to it.

Currently calling setup_access for one backup lists all access for all backups. Filter it out. DONE 2005-02-18

Only loads up the table that you specify now.

*/

function setup_access(){

	$output = "<h1>Backup Access Setup</h1>";

	$query = "select id as backupsetuptableid, path_to_file from backupsetup where id={$_GET['id']}";

	$result = run_query($query);

	while($row = mysql_fetch_assoc($result)){

		extract($row);

		$output.= "<p>$backupsetuptableid: $path_to_file</p>";
		$output.= "<p><form method=post action=./><select name=userid>";
		
		$query3 = "select id as usertableid, name, username from users";
		$result3 = run_query($query3);
		while($row3 = mysql_fetch_assoc($result3)){
			
			extract($row3);
			
			$output.= "<option value=$usertableid>$name</option>";

		}

		$output.= "</select><input type=hidden name=op value=process_setup_access><input type=hidden name=backupid value=$backupsetuptableid><input type=submit value=\"Add User\"></form></p>";

		
		$output.= "<table border=1>";
		
		$query2 = "select users.*, access.id as access_id from users inner join access on users.id=access.usersid where access.backupsetupid=$backupsetuptableid";

		$result2 = run_query($query2);

		while($row2 = mysql_fetch_assoc($result2)){
			
			extract($row2);
			
			$output.= "<tr><td>$id</td><td>$username</td><td>$name</td><td><a href=\"./?op=process_delete_access&access_id=$access_id\">Delete</a></td></tr>";

		}

		$output.= "</table>";

	}

	echo $output;

}

// inserts the access from the form on the previous page

function process_setup_access(){
	
	$query = "insert into access values('', ".$_POST['backupid'].", ".$_POST['userid'].")";

	$result = run_query($query);
	
	# TODO: Function breaks page as variables not passed to function. Temp fix redirect to Admin menu.
	#setup_access();

	# Returns to Administration menu.
	admin(); 
}

/* Deletes the access from the form on the previous page

TODO: Add a javascript "Are you Sure?" before process_delete_access

*/

function process_delete_access(){

	$query = "delete from access where id = ".$_GET['access_id'];

	$result = run_query($query);

	# TODO: Function breaks page as variables not passed to function. Temp fix redirect to Admin menu.
	# setup_access();

	# Returns to Administration menu.
	admin(); 

}

// A function I used to carry out all my queries, just to cut down on repetition.

function run_query($query_string){

	include("./config.php");

	$connection = mysql_connect($config_dbhost, $config_dbuser, $config_dbpass) or die("Invalid server or user\n".mysql_error());

	mysql_select_db($config_db) or die("Invalid Database\n".mysql_error());

    $result = mysql_query($query_string) or die("Error in query: $query_string\n".mysql_error());

    return $result;

}

function check_login(){

	if(isset($_COOKIE['username'])){

		return 1;

	} else {

		return 0;

	}

}

// found this function in the comments to the php manual on www.php.net. Creates the required backup directory when creating a new backup

function mkdir_p($target){

	if (is_dir($target)||empty($target)) return 1; // best case check first
	if (file_exists($target) && !is_dir($target)) return 0;
	if (mkdir_p(substr($target,0,strrpos($target,'/')))) return mkdir($target, "0777"); // crawl back up & create dir tree
	return 0;
}

function chpass(){
	$output="<strong>Change Password</strong>";
	$output.="<br><br>";
	$output.="<form action=./ method=post><input type=hidden name=op value=process_chpass>";
	$output.="<input type=hidden name=userid value=" . $_GET['userid'] . ">";
	$output.="New Password:&nbsp;&nbsp;<input type=password name=new1password>";
	$output.="<br>";
	$output.="Confirm:&nbsp;&nbsp;<input type=password name=new2password>";
	$output.="<br>";
	$output.="<input type=submit>\n</form>";
	echo $output;
}

function process_chpass(){
	if ($_POST['new1password'] == $_POST['new2password']){
		$query = "update users set password=MD5('" . $_POST['new1password'] . "') where id='" . $_POST['userid'] . "'";
		run_query($query);
		add_user();
		
	} else {
		echo "<script>alert('Passwords do not match')</script>";
		chpass();
	}
}