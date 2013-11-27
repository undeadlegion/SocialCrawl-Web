<?php
/*
 * This file will take data from the MySQL backend and publish it as XML.
 * Serves as an connection between the iPhone and backend MySQL database.
 * 
 */

include_once('DatabaseInteraction.php');


$request_method = strtolower($_SERVER['REQUEST_METHOD']);
error_log("Request Method: $request_method");
if ($request_method === 'post') {
	ob_start();
	// decode POSTed json
	$input = file_get_contents("php://input");
	$createdEvent = json_decode($input);

	// get token from auth header
	$access_token = getAccessToken();
	
	// extract event parameters
	$creator_id = $createdEvent->creator_id;
	$name = $createdEvent->name;
	$description = $createdEvent->description;
	$start_time = $createdEvent->start_time;
	$final_time = NULL;
	$invited_guests = $createdEvent->invited_guests;
	$selected_bars = json_decode(json_encode($createdEvent->selected_bars), true);
	echo "\nSELECTED BARS:".var_export($selected_bars, true)."\n";
	echo "\nINVITED GUESTS:".var_export($invited_guests, true)."\n";
	
	// TESTING
	$privacy_type = "SECRET";
	$image = NULL;
	$location_id = 12;


	// Facebook
	$event_id = createEvent($access_token, $creator_id, $name, $description, $start_time, $final_time, '', $privacy_type);
	$invited = inviteGuests($access_token, $event_id, $invited_guests);
	// $event_id = 123456;
	// $invited = true;

	// Database
	$db = new DatabaseInteraction();
	$db_created = $db->addNewEvent($event_id, $creator_id, $location_id, $start_time, $name, $description, $image, $privacy_type);
	$db_bars = $db->addBarsToEvent($event_id, $selected_bars);

	$invited_guests[] = $creator_id;	
	$db_invited = $db->addGuestsToEvent($event_id, $invited_guests);
	

	// print debug info
	echo "\nToken:\n$access_token\n";
	echo "\nJSON:\n$input\n";
	echo "\nCreated Event:".var_export($createdEvent,true)."\n";
	echo "Created FB event:$event_id\n";
	echo "Invited FB guests:".var_export($invited, true)."\n";
	echo "Created in DB:".var_export($db_created, true)."\n";
	echo "Bars added to DB:".var_export($db_bars, true)."\n";
	echo "Guests added to DB:".var_export($db_invited, true)."\n";

	$contents = ob_get_contents();
	ob_end_clean();
	error_log($contents);
	return;
} else if ($request_method === 'get') {
	$type = isset($_GET['type']) ? $_GET['type'] : '';
	$id = isset($_GET['id']) ? $_GET['id'] : '';
	$uid = isset($_GET['uid']) ? $_GET['uid'] : '';
	$db = new DatabaseInteraction();
}

