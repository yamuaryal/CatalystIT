<?php
$shortopts  = "";
$shortopts .= "u::";  
$shortopts .= "p::"; 
$shortopts .= "h::"; 
$longopts  = array(
    "file::",    
    "create_table::",    
    "dry_run::",    
    "help",    
);
try{
	$options = getopt($shortopts, $longopts);
	if(isset($options['help'])){
		$help = "\nAvailable Directives: \n";
		$help  .= "\t--file [csv file name] - Name of the CSV file to be parsed.\n";
		$help .= "\t--create_table - To create the MYSQL User Table( no further action will be taken ).\n";
		$help .= "\t--dry_run - Option to use with --file to run the script without altering the Database.\n";
		$help .= "\t-u - MySQL Username.\n";
		$help .= "\t-p - MySQL Password.\n";
		$help .= "\t-h - MySQL Host.\n";
		$help .= "\t--help - Show Options(help).\n";
		echo $help;
		exit(0);
	}
	if(isset($options['create_table'])){
		$conn = establish_mysql_connection();
		create_users_table($conn);
		exit(0);
		
	}
	if(isset($options['file'])){
		$filename = $options['file'];
		$dry_run = isset($options['dry_run']);
		if($dry_run){
			$conn = false;	// No database changes is done for dry_run, so do not connect in the first place.
		}else{			
			$conn = establish_mysql_connection();
			$table_exists = $conn->query("SHOW TABLES LIKE 'users'");
			if(! $table_exists || $table_exists->num_rows != 1){
				throw new Exception ("Users Table does not exist yet, Please create the table first using the --create_table option.");
			}
		}
			$row = 1;
			if (($handle = fopen("{$filename}", "r")) !== FALSE) {
				while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
					$num = count($data);
					// First row has column names, skip that row. 
					//@Assumption: The order of column is always: name surname email
						if($row > 1){ 
							// Name and surname to have uppercase first letter ( forcing to lowercase if any letter within are capital)
							$email = strtolower(trim($data[2]));
							if (valid_email($email)) {
								if($conn){
									$name = mysqli_real_escape_string($conn, ucfirst(strtolower(trim($data[0]))));
									$surname = mysqli_real_escape_string($conn, ucfirst(strtolower(trim($data[1]))));
									$sql = "INSERT IGNORE INTO users (name, surname, email) VALUES ('{$name}', '{$surname}', '{$email}')";
									if ($conn->query($sql) != TRUE) {
									  echo "Error: " . $sql . "\n" . $conn->error;
									} 
								}
							}
							else{
								echo("Invalid Email address: $email found\n");
							}
						}
						$row++;
				}
				fclose($handle);
			}
		if($conn){
			$conn->close();	
		}
	}
}
catch (Exception $e){
	echo $e->getMessage()."\n";	
}


die;

function valid_email($str) {

return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) ? FALSE : TRUE;

}

function establish_mysql_connection(){
	$dbName = 'UserCollections';
	$options = getopt("u::p::h::");
	$username = $options['u'];
	$password = $options['p'];
	$hostname = $options['h'];
	
	if(!isset($options['u'])){
		$username = readline('Please enter MySQL Username:');
	}
	if(!isset($options['p'])){
		$password = readline('Please enter MySQL Password:');
	}
	if(!isset($options['h'])){
		$hostname = readline('Please enter Hostname:');
	}
			
	$conn = new mysqli($hostname, $username, $password);
	if ($conn->connect_error) {
		throw new Exception("Connection Failed: ".$conn->connect_error);
	}
	
	if (!mysqli_select_db($conn,$dbName)){
		$sql = "CREATE DATABASE IF NOT EXISTS ".$dbName;
		if ($conn->query($sql) != TRUE) {
			throw new Exception ("Error creating database: ". $conn->error);
		}
	}
	return $conn;
}
/*s
function establish_mysql_connection($hostname,$username,$password){
	$conn = new mysqli($hostname, $username, $password);
	if ($conn->connect_error) {
		throw new Exception("Connection Failed: ".$conn->connect_error);
	}
	return $conn;
}
*/

function create_users_table($conn){
		$dbName = 'UserCollections';
		$table = 'users';
    $sql = "CREATE DATABASE IF NOT EXISTS ".$dbName;
	if ($conn->query($sql) === TRUE) {
		// Create user table if it does not already exists
		if ($result = $conn->query("SHOW TABLES LIKE '".$table."'")) {
			if($result->num_rows == 1) {
				throw new Exception ("Users Table already exists.");
			}
			else{
				$sql = "CREATE TABLE users(
				id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
				name VARCHAR(30) NOT NULL,
				surname VARCHAR(30) NOT NULL,
				email VARCHAR(70) NOT NULL UNIQUE
				)";
				if(mysqli_query($conn, $sql)){
					echo "Table created successfully.";
				} else{
					throw new Exception ("Failed to create table. Error: ". mysqli_error($conn));
				}
			}
		}		
    }else {
		throw new Exception ("Error creating database: ". $conn->error);
    }
}
/*
//Include file that store database connection information
include('dbconfig.php');
$servername = DBHOST;
$username = DBUSER;
$password = DBPWD;
$dbName = DBNAME;
$table = 'users';

// Connect to MySQL
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// If database does not exist create one
if (!mysqli_select_db($conn,$dbName)){
    $sql = "CREATE DATABASE IF NOT EXISTS ".$dbName;
	if ($conn->query($sql) === TRUE) {
		// Create user table if it does not already exists
		if ($result = $conn->query("SHOW TABLES LIKE '".$table."'")) {
			if($result->num_rows == 1) {
				echo "Users Table exists";
			}
		}else {
			$sql = "CREATE TABLE users(
			id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
			name VARCHAR(30) NOT NULL,
			surname VARCHAR(30) NOT NULL,
			email VARCHAR(70) NOT NULL UNIQUE
			)";
			if(mysqli_query($conn, $sql)){
				echo "Table created successfully.";
			} else{
				echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
			}
					
		}		
    }else {
        echo "Error creating database: " . $conn->error;
    }
} 

// Get file name from user
$filename = readline('Please Enter the CSV File Name (Example: users.csv): ');

if($filename){
	$row = 1;
	if (($handle = fopen("{$filename}", "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
			$num = count($data);
			// First row has column names, skip that row. 
			//@Assumption: The order of column is always: name surname email
				if($row > 1){ 
					// Name and surname to have uppercase first letter ( forcing to lowercase if any letter within are capital)
					$name = ucfirst(strtolower(trim($data[0])));
					$surname = ucfirst(strtolower(trim($data[1])));
					$email = strtolower(trim($data[2]));
					if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
						
						
					}
					else{
						echo("$email is not a valid email address");
					}
				}
				$row++;
		}
		fclose($handle);
	}
}

*/