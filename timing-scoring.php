<html>
<head>
<meta http-equiv="refresh" content="30">
<style>
body {
  font-family: sans-serif;
}
table {
  border-collapse: collapse;
  width: 100%;
}
table, th, td {
  border: 1px solid black;
}
tr:nth-child(even) {
  background-color: #f2f2f2;
}
</style>
</head>
<div style="overflow-x:auto;">
<table>
<?php

$ch = curl_init();		//Setting up cURL request to pull JSON
curl_setopt($ch, CURLOPT_URL, "http://racecontrol.indycar.com/xml/timingscoring.json");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$get = curl_exec($ch);	//Pull data from ICS
curl_close($ch);		//Close cURL request
$topTrim    = str_replace("jsonCallback(","",$get);     //Trim top line
$bottomTrim = str_replace(");","",$topTrim);            //Trim bottom line
$data       = json_decode($bottomTrim);                 //Load formatted string as JSON data
$event      = $data->{'timing_results'}->{'heartbeat'}; //Setup $event variable

//DEBUG
/*
$get        = file_get_contents("JSON-SC-Qual.txt");
$data       = json_decode($get);		//Load local data file as JSON data
$event      = $data->{'timing_results'}->{'heartbeat'}; //Setup $event variable
*/

//On the chance that the JSON is broke, T&S formatting has changed, or something else exploded, just stop the script.
if(array_key_exists('trackType', $event) == FALSE or array_key_exists('preamble', $event) == FALSE ){
	exit("Invalid T&S data received.");
}

//Event Type
switch($event->{'trackType'}){
	case "O": $eventType = "Oval"; break;
	case "I": $eventType = "Oval"; break;
	case "RC": $eventType = "Road Course"; break;
	case "SC": $eventType = "Street Course"; break;
	default: $eventType = "Race Course"; break;
}
//Status Type
switch($event->{'currentFlag'}){
	case "GREEN": $eventFlag ="<p style='color:green;font-weight:bold;'>GREEN</p>"; break;
	case "YELLOW": $eventFlag = "<p style='color:orange;font-weight:bold;'>YELLOW</p>"; break;
	case "RED": $eventFlag = "<p style='color:red;font-weight:bold;'>RED</p>"; break;
	default: $eventFlag = "COLD (no track activity)"; break;
}
//Session Type
if ($event->{'SessionType'} == "Q"){
	if ($eventType != "Oval"){ //Qualifying for Road/Street
		switch($event->{'preamble'}){
			case "Q1.I": $eventSession = "Qualifying Round 1 Group 1"; break;
			case "Q2.I": $eventSession = "Qualifying Round 1 Group 2"; break;
			case "Q3.I": $eventSession = "Qualifying Round 2 (Fast 12)"; break;
			case "Q4.I": $eventSession = "Qualifying Round 2 (Fast 6)"; break;
			default: $eventSession = "Qualifying"; break;
		}
	}
	else{
		$eventSession = "Qualifying";     //Qualifying for oval tracks
	}
}
elseif ($event->{'SessionType'} == "P"){
	$eventSession = "Practice";
}
else{
	$eventSession = "Race";
}