if ($type == "bars") {
	$bars = $db->getBarsForLocation($id);
	printBarsXML($bars);

} elseif ($type == "events") {
	$events = $db->getAllEvents();
	printEventsXML($events);

} elseif ($type == "eventsforid") {
	$events = $db->getEventsForId($id);
	$db->linkUserToFB($uid, $id);
	printEventsXML($events);	

} elseif ($type == "eventwithid") {
	$events = $db->getEventWithId($id);
	$db->addNewUser($uid);
	printEventsXML($events);

}elseif ($type == "eventwithshortid") {
	$events = $db->getEventWithShortId($id);
	$db->addNewUser($uid);
	printEventsXML($events);
}  
elseif ($type == "barsforevent") {
	$barsForEvent = $db->getBarsForEvent($id);
	printBarsForEventXML($barsForEvent);	
	
} elseif ($type == "specialsforevent"){
	$specials = $db->getEventSpecialsForEvent($id);
	printSpecialsXML($specials);

} elseif ($type == "feedback") {
	$db->insertFeedback($id, $message);

	$root = $XMLDoc->createElement('Feedback');
	$root = $XMLDoc->appendChild($root);
	print $XMLDoc->saveXML(); 

} elseif ($type == "deleteevents") {
	header('Content-type: text/plain');

	error_log("Deleting events for $id\n");
	echo("Deleting events for $id\n");
	$createdEvents = $db->getCreatedEvents($id);
	// $access_token = getAccessToken();
	$access_token = "CAAIRSSbUNo0BAGJzkZBTRaXK3KFtbOsfDYuNXZA81gZAmbhP2jqk9bTZCkZBOe6iXFh25ZBkSFJuv0jORYR9r11KOeQhhEfg02g2mcHUxFxV6GfLUHJAlVQmdzs2OB1CyqErFSZAnUeZCYZBAwwz152vPN6FljEqssgcocURCvSgwL3D8nSzMdso7g9ZAOuiN0UVbvrwUZCzEdZBPsR6BTjAnOIv";
	
	foreach ($createdEvents as $event) {
		$event_id = $event['event_id'];
		$fb_result = cancelEvent($access_token, $event_id);
		$db_result = false;
		if ($fb_result) {
			$db_result = $db->deleteEvent($event_id);
		}
		error_log("Event:$event_id\n\tFB:$fb_result\n\tDB:$db_result\n");
		echo("Event:$event_id\n\tFB:$fb_result\n\tDB:$db_result\n");
	}

} elseif ($type == "deletefbevent") {
	$access_token = "CAAIRSSbUNo0BAGJzkZBTRaXK3KFtbOsfDYuNXZA81gZAmbhP2jqk9bTZCkZBOe6iXFh25ZBkSFJuv0jORYR9r11KOeQhhEfg02g2mcHUxFxV6GfLUHJAlVQmdzs2OB1CyqErFSZAnUeZCYZBAwwz152vPN6FljEqssgcocURCvSgwL3D8nSzMdso7g9ZAOuiN0UVbvrwUZCzEdZBPsR6BTjAnOIv";
	cancelEvent($access_token, $id);
}

function printEventsXML($events) {
	header('Content-type: text/xml');
	$XMLDoc = new DOMDocument('1.0');
	$XMLDoc->formatOutput = true;
	$root = $XMLDoc->createElement('Events');
	$root = $XMLDoc->appendChild($root);
	
	foreach ($events as $event) {
		
		$eventElement = $XMLDoc->createElement('Event');
		$eventElement = $root->appendChild($eventElement);
		$eventElement->setAttribute('id', $event['event_id']);
		
		$creatorElement = $XMLDoc->createElement('creatorid', $event['creator_id']);
		$dateElement = $XMLDoc->createElement('date', $event['start_time']);
		$titleElement = $XMLDoc->createElement('title', $event['name']);
		$descriptionElement = $XMLDoc->createElement('description', $event['description']);
		$pictureElement = $XMLDoc->createElement('picture', $event['image']);
		$privacyElement = $XMLDoc->createElement('privacytype', $event['privacy_type']);
		
		$eventElement->appendChild($creatorElement);
		$eventElement->appendChild($dateElement);
		$eventElement->appendChild($titleElement);
		$eventElement->appendChild($descriptionElement);
		$eventElement->appendChild($pictureElement);
		$eventElement->appendChild($privacyElement);
	}
	print $XMLDoc->saveXML();
}

function printBarsForEventXML($barsForEvent) {
	header('Content-type: text/xml');
	$XMLDoc = new DOMDocument('1.0');
	$XMLDoc->formatOutput = true;
	$root = $XMLDoc->createElement('EventBars');
	$root = $XMLDoc->appendChild($root);
	
	foreach ($barsForEvent as $bar) {
		error_log("Found bar:".$bar['bar_id']."\n");
		$barElement = $XMLDoc->createElement('Bar');
		$barElement = $root->appendChild($barElement);
		$barElement->setAttribute('id',$bar['bar_id']);

		$timeElement = $XMLDoc->createElement('time',$bar['start_time']);
		$barElement->appendChild($timeElement);
	}
	print $XMLDoc->saveXML();
}

