<?php
$mysqli = new mysqli("localhost", "root", "123456",
"xsl", 3306);

/* check connection */
if (mysqli_connect_errno()) {
	printf("Connect failed: %s\n", mysqli_connect_error());
	exit();
}

$city = "2";

/* create a prepared statement */
if ($stmt = $mysqli->prepare("SELECT id FROM x_logs WHERE id=?")) {

	/* bind parameters for markers */
	$stmt->bind_param("i", $city);

	/* execute query */
	$stmt->execute();

	/* bind result variables */
	$stmt->bind_result($district);

	/* fetch value */
	$stmt->fetch();

	printf("%s is in district %s\n", $city, $district);

	/* close statement */
	$stmt->close();
}

/* close connection */
$mysqli->close();