print "<tr><td style='font-weight:bold; width:10%;'>Race Name:</td><td>" . $event->{'eventName'} . "</td></tr>";
print "<tr><td style='font-weight:bold;'>Track Name:</td><td>" . $event->{'trackName'} . "</td></tr>";
print "<tr><td style='font-weight:bold;'>Session:</td><td>" . $eventSession . "</td></tr>";
print "<tr><td style='font-weight:bold;'>Status:</td><td>" . $eventFlag . "</td></tr>";
if (array_key_exists('overallTimeToGo', $event) == FALSE){
	print "<tr><td style='font-weight:bold;'>Elapsed Time:</td><td>" . $event->{'elapsedTime'};	// Qual/Race will show elapsed time
}
else{
	print "<tr><td style='font-weight:bold;'>Time Left:</td><td>" . $event->{'overallTimeToGo'};	// Practice will show time remaining. This may also occur during timed races, not sure
}
print "<tr><td style='font-weight:bold;'>Comment:</td><td>" . $event->{'Comment'} . "</td></tr>";
if ($eventSession == "Race"){
	if (array_key_exists('totalLaps', $event)){
		print "<tr><td style='font-weight:bold;'>Lap:</td><td>" . $event->{'lapNumber'} . " of " . $event->{'totalLaps'} . "</td></tr>";
	}
	else{
		print "<tr><td style='font-weight:bold;'>Lap:</td><td>" . $event->{'lapNumber'} . "</td></tr>";	//Theoretically if a race becomes a timed race, this will display
	}
}
?>
</table>
<table>
<?php
if ($event->{'SessionType'} == "R") { //If event is a Race. . . 
	if ($eventType != "Oval") { //If track *is not* an Oval. . .
		echo '		<thead>
		<tr>
			<th>Position</th>
			<th>Driver</th>
			<th>Car</th>
			<th>Last Lap</th>
			<th>Gap to Leader</th>
			<th>Gap Ahead</th>
			<th>Best S1</th>
			<th>Best S2</th>
			<th>Best S3</th>
			<th>Last S1</th>
			<th>Last S2</th>
			<th>Last S3</th>
			<th>Tire</th>';
		if (preg_match("/\.I|.L/", $event->{'preamble'})) {	//If it's a Lights or Indycar Race
			echo '<th>Push 2 Pass Remaining</th>';
		}
		echo '	<th>Status</th>
		</tr>
	</thead>';
	}
	else { //If track IS an Oval. . .
		echo '		<thead>
		<tr>
			<th>Position</th>
			<th>Driver</th>
			<th>Car</th>
			<th>Last Lap</th>
			<th>Gap to Leader</th>
			<th>Gap Ahead</th>
			<th>Status</th>
		</tr>
	</thead>';
	}
}
else { //If event *is not* a Race. . .
	if ($eventType != "Oval") { //If track *is not* an Oval. . .
		echo '		<thead>
	<tr>
		<th>Position</th>
		<th>Driver</th>
		<th>Car</th>
		<th>Last Lap</th>
		<th>Best Lap</th>
		<th>Best S1</th>
		<th>Best S2</th>
		<th>Best S3</th>
		<th>Last S1</th>
		<th>Last S2</th>
		<th>Last S3</th>
		<th>Tire</th>
		<th>Status</th>
	</tr>
</thead>';
	}
	else { //If track IS an Oval. . .
		echo '		<thead>
		<tr>
			<th>Position</th>
			<th>Driver</th>
			<th>Car</th>
			<th>Last Lap</th>
			<th>Best Lap</th>
			<th>Status</th>
		</tr>
	</thead>';
	}
}
?>

	<tbody>
<?php
//Driver Tables: Lap Times
$bestLap = array();	//Setup an empty array to strip out uncompleted laps
foreach ($data->{'timing_results'}->{'Item'} as $drivers){
	if($drivers->{'bestLapTime'} != "0.0000"){
		$bestLap[] = $drivers->{'bestLapTime'};	//Fill the array with completed laps
	}
}
if($bestLap != NULL){	//If the array is not empty
	$bestMin = min($bestLap);	//Locate the fastest lap
}

//Driver Tables: Best Sectors
if ($eventType != "Oval"){	//This only applies for road/street courses
	//Best Sector 1
	$bestS1 = array();	//Setup an empty array to strip out uncompleted S1
	foreach ($data->{'timing_results'}->{'Item'} as $drivers){
		if($drivers->{'Best_I1'} != "0.0000"){
			$bestS1[] = $drivers->{'Best_I1'};	//Fill the array with completed S1
		}
	}
	if($bestS1 != NULL){
		$bestS1Min = min($bestS1);	//Locate the fastest S1
	}

	//Best Sector 2
	$bestS2 = array();	//Setup an empty array to strip out uncompleted S2
	foreach ($data->{'timing_results'}->{'Item'} as $drivers){
		if($drivers->{'Best_I2'} != "0.0000"){
			$bestS2[] = $drivers->{'Best_I2'};	//Fill the array with completed S2
		}
	}
	if($bestS2 != NULL){
		$bestS2Min = min($bestS2);	//Locate the fastest S2
	}

	//Best Sector 3
	$bestS3 = array();	//Setup an empty array to strip out uncompleted S3
	foreach ($data->{'timing_results'}->{'Item'} as $drivers){
		if($drivers->{'Best_I3'} != "0.0000"){
			$bestS3[] = $drivers->{'Best_I3'};	//Fill the array with completed S3
		}
	}
	if($bestS3 != NULL){
		$bestS3Min = min($bestS3);	//Locate the fastest S3
	}
}

