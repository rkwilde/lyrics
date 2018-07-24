# lyrics
This is a simple site for users to upload lyrics sheets into a central database. There is a secure sign-in page and a place to change your information and review the songs you’ve uploaded.

I mostly was practicing PHP. It’s one of the earlier sites I tried, and the code formatting is rather horrifying (all the code is in one file), but as far as I can tell, the features that have been implemented are working. Some features, like Search, are not implemented yet.

If you try to run this yourself, you will need to edit line 50 (database connection variables), and then add the following tables in a sql database:

table `song`
Field 	Type 	Null 	Key 	Default 	Extra
id 	int(8) 	NO 	PRI 	NULL	auto_increment
title 	varchar(100) 	NO 		NULL
artist 	varchar(100) 	NO 		NULL
lyrics 	text 	NO 		NULL
uploaded 	datetime 	NO 		CURRENT_TIMESTAMP 
uploadedBy 	int(10) 	YES 		NULL

table `user`
Field 	Type 	Null 	Key 	Default 	Extra 
id 	int(10) 	NO 	PRI 	NULL	auto_increment
email 	varchar(100) 	NO 		NULL
username 	varchar(100) 	YES 		NULL
pw 	varchar(100) 	NO 		NULL
created 	datetime 	NO 		CURRENT_TIMESTAMP 	
