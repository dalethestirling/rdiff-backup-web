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
include("functions/generic.php");

if($_POST['op']){ $op = $_POST['op']; }
if($_GET['op']){ $op = $_GET['op']; }


if($op == "process_login"){

	$invalid_login = "Username or Password is incorrect";
        $query = "select password, id, administrator from users where username='".$_POST['username']."'";

        $result = run_query($query);
	
       	while($row = mysql_fetch_assoc($result)){

               	extract($row);
		#echo $password."||";
		#echo md5($_POST['userpass'])."||";
               	if($password == md5($_POST['userpass'])){

                       	setcookie("username", $_POST['username']);
                       	setcookie("userid", $id);
                       	setcookie("admin", $administrator);
                       	$op = "process_login";

               	} else {

			$op = 'login';
			echo "<script>alert('" .$invalid_login . "')</script>";
		}

	}
}

if($op == "logout"){

        setcookie("username", "", time()-3600);
        setcookie("userid", "", time()-3600);
        setcookie("admin", "", time()-3600);
        $op="process_login";

}

?>


<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Rdiff-Backup-Web</title>
<link rel="stylesheet" type="text/css" href="main.css" />
</head>

<body>

   <!-- Begin Wrapper -->
   <div id="wrapper">
   
         <!-- Begin Header -->
         <div id="header">
		 
		       <h2 align=right>Rdiff-Backup-Web</h2>		 
			   
		 </div>
		 <!-- End Header -->
		 
         <!-- Begin Faux Columns -->
		 <div id="faux">
		 
		       <!-- Begin Left Column -->
		       <div id="leftcolumn">
		 
			<?
			// Admin menu options.
        		if(check_login()){
                		$output = "<ul class=\"vert-one\">\n
				<li><a href=\"./?op=logged_in\" title=\"Backups\">Backups</a></li>\n";
                	
				if($_COOKIE['admin'] == 1){
				$output.= "<li><a href=\"./?op=admin\" title=\"Administration\" >Administration</a></li>\n";
				}
			$output.= "<li><a href=\"./?op=logout\" title=\"Logout\">Logout</a></li>\n</ul>\n";
			echo $output;
			#echo $op;
			}
			?>


		       </div>
		       <!-- End Left Column -->
		 
		       <!-- Begin Right Column -->
		       <div id="rightcolumn">
		       
 			<?
                	// this little section sees what operation is being called and calls the appropriate function.

                	if($op) {
				#echo $op;
                        	$op();
				#logged_in();
                	} else {
                        	login();
                	}
                	?>
			<br />
                	<hr>
			<?
			// Admin menu options.
        		if(check_login()){

                		$output = "";

                	if($_COOKIE['admin'] == 1){

                        	$output.= "<p align=center><a href=./?op=admin>Administration</a></p>";

                }

                		$output.= "<p align=center><a href=./?op=logout>Logout</a></p>";
                echo $output;

        		}
			?>
			  
			 
		       </div>
		       <!-- End Right Column -->
			   
			   <div class="clear"></div>
			   
         </div>	   
         <!-- End Faux Columns --> 

         <!-- Begin Footer -->
         <div id="footer">
		<p align=center>
			<small>
				<a href="http://rdiffbackupweb.sourceforge.net">rdiff-backup-web</a> created by <a href="mailto:goodevans@gmail.com">David Evans</a>
	                	<br />
				<a href="http://www.nongnu.org/rdiff-backup/">rdiff-backup</a> created by <a href="mailto:bescoto@stanford.edu">Ben Escoto</a>
			</small>
		</p>
	
         </div>
	 <!-- End Footer -->
		 
   </div>
   <!-- End Wrapper -->
</body>
</html>