function printBarsXML($bars) {
	header('Content-type: text/xml');
	$XMLDoc = new DOMDocument('1.0');
	$XMLDoc->formatOutput = true;
	$root = $XMLDoc->createElement('Bars');
	$root = $XMLDoc->appendChild($root);
	
	foreach ($bars as $bar) {
		
		$barElement = $XMLDoc->createElement('Bar');
		$barElement = $root->appendChild($barElement);
		$barElement->setAttribute('id',$bar['bar_id']);
		
		$nameElement = $XMLDoc->createElement('name',htmlentities($bar['name']));
		$addressElement = $XMLDoc->createElement('address',$bar['address']);
		$descriptionElement = $XMLDoc->createElement('description',htmlentities($bar['description']));
		$quickElement = $XMLDoc->createElement('quicklogo',$bar['quick_logo']);
		$detailedElement = $XMLDoc->createElement('detailedlogo', $bar['detailed_logo']);
		$longitudeElement = $XMLDoc->createElement('longitude', $bar['longitude']);
		$latitudeElement = $XMLDoc->createElement('latitude', $bar['latitude']);
		
		$barElement->appendChild($nameElement);
		$barElement->appendChild($addressElement);
		$barElement->appendChild($descriptionElement);
		$barElement->appendChild($quickElement);
		$barElement->appendChild($detailedElement);
		$barElement->appendChild($longitudeElement);
		$barElement->appendChild($latitudeElement);
	}	
	print $XMLDoc->saveXML();
}

function printSpecialsXML($specials) {
	header('Content-type: text/xml');
	$XMLDoc = new DOMDocument('1.0');
	$XMLDoc->formatOutput = true;
	$root = $XMLDoc->createElement('BarSpecials');
	$root = $XMLDoc->appendChild($root);
	foreach( $specials as $bar){
		$barSpecial = $XMLDoc->createElement('BarSpecial');
		$barSpecial = $root->appendChild($barSpecial);
		$barSpecial->setAttribute('id', $bar['bar_id']);
		$special = $XMLDoc->createElement('specials', htmlentities($bar['specials']));
		$barSpecial->appendChild($special);
	}
	print $XMLDoc->saveXML();
}

function inviteGuests($access_token, $event_id, $users) {
	$url = 'https://graph.facebook.com/'.$event_id.'/invited/?access_token='.$access_token;
	$post_fields = "users=";
	$last = count($users) - 1;

	foreach ($users as $key => $user) {
		$post_fields = ($key === $last) ? $post_fields.$user : $post_fields.$user.',';
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = json_decode(curl_exec($ch));
	curl_close($ch);
	
	// print debug info
	echo "FBGraph Result:".var_export($result, true)."\n";

	return $result;
}

function createEvent($access_token, $creator_id, $name, $description, $start_time, $final_time, $location, $privacy_type) {		
	$url = 'https://graph.facebook.com/'.$creator_id.'/events/?access_token='.$access_token;
	$post_fields = 'name='.$name.'&description='.$description.'&start_time='.$start_time.'&final_time='.$final_time.'&location='.$location.'&privacy_type='.$privacy_type;
	
	$ch = curl_init();		
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = json_decode(curl_exec($ch));
	$event_id = $result->id;
	curl_close($ch);

	// print debug info
	echo "FBGraph Result:".var_export($result, true)."\n";

	return $event_id;
}

function cancelEvent($access_token, $event_id) {		
	$url = 'https://graph.facebook.com/'.$event_id.'/?access_token='.$access_token;
	
	$ch = curl_init();		
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = json_decode(curl_exec($ch));
	curl_close($ch);

	// print debug info
	echo "FBGraph Result:".var_export($result, true)."\n";

	return $result;
}

function getAccessToken() {
	$headers = apache_request_headers();
	if(isset($headers['Authorization'])){
		$matches = array();
	    preg_match('/Token token="(.*)"/', $headers['Authorization'], $matches);
	    if(isset($matches[1])){
	    	$access_token = $matches[1];
	    }
	}
	return $access_token;
}
?>
