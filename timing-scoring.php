<html>
<head>
<meta http-equiv="refresh" content="30">
<style>
table {
  border-collapse: collapse;
  width: 50%;
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
$get        = file_get_contents("JSON-RC-Race.txt");
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

print "<tr><td style='font-weight:bold;'>Race Name:</td><td>" . $event->{'eventName'} . "</td></tr>";
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
//Driver Tables
$bestLap = array();	//Setup an empty array to strip out uncompleted laps
foreach ($data->{'timing_results'}->{'Item'} as $drivers){
	$position		= $drivers->{'rank'};
	$driverName 	= $drivers->{'lastName'};
	$carNum 		= $drivers->{'no'};
	$team 			= $drivers->{'team'};
	if($drivers->{'bestLapTime'} != "0.0000"){
		$bestLap[] = $drivers->{'bestLapTime'};	//Fill the array with completed laps
	}
	if($bestLap == NULL){
		$bestLapTime = $drivers->{'bestLapTime'};	//If no laps have been set, print the normal laptimes.
	}
	else{
		$bestMin = min($bestLap);	//Locate the fastest lap
	}
	if ($drivers->{'bestLapTime'} == $bestMin){
		$bestLapTime = "<p style='color:purple;font-weight:bold;'>".$drivers->{'bestLapTime'}."</p>";
	}
	else{
		$bestLapTime = $drivers->{'bestLapTime'};
	}
	if ($drivers->{'lastLapTime'} == $bestMin){
		$lastLapTime = "<p style='color:purple;font-weight:bold;'>".$drivers->{'lastLapTime'}."</p>";
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
		if ($event->{'SessionType'} == "R"){ // This should cover racing for all road/street courses
			if (preg_match("/\.I|.L/", $event->{'preamble'})) {	//If it's an Indy Lights or ICS race
				print "<tr><td>" .$position. "</td><td>" .$driverName. "</td><td>" .$carNum. "</td><td>" .$lastLapTime. "</td><td>" .$diff2Lead. "</td><td>" .$gapAhead. "</td><td>" .$driverTire. "</td><td>" .$p2pRemain. "</td><td>" .$status. "</td></tr>";
			}
			else {
				print "<tr><td>" .$position. "</td><td>" .$driverName. "</td><td>" .$carNum. "</td><td>" .$lastLapTime. "</td><td>" .$diff2Lead. "</td><td>" .$gapAhead. "</td><td>" .$driverTire. "</td><td>" .$status. "</td></tr>";
			}
		}
		elseif ($event->{'SessionType'} == "Q"){ // This should cover qualification for all road/street courses
			if ($position == "7" and preg_match("/\.I/", $event->{'preamble'})){ //optionally: preg_match("/Q.\.I/", $event->{'preamble'})
				print "<tr><td colspan='100%' style='text-align: center;'>--- TRANSFER CUT OFF ---</td></tr>";
				print "<tr><td>" .$position. "</td><td>" .$driverName. "</td><td>" .$carNum. "</td><td>" .$lastLapTime. "</td><td>" .$bestLapTime. "</td><td>" .$driverTire. "</td><td>" .$status. "</td></tr>";
			}
			else{
				print "<tr><td>" .$position. "</td><td>" .$driverName. "</td><td>" .$carNum. "</td><td>" .$lastLapTime. "</td><td>" .$bestLapTime. "</td><td>" .$driverTire. "</td><td>" .$status. "</td></tr>";
			}
		}
		else{ //This should cover practice for all road/street courses
			print "<tr><td>" .$position. "</td><td>" .$driverName. "</td><td>" .$carNum. "</td><td>" .$lastLapTime. "</td><td>" .$bestLapTime. "</td><td>" .$driverTire. "</td><td>" .$status. "</td></tr>";
		}
	}
}
?>

	</tbody>
</table>
</div>
</html>