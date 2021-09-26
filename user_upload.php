<?php
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