//Driver Tables: Everything Else
foreach ($data->{'timing_results'}->{'Item'} as $drivers){
	$position		= $drivers->{'rank'};
	$driverName 	= $drivers->{'lastName'};
	$carNum 		= $drivers->{'no'};
	$team 			= $drivers->{'team'};
	if ($drivers->{'bestLapTime'} == $bestMin){	//If a driver's fastest lap is best overall, color it purple
		$bestLapTime = "<p style='color:purple;font-weight:bold;'>".$drivers->{'bestLapTime'}."</p>";
	}
	else{
		$bestLapTime = $drivers->{'bestLapTime'};
	}
	if ($drivers->{'lastLapTime'} == $bestMin){	//If a driver's last lap is best overall, color it purple
		$lastLapTime = "<p style='color:purple;font-weight:bold;'>".$drivers->{'lastLapTime'}."</p>";
	}
	if ($drivers->{'lastLapTime'} == $drivers->{'bestLapTime'} && $drivers->{'lastLapTime'} != $bestMin){	//If a driver's last lap is their personal best, color it green
		$lastLapTime = "<p style='color:green;font-weight:bold;'>".$drivers->{'lastLapTime'}."</p>";
	}
	if ($drivers->{'lastLapTime'} == "0.0000"){
		$lastLapTime = $drivers->{'lastLapTime'};
	}
	else{
		$lastLapTime = $drivers->{'lastLapTime'};
	}
	$diff2Lead 		= $drivers->{'diff'};
	$gapAhead 		= $drivers->{'gap'};
	if ($drivers->{'OverTake_Remain'} >= 100){
		$p2pRemain = "<p style='color:green;font-weight:bold;'>".$drivers->{'OverTake_Remain'}."</p>";
	}
	elseif ($drivers->{'OverTake_Remain'} <= 99 && $drivers->{'OverTake_Remain'} >= 60){
		$p2pRemain = "<p style='color:orange;font-weight:bold;'>".$drivers->{'OverTake_Remain'}."</p>";
	}
	elseif ($drivers->{'OverTake_Remain'} <= 59) {
		$p2pRemain = "<p style='color:red;font-weight:bold;'>".$drivers->{'OverTake_Remain'}."</p>";
	}
	//$p2pRemain 		= $drivers->{'OverTake_Remain'};
	$status			= $drivers->{'status'};
	switch($drivers->{'Tire'}){
		case "P": $driverTire = "<p style='font-weight:bold;'>Black</p>"; break;
		case "W": $driverTire = "<p style='color:blue;font-weight:bold;'>Wet</p>"; break;
		case "A": $driverTire = "<p style='color:red;font-weight:bold;'>Red</p>"; break;
		default: $driverTire = "Unknown"; break;
	}
	//Oval
	if ($eventType == "Oval"){
		if ($event->{'SessionType'} == "R"){
			print "<tr><td>" .$position. "</td><td>" .$driverName. "</td><td>" .$carNum. "</td><td>" .$lastLapTime. "</td><td>" .$diff2Lead. "</td><td>" .$gapAhead. "</td><td>" .$status. "</td></tr>";
		}
		else{ //Practice or Qualifying
			print "<tr><td>" .$position. "</td><td>" .$driverName. "</td><td>" .$carNum. "</td><td>" .$lastLapTime. "</td><td>" .$bestLapTime. "</td><td>" .$status. "</td></tr>";
		}
	}
	//Road Course/Street Course
	else{
		//Best Sectors
		if (array_key_exists('Best_I1', $drivers) == FALSE){	//If the Best S1 key doesn't exist, set the printed result to be dashes
			$bSect1 = "<p style='text-align: center;'>--</p>";
		}
		else {
			if ($drivers->{'Best_I1'} == $bestS1Min){	//If a driver's fastest Sector 1 is best overall, color it purple
				$bSect1 = "<p style='color:purple;font-weight:bold;'>".$drivers->{'Best_I1'}."</p>";
			}
			else{
				$bSect1 = $drivers->{'Best_I1'};
			}
		}
		if (array_key_exists('Best_I2', $drivers) == FALSE){	//If the Best S2 key doesn't exist, set the printed result to be dashes
			$bSect2 = "<p style='text-align: center;'>--</p>";
		}
		else {
			if ($drivers->{'Best_I2'} == $bestS2Min){	//If a driver's fastest Sector 2 is best overall, color it purple
				$bSect2 = "<p style='color:purple;font-weight:bold;'>".$drivers->{'Best_I2'}."</p>";
			}
			else{
				$bSect2 = $drivers->{'Best_I2'};
			}
		}
		if (array_key_exists('Best_I3', $drivers) == FALSE){	//If the Best S3 key doesn't exist, set the printed result to be dashes
			$bSect3 = "<p style='text-align: center;'>--</p>";
		}
		else {
			if ($drivers->{'Best_I3'} == $bestS3Min){	//If a driver's fastest Sector 3 is best overall, color it purple
				$bSect3 = "<p style='color:purple;font-weight:bold;'>".$drivers->{'Best_I3'}."</p>";
			}
			else{
				$bSect3 = $drivers->{'Best_I3'};
			}
		}
		//Last Sectors
		if ($drivers->{'I1'} == $bestS1Min){	//If a driver's best Sector 1 is best overall, color it purple
			$lSect1 = "<p style='color:purple;font-weight:bold;'>".$drivers->{'I1'}."</p>";
		}
		elseif ($drivers->{'I1'} == $drivers->{'Best_I1'} && $drivers->{'I1'} != $bestS1Min){	//If it's just a personal best, color it green
			$lSect1 = "<p style='color:green;font-weight:bold;'>".$drivers->{'I1'}."</p>";
		}
		else{
			$lSect1 = $drivers->{'I1'};
		}
		if ($drivers->{'I2'} == $bestS2Min){	//If a driver's best Sector 2 is best overall, color it purple
			$lSect2 = "<p style='color:purple;font-weight:bold;'>".$drivers->{'I2'}."</p>";
		}
		elseif ($drivers->{'I2'} == $drivers->{'Best_I2'} && $drivers->{'I2'} != $bestS1Min){	//If it's just a personal best, color it green
			$lSect1 = "<p style='color:green;font-weight:bold;'>".$drivers->{'I2'}."</p>";
		}
		else{
			$lSect2 = $drivers->{'I2'};
		}
		if ($drivers->{'I3'} == $bestS3Min){	//If a driver's best Sector 3is best overall, color it purple
			$lSect3 = "<p style='color:purple;font-weight:bold;'>".$drivers->{'I3'}."</p>";
		}
		elseif ($drivers->{'I3'} == $drivers->{'Best_I3'} && $drivers->{'I3'} != $bestS1Min){	//If it's just a personal best, color it green
			$lSect1 = "<p style='color:green;font-weight:bold;'>".$drivers->{'I3'}."</p>";
		}
		else{
			$lSect3 = $drivers->{'I3'};
		}

		// This should cover racing for all road/street courses
		if ($event->{'SessionType'} == "R"){
			if (preg_match("/\.I|.L/", $event->{'preamble'})) {	//If it's an Indy Lights or ICS race
				print "<tr><td>" .$position. "</td><td>" .$driverName. "</td><td>" .$carNum. "</td><td>" .$lastLapTime. "</td><td>" .$diff2Lead. "</td><td>" .$gapAhead. "</td><td>" .$bSect1. "</td><td>" .$bSect2. "</td><td>" .$bSect3. "</td><td>" .$lSect1. "</td><td>" .$lSect2. "</td><td>" .$lSect3. "</td><td>" .$driverTire. "</td><td>" .$p2pRemain. "</td><td>" .$status. "</td></tr>";
			}
			else {
				print "<tr><td>" .$position. "</td><td>" .$driverName. "</td><td>" .$carNum. "</td><td>" .$lastLapTime. "</td><td>" .$diff2Lead. "</td><td>" .$gapAhead. "</td><td>" .$driverTire. "</td><td>" .$status. "</td></tr>";
			}
		}
		// This should cover qualification for all road/street courses
		elseif ($event->{'SessionType'} == "Q"){
			if ($position == "7" and preg_match("/\.I/", $event->{'preamble'})){ //optionally: preg_match("/Q.\.I/", $event->{'preamble'})
				print "<tr><td colspan='100%' style='text-align: center;'>--- TRANSFER CUT OFF ---</td></tr>";
				print "<tr><td>" .$position. "</td><td>" .$driverName. "</td><td>" .$carNum. "</td><td>" .$lastLapTime. "</td><td>" .$bestLapTime. "</td><td>" .$bSect1. "</td><td>" .$bSect2. "</td><td>" .$bSect3. "</td><td>" .$lSect1. "</td><td>" .$lSect2. "</td><td>" .$lSect3. "</td><td>" .$driverTire. "</td><td>" .$status. "</td></tr>";
			}
			else{
				print "<tr><td>" .$position. "</td><td>" .$driverName. "</td><td>" .$carNum. "</td><td>" .$lastLapTime. "</td><td>" .$bestLapTime. "</td><td>" .$bSect1. "</td><td>" .$bSect2. "</td><td>" .$bSect3. "</td><td>" .$lSect1. "</td><td>" .$lSect2. "</td><td>" .$lSect3. "</td><td>" .$driverTire. "</td><td>" .$status. "</td></tr>";
			}
		}
		// This should cover practice for all road/street courses
		else{
			print "<tr><td>" .$position. "</td><td>" .$driverName. "</td><td>" .$carNum. "</td><td>" .$lastLapTime. "</td><td>" .$bestLapTime. "</td><td>" .$bSect1. "</td><td>" .$bSect2. "</td><td>" .$bSect3. "</td><td>" .$lSect1. "</td><td>" .$lSect2. "</td><td>" .$lSect3. "</td><td>" .$driverTire. "</td><td>" .$status. "</td></tr>";
		}
	}
}
?>

	</tbody>
</table>
</div>
</html>