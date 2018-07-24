<?php
	// show errors, start session
		$debugMode=0;  // set to 0 if you don't want debug messages
		if($debugMode) {
			ini_set('display_errors',1);
		}
		session_start();


	// variables
		$loggedIn = 0;
		$message='';
		$errorMessage='';
		$debugMessage='';
		$email = '';
		$username = '';
		$emailOrUsername = '';  # intended to be username if it exists, but email otherwise
		$pw = '';
		// get page from $_GET, default to intro page
			$page='intro';
			
			if(isset($_GET['page'])) {
				$page = $_GET['page'];
			}
		// get pageAfterLogin from $_SESSION, if applicable
			# haven't used these yet
			$pageAfterLogin='';
			
			if(isset($_SESSION['pageAfterLogin'])) {
				$pageAfterLogin = $_SESSION['pageAfterLogin'];
			}


	// functions
	function login($i,$e,$u) {
		global $debugMessage, $loggedIn, $email, $username, $emailOrUsername;
		$debugMessage .= '<br>login function';
		$loggedIn=1;
		$_SESSION['login']['id']=$i;
		$email=$e;
		$username=$u;
		if($u) {
			$emailOrUsername=$u;	
		} else {
			$emailOrUsername=$e;
		}
	}
	
	// connect to database
		$db = new PDO("mysql:host=localhost;dbname=lyrics", 'root', 'pass');
	
	// model
		// if logged in, get person info
			if(isset($_GET['logout']) and $_GET['logout']=='true' and isset($_SESSION['login'])) {
				$debugMessage .= '<br>Unsetting $_SESSION[login]';
				unset($_SESSION['login']);
			}
			if(isset($_SESSION['login']['id'])) {
				$debugMessage .= '<br>Try to get user info';
				$sql = $db->prepare('SELECT id, email, username FROM user WHERE id=?');
				$sql->execute(array($_SESSION['login']['id']));
				if($sql_result=$sql->fetch(PDO::FETCH_ASSOC)) {
					$debugMessage .= '<br>Got user info';
					login($sql_result['id'],$sql_result['email'],$sql_result['username']);
				}
			}
		
	
	// control loop
		$loop = true;
		$songRows = array();
		$mySongRows = array();
		$songTitle = '';
		$songArtist = '';
		$songLyrics = '';
		while($loop) {
			// controller -- if page=intro
				if($page=='intro') { 
					$loop=false;
					$debugMessage .= '<br>(control loop -- intro)';
				}
			// controller -- if page=about || contact || sitemap 
				elseif($page=='about' or $page=='contact' or $page=='sitemap') {
					$loop=false;
					$debugMessage .= "<br>(control loop -- {$page})";
				}
			// controller -- if page=songs
				elseif($page=='songs') {
					$loop=false;
					$debugMessage .= '<br>(control loop -- songs)';
					// get associative array from 'song' table in database
						$sql = $db->prepare("SELECT id, title, artist FROM song");
						$sql->execute();
						$songRows = $sql->fetchAll(PDO::FETCH_ASSOC);
				}
			// controller -- if page=lyrics
				elseif($page=='lyrics') {
					$loop=false;
					$debugMessage .= '<br>(control loop -- lyrics)';
					// redirect if no songID in $_GET array
						if(!isset($_GET['songID'])) {
							$debugMessage .= '<br>No songID, redirect to songs page';
							$page = 'songs';
							$loop=true;
							continue;  #restart loop
						}
					// otherwise retrieve lyrics for song
						// get lyrics of selected song from database
							$sql = $db->prepare("SELECT title, artist, lyrics from song where id=?");
							if($sql->execute(array($_GET['songID']))) {
								// check if there is a result (id is primary key, so there can only be 0 or 1 results)
								if($sql_result = $sql->fetch(PDO::FETCH_ASSOC)) {
									$songTitle = htmlspecialchars($sql_result['title']); # user-supplied, potential xss
									$songArtist = htmlspecialchars($sql_result['artist']); # user-supplied, potential xss
									$songLyrics = htmlspecialchars($sql_result['lyrics']); # user-supplied, potential xss
								} else {
									$errorMessage .= '<br>Song not found.';
									$debugMessage .= '<br>No matching song found. Redirect to songs page.';
									$loop = true;
									$page = 'songs';
								}
							} else {
								$errorMessage .= '<br>Search has encountered an error.';
								$debugMessage .= '<br>Query failed. Redirect to songs page.';
								$loop = true;
								$page = 'songs';
							}
				}
			// controller -- if page=search
				elseif($page=='search') {
					$loop=false;
					$debugMessage .= '<br>(control loop -- search)';
				} 
			// controller -- if page=signup
				elseif($page=='signup') {
					$loop=false;
					$debugMessage .= '<br>(control loop -- signup)';
					// if already logged in, go to account
						if($loggedIn) {
							$debugMessage .= '<br>Already logged in, redirect to account page';
							$page='account';
							$loop=true;
							continue; #restart loop
						}
					// if form has been submitted, attempt to sign up
						if(isset($_POST['signup_submit'])) {
							// validate email
								if(!isset($_POST['email'])) {
									$errorMessage .= '<br>Input error (email)';
								} elseif(strlen($_POST['email'])==0) {
									$errorMessage .= '<br>Please enter an email address.';
								} elseif(!filter_var($_POST['email'],FILTER_VALIDATE_EMAIL)) {
									$errorMessage .= '<br>Not a valid email address.';
								} else {
									$email=$_POST['email'];
								}
							// validate username
								if(!isset($_POST['username'])) {
									$errorMessage .= '<br>Input error (username)';
								} elseif(!preg_match("/^[a-zA-Z0-9_]*$/",$_POST['username'])) {
									$errorMessage .= '<br>Usernames can only use letters, numbers, and underscores.';
								}  else {
									$username=$_POST['username'];
								}
							// validate password
								if(!isset($_POST['pw']) or !isset($_POST['re_pw'])) {
									$errorMessage .= '<br>Input error (password)';
								} elseif($_POST['pw'] != $_POST['re_pw']) {
									$errorMessage .= '<br>Passwords do not match.';
								} else {
									$pw = $_POST['pw'];
								}
							// if no $errorMessage, sign person up
								if(!$errorMessage) {
									$debugMessage .= '<br>attempt to insert row in database';
									// check for uniqueness of email
										#
									// insert into database
										$sql = $db->prepare("INSERT INTO user (email, username, pw) VALUES (?,?,sha2(?,256));");
										if($sql->execute(array($email,$username,$pw))) {
											$message .= '<br>You have been signed up. You can now log in with the credentials you created.';
											// it worked, redirect to login page
												$loop=true;
												$page='login';
										} else {
											$errorMessage .= '<br>The system could not sign you up.';
										}
								}
						}
						
					
				}
			// controller -- if page=login
				elseif($page=='login') {
					$loop=false;
					$debugMessage .= '<br>(control loop -- login)';
					// if already logged in, go to account
						if($loggedIn) {
							$debugMessage .= '<br>Already logged in, redirect to account page';
							$page='account';
							$loop=true;
							continue; #restart loop
						}
					// if form has been submitted, attempt to log in
						if(isset($_POST['login_submit'])) {
								if(isset($_POST['emailOrUsername']) and isset($_POST['pw'])) {
									if($_POST['emailOrUsername']) {
										$emailOrUsername = $_POST['emailOrUsername'];
										$pw = $_POST['pw'];
										$sql = $db->prepare('SELECT id, email, username FROM user WHERE ? in(email,username) and sha2(?,256)=pw');
										if($sql->execute(array($emailOrUsername,$pw))) {
											if($sql_result=$sql->fetch(PDO::FETCH_ASSOC)) {
												login($sql_result['id'],$sql_result['email'],$sql_result['username']);
												$loop=true;
												$page='account';
											} else {
												$errorMessage .= '<br>Sorry, your credentials were not recognized';
											}
										} else {
											$errorMessage .= '<br>The system could not log you in.';
										}
									} else {
										$errorMessage .= '<br>Please enter a username or email address.';
									}
								} else {
									$errorMessage .= '<br>Input error';
								}
						}
				}
 			// controller -- if page=logout
				elseif($page=='logout') {
					$loop=false;
					$debugMessage .= '<br>(control loop -- logout)';
					// if not logged in, go to login
						if(!$loggedIn) {
							$debugMessage .= '<br>Not logged in, redirect to login page';
							$page='login';
							$loop=true;
							continue; #restart loop
						}
				}
			// controller -- if page=account
				elseif($page=='account') {
					$loop=false;
					$debugMessage .= '<br>(control loop -- account)';
					// if not logged in, go to login
						if(!$loggedIn) {
							$debugMessage .= '<br>Not logged in, redirect to login page';
							$page='login';
							$loop=true;
							continue; #restart loop
						}
				}
			// controller -- if page=addsong
				elseif($page=='addsong') {
					$loop=false;
					$debugMessage .= '<br>(control loop -- addsong)';
					// if not logged in, go to login
						if(!$loggedIn) {
							$debugMessage .= '<br>Not logged in, redirect to login page';
							$page='login';
							$loop=true;
							continue; #restart loop
						}
					// if form submitted, attempt to add song
						if(isset($_POST['addsong_submit'])) {
							// validate that there's a title.
								if(!isset($_POST['title'])) {
									$errorMessage .= '<br>Input error(title)';
								} elseif(!$_POST['title']) {
									$errorMessage .= '<br>Please enter a title';
								} else {
									$songTitle = htmlspecialchars($_POST['title']);  # user-supplied. could have xss
								}
							// validate that there's an artist.
								if(!isset($_POST['artist'])) {
									$errorMessage .= '<br>Input error(artist)';
								} elseif(!$_POST['artist']) {
									$errorMessage .= '<br>Please enter an artist';
								} else {
									$songArtist = htmlspecialchars($_POST['artist']); # user-supplied. could have xss
								}
							// validate that there are lyrics.
								if(!isset($_POST['lyrics'])) {
									$errorMessage .= '<br>Input error(lyrics)';
								} elseif(!$_POST['lyrics']) {
									$errorMessage .= '<br>Please enter lyrics';
								} else {
									$songLyrics = htmlspecialchars($_POST['lyrics']); # user-supplied, could have xss
								}
							// if no $errorMessage, attempt to add song to database
								if(!$errorMessage) {
									$debugMessage .= '<br>attempt to insert song into database';
									$sql = $db->prepare('INSERT INTO song (title, artist, lyrics, uploadedBy) VALUES (?,?,?,?)');
									if($sql->execute(array($_POST['title'],$_POST['artist'],$_POST['lyrics'],$_SESSION['login']['id']))) { 
											# remember there could be xss in database now
										$message .= '<br>Song uploaded';
										// it worked! redirect to account
											$loop = true;
											$page = 'account';
									} else {
										$errorMessage .= '<br>The system could not upload the song';
									}
								} 
						}
					
				}
			// controller -- if page=editprofile
				elseif($page=='editprofile') {
					$loop=false;
					$debugMessage .= '<br>(control loop -- editprofile)';
					// if not logged in, go to login
						if(!$loggedIn) {
							$debugMessage .= '<br>Not logged in, redirect to login page';
							$page='login';
							$loop=true;
							continue; #restart loop
						}
					// if personal info form submitted, attempt to update personal info
						if(isset($_POST['editprofile_personalinfo_submit'])) {
							$updateEmail = false;
							$updateUsername = false;
							// validate email
								if(!isset($_POST['email'])) {
									$errorMessage .= '<br>Input error (email). Email not updated.';
								} elseif(!$_POST['email'] or $_POST['email'] == $email) {
									$message .= '<br>No update to email address.';
								} elseif(!filter_var($_POST['email'],FILTER_VALIDATE_EMAIL)) {
									$errorMessage .= '<br>Not a valid email address. Email not updated.';
								} else {
									$updateEmail = true;
								}
							// validate username
								if(!isset($_POST['username'])) {
									$errorMessage .= '<br>Input error (username). Username not updated.';
								} elseif(!$_POST['username'] or $_POST['username'] == $username) {
									$message .= '<br>No update to username.';
								} elseif(!preg_match("/^[a-zA-Z0-9_]*$/",$_POST['username'])) {
									$errorMessage .= '<br>Usernames can only use letters, numbers, and underscores. Username not updated.';
								}  else {
									$updateUsername = true;
								}
							// update items user has changed
								if($updateEmail or $updateUsername) {
									$debugMessage .= "<br>attempt to update user's info in database";
									// check for uniqueness of new email and username
										###
									// update database
										$sql = $db->prepare("UPDATE user SET email=?, username=? WHERE id=?");
										$sql_inputs[0] = $updateEmail ? $_POST['email'] : $email;
										$sql_inputs[1] = $updateUsername ? $_POST['username'] : $username;
										$sql_inputs[2] = $_SESSION['login']['id'];
										if($sql->execute($sql_inputs)) {
											$debugMessage .= '<br>'.$sql->rowCount().' rows updated.';
											if($updateEmail) {
												$message .= '<br>Email updated.';
												$email = $_POST['email'];
											}
											if($updateUsername) {
												$message .= '<br>Username updated.';
												$username = $_POST['username'];
												$emailOrUsername = $_POST['username']; # we know username exists
											}
											// it worked, now loop will end and display editprofile page again
										} else {
											$errorMessage .= '<br>The system could not update your information.';
										}
								}
							}

					// if change password form submitted, attempt to update password
						if(isset($_POST['editprofile_password_submit'])) {
							// validate new password
								if(!isset($_POST['old_pw']) or !isset($_POST['pw']) or !isset($_POST['re_pw'])) {
									$errorMessage .= '<br>Input error (password)';
								} elseif($_POST['pw'] != $_POST['re_pw']) {
									$errorMessage .= '<br>Passwords do not match.';
								} elseif(!$_POST['pw'] and !$_POST['re_pw']) {
									$message .= '<br>No update to password';
								} else { 	
									// attempt to update password. 
										// check old password
										$sql = $db->prepare("SELECT pw FROM user WHERE id=? AND pw=sha2(?,256)");
										if($sql->execute(array($_SESSION['login']['id'],$_POST['old_pw']))) {
											if($sql->rowCount()>0) {
												// update password
												$sql = $db->prepare("UPDATE user SET pw=sha2(?,256) WHERE id=?");
												if($sql->execute(array($_POST['pw'],$_SESSION['login']['id']))) {
													$debugMessage .= '<br>'.$sql->rowCount().' rows updated.';
													$message .= 'Password updated';
													// it worked, now loop will end and display editprofile page again
												} else {
													$errorMessage .= '<br>The system could not update your password.';
												}
											} else {
												$errorMessage .= '<br>Old password incorrect.';
											}	
										} else {
											$errorMessage .= '<br>The system could not access your account.';
										}
								}
						}
						
						// look up songs uploaded by the user, so they can be displayed
							$sql = $db->prepare('SELECT id, title, artist FROM song WHERE uploadedBy=?');
							$sql->execute(array($_SESSION['login']['id']));
							$mySongRows = $sql->fetchAll(PDO::FETCH_ASSOC);
				}
				
			// controller -- if page=deletesong
				elseif($page=='deletesong') {
					$loop = false;
					$debugMessage .= '<br>(control loop -- deletesong)';
					// if not logged in, go to login
						if(!$loggedIn) {
							$debugMessage .= '<br>Not logged in, redirect to login page';
							$page='login';
							$loop=true;
							continue; #restart loop
						}
					// redirect if no songID in $_GET array
						if(!isset($_GET['songID'])) {
							$debugMessage .= '<br>No songID, redirect to editprofile page';
							$page = 'editprofile';
							$loop=true;
							continue;  #restart loop
						}
					// if form submitted, delete or don't delete song
						if(isset($_POST['deletesong_yes'])) {
							$debugMessage .= '<br>try to delete song';
							$loop = true;
							$page = 'editprofile';
							$sql = $db->prepare("DELETE FROM song WHERE id=?");
							if($sql->execute(array($_GET['songID']))) {
								$debugMessage .= '<br>'.$sql->rowCount().' rows deleted.';
								if($sql->rowCount()>0) {
									$message .= '<br>Song deleted';
								} else {
									$errorMessage .= '<br>Song not found.';
									$debugMessage .= '<br>songID not found in database.';
								}
							} else {
								$errorMessage .= '<br>Search error.';
								$debugMessage .= '<br>Query failed.';
							}
						} elseif(isset($_POST['deletesong_no'])) {
							$debugMessage .= '<br>not deleting song';
							$loop = true;
							$page = 'editprofile';
						} else {
					// form not submitted, ask for deletion confirmation
							// get name of selected song from database
							$sql = $db->prepare("SELECT title, artist from song where id=?");
							if($sql->execute(array($_GET['songID']))) {
								// check if there is a result (id is primary key, so there can only be 0 or 1 results)
								if($sql_result = $sql->fetch(PDO::FETCH_ASSOC)) {
									$songTitle = htmlspecialchars($sql_result['title']); # user-supplied, potential xss
									$songArtist = htmlspecialchars($sql_result['artist']); # user-supplied, potential xss
								} else {
									$errorMessage .= '<br>Error, song not found.';
									$debugMessage .= '<br>No matching song found. Redirect to editprofile page.';
									$loop = true;
									$page = 'editprofile';
								}
							} else {
								$errorMessage .= '<br>Search error.';
								$debugMessage .= '<br>Query failed. Redirect to editprofile page.';
								$loop = true;
								$page = 'editprofile';
							}
						}
					
				}
			// controller -- undefined page names
				else {
					$debugMessage .= '<br>control loop -- other page. should redirect to intro';
					$page='intro';
				}
		}
	
	// display 
		// display -- start with header stuff
			?>
				<!DOCTYPE html>
				<html>
				<head>
				<title>Lyrics lookup</title>
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<!--formatting-->
				<style type="text/css">
					* {
						box-sizing: border-box;
					}
					body {
						margin: 0em;
						font-size: 1em;
						font-family: Arial, sans-serif;
					}
					
					div.header , div.footer {
						padding: 0em 1em;
						background-color: #4477aa;
						overflow: auto;
						text-align: right;
					} div.header {
						/* width: 100%; */
						/* position: fixed;*/
						/* top: 0em;*/
					}
					
					h1.logo {
						display: inline-block;
						float: left;
						color: white;
						/* font-family: "Times New Roman", serif; */
						font-size: 2em;
						margin: 0em;
						padding: 0.25em 0em;
					}
					
					.headerLink , .footerLink {
						color: white;
						display: inline-block;
						padding: 1em;
					}
					
					.copyright {
						float: left;
						color: white;
					}
					
					.searchBar {
						display: inline-block;
						padding: 0.5em 1em;
					}
					
					div.content {
						padding: 1em;
					}
					
					textarea {
						vertical-align: top;
					}
					
					p.lyrics{
						white-space: pre-wrap;
					}
					
					p.errorMessage{
						color: red;
					}
					
					p.message{
						color: green;
					}
					
					textarea{
						resize: none;
					}
					
					@media only screen and (min-width:769px){
						body {
							font-size: 1em;
						}
					}
				</style>
				</head>
				<body>
				<!--common header-->
				<div class="header">
					<a href="index.php?page=intro"  >
						<h1 class="logo">Lyrics Lookup</h1>
					</a>
					<form class="searchBar" action="index.php" method="GET">
						<input type="hidden" name="page" value="search" >
							<!--this doesn't show up. it just sends us to the search page-->
						<input type="text" name="search" value="" placeholder="Search">
					</form>
					<?php
						if($loggedIn) {
							?> 
							<a class="headerLink" href="index.php?page=account">My Account</a>
							<a class="headerLink" href="index.php?page=logout" >Log Out</a> 
							<?php
						} else {
							?>
							<a class="headerLink" href="index.php?page=signup" >Sign Up</a> 
							<a class="headerLink" href="index.php?page=login"  >Log In</a>
							<?php
						} 
					?> 
				</div>
				<div class="content">
			<?php
		
		// display -- content between header and footer depends on page requested
			if($errorMessage) {
				echo '<p class="errorMessage">'.$errorMessage.'</p>';
			}
			if($message) {
				echo '<p class="message">'.$message.'</p>';
			}
			// display -- if page=intro
				if($page=='intro') {
					?>
						<h1>Welcome!</h1>
						<p>Go ahead and <a href="index.php?page=songs">browse our songs</a> totally free!</p>
						
					<?php
				}
			// display -- if page=about
				if($page=='about') {
					?>
						<h1>About Lyrics Lookup</h1>
						<p>It doesn't actually exist. I'm just practicing my php, sql, html, and css.</p>
					<?php
				}
			// display -- if page=contact
				if($page=='contact') {
					?>
						<h1>Contact Us</h1>
						<p>Phone: ###-###-####</p>
						<p>Email: nobody@nowhere.com</p>
					<?php
				}
			// display -- if page=sitemap
				if($page=='sitemap') {
					?>
						<h1>Site Map</h1>
						<p>Here we'll have a list of all the different parts of the site, with links.</p>
					<?php
				}	
			// display -- if page=songs
				elseif($page=='songs') {
					?>
						<h1>Songs</h1>
							<?php
								// list out all songs
									for($i=0; $i<count($songRows); $i++) {
										// link
											echo '<p><a href="index.php?page=lyrics&songID='.$songRows[$i]['id'].'">';
										// song title 
											echo $songRows[$i]['title'].'</a>'.', by '.$songRows[$i]['artist'].'</p>';
									}
							?>
					<?php
				}
			// display -- if page=lyrics
				elseif($page=='lyrics') { 
					?>
						<h1>Lyrics to: <?php echo $songTitle ?></h1>
						<h2>by: <?php echo $songArtist ?></h2>
						<!--<h3>uploaded by: <?php echo $songUploader ?></h3>-->
						<p class="lyrics"><?php  echo $songLyrics ?></p>
						<br>
						<a href="index.php?page=songs">Return to songs page</a>
					<?php
				}
			// display -- if page=search
				elseif($page=='search') {
					?>
						<h1>Search</h1>
					<?php
				}
			// display -- if page=signup
				elseif($page=='signup') {
					?>
						<h1>Sign Up</h1>
						<form action="index.php?page=signup" method="POST">
							<!--optimally I should probably find a way to encrypt on the client side before sending to index.php on the server-->
							<p>Email: <input name="email" type="text" value="<?= $email ?>"></p>
							<p>Username (optional): <input name="username" type="text" value="<?= $username ?>"></p>
							<p>Password: <input name="pw" type="password"></p> 
							<p>Re-enter password: <input name="re_pw" type="password"></p>
							<input name="signup_submit" type="submit" value="Sign Up">
						</form>
					<?php
				}
			// display -- if page=login
				elseif($page=='login') {
					?>
						<h1>Login</h1>
						<form action="index.php?page=login" method="POST">
							<p>Email or username: <input name="emailOrUsername" type="text" value="<?= $emailOrUsername ?>"></p>
							<p>Password: <input name="pw" type="password"></p>
							<input name="login_submit" type="submit" value="Login">
						</form>
					<?php
				}
			// display -- if page=account
				elseif($page=='account') {
					?>
						<h1><?= $emailOrUsername ?>'s account</h1>
						<a href="index.php?page=editprofile">Edit Profile</a>
						<a href="index.php?page=addsong">Add Song</a>
						<a href="index.php?page=logout">Log Out</a>
					<?php
				}
			// display -- if page=addsong
				elseif($page=='addsong') {
					?>
						<h1>Add Song</h1>
						<form action="index.php?page=addsong" method="POST">
							<p>Title: <input name="title" type="text" value="<?= $songTitle ?>"></p>
							<p>Artist: <input name="artist" type="text" value="<?= $songArtist ?>"></p>
							<p>Lyrics: <textarea name="lyrics" rows="10" cols="60"><?= $songLyrics ?></textarea></p>
							<input name="addsong_submit" type="submit" value="Add Song">
						</form>
					<?php
				}
			// display -- if page=editprofile
				elseif($page=='editprofile') {
					?>
						<h1>Edit Profile</h1>
						<h2>Personal Info</h2>
							<form action="index.php?page=editprofile" method="POST">
								<p>Email: <input name="email" type="text" value="<?= $email ?>" ></p>
								<p>Username: <input name="username" type="text" value="<?= $username ?>" ></p>
								<input name="editprofile_personalinfo_submit" type="submit" value="Save Changes" >
							</form>
						<h2>Password</h2>
							<form action="index.php?page=editprofile" method="POST">
								<p>Old Password: <input name="old_pw" type="password" ></p>
								<p>Reset Password: <input name="pw" type="password" ></p>
								<p>Confirm Password: <input name="re_pw" type="password" ></p>
								<input name="editprofile_password_submit" type="submit" value="Changed Password" >
							</form>
						<h2>Songs You've Uploaded</h2>
							<?php
								// list out all songs
									for($i=0; $i<count($mySongRows); $i++) {
										// link
											echo '<p><a href="index.php?page=lyrics&songID='.$mySongRows[$i]['id'].'">';
										// song title 
											echo $mySongRows[$i]['title'].'</a>'.', by '.$mySongRows[$i]['artist'].'  ';
										// option to delete
											echo '<a href="index.php?page=deletesong&songID='.$mySongRows[$i]['id'].'">Delete Song</a>'.'</p>';
									}
	
							?>
						<!--anything after songs you've uploaded?-->
					<?php
				}
				elseif($page=='deletesong') {
					?>
						<h1>Delete "<?= $songTitle ?>" by <?= $songArtist ?>?</h1>
						<form action="index.php?page=deletesong&songID=<?= $_GET['songID'] ?>" method="POST"> 
							<input type="submit" name="deletesong_yes" value="Yes">
							<input type="submit" name="deletesong_no" value="No">
						</form>						
					<?php
				}
			// display -- if page=logout
				elseif($page=='logout') {
					?>
						<h1>Logout?</h1>
						<a href="index.php?page=intro&logout=true"><button type="button">Yes</button></a>
						<a href="index.php?page=account"><button type="button">No</button></a>
					<?php
				}

		// display -- end with footer stuff
			?>
				<!--common footer-->
				</div>
				<div class="footer">
					<!--about, contact, copyright, site map, -->
					<p class="copyright">&copy Copyright 2017</p>
					<a class="footerLink" href="index.php?page=about">About</a>
					<a class="footerLink" href="index.php?page=contact">Contact Us</a>
					<a class="footerLink" href="index.php?page=sitemap">Site Map</a>
				</div>
				<?php
					if($debugMode) {
						echo "<br><br><pre>{$debugMessage}</pre>";
					}
				?>
				</body>
				</html>
			<?php
			
	// concerns
		// since my redirection happens without changing the get array, you'll have one page displaying and a different url in the address bar
		
	
?>
