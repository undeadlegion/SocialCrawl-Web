<?php

//include_once('../FacebookInteraction/FacebookInteraction.php');
include_once(dirname(__FILE__) . '/../Event.php');

/*
 * Utility class for interaction with MySQL database
 * mainly will consist of helper functions for commonly requested data
 *
 */

class DatabaseInteraction {

	function __construct() {
		$url = "localhost";
		$dbname = "illini_crawler";
		$user = "illini_crawler";
		$pw = "illini_crawler";

		mysql_connect($url,$user,$pw);
		@mysql_select_db($dbname) or die("Cannot connect to database!");
	}

/***************************************************************************************************
 *                                        Bars Fetching
 **************************************************************************************************/
	function getBarsForLocation($location_id) {
		$result = mysql_query("SELECT * FROM bars WHERE location_id=$location_id");
		$barArray = array();

		while ($row = mysql_fetch_array($result)) {
			$bar = array();
			$bar['bar_id'] = $row['bar_id'];
			$bar['location_id'] = $row['location_id'];
			$bar['name'] = $row['name'];
			$bar['address'] = $row['address'];
			$bar['description'] = $row['description'];
			$bar['website'] = $row['website'];
			$bar['quick_logo'] = $row['quick_logo'];
			$bar['detailed_logo'] = $row['detailed_logo'];
			$bar['longitude'] = $row['longitude'];
			$bar['latitude'] = $row['latitude'];
			$barArray[] = $bar;
		}
		return $barArray;
	}
	
	function getBarsForEvent($event_id) {
		$result = mysql_query("SELECT * FROM event_bars WHERE event_id=$event_id ORDER BY start_time ASC");
		$barArray = array();
		
		while ($row = mysql_fetch_array($result)) {
			$bar = array();
			$bar['bar_id'] = $row['bar_id'];
			$bar['location_id'] = $row['location_id'];
			$bar['start_time'] = $row['start_time'];
			$bar['specials'] = $this->getBarSpecials($bar);
			$barArray[] = $bar;
		}
		return $barArray;
	}

	function getBarSpecials($bar) {
		$bar_id = $bar['bar_id'];
		$location_id = $bar['location_id'];
		$query = "SELECT * FROM bar_specials WHERE bar_id=$bar_id AND location_id=$location_id";
		$result = mysql_query($query);
		if (!$result) {
			error_log("Invalid query: $query\nError:".mysql_error()."\n");
		}
		$row = mysql_fetch_array($result);
		$specials = $row['specials'];
		return $specials;
	}

/***************************************************************************************************
 *                                        Events Fetching
 **************************************************************************************************/

	function getAllEvents() {
		$eventArray = $this->getEventWithId(" '' OR 1");
		return $eventArray;
	}

	function getEventWithId($event_id) {
		$result = mysql_query("SELECT * FROM events WHERE event_id=$event_id");
		$eventArray = $this->fetchEventArray($result);
		return $eventArray;
	}

	function getEventWithShortId($short_id) {
		$result = mysql_query("SELECT * FROM events WHERE short_id=$short_id");
		$eventArray = $this->fetchEventArray($result);
		return $eventArray;
	}

	function getEventsForId($guest_id) {
		$result = mysql_query("SELECT * FROM events INNER JOIN event_guests ON events.event_id=event_guests.event_id WHERE event_guests.guest_id=$guest_id ORDER BY start_time ASC");
		$eventArray = $this->fetchEventArray($result);
		return $eventArray;
	}

	function getCreatedEvents($creator_id) {
		$result = mysql_query("SELECT * FROM events WHERE creator_id=$creator_id ORDER BY start_time ASC");
		$eventArray = $this->fetchEventArray($result);
		return $eventArray;		
	}

	function getEventSpecials($event_id) {
		$bars = $this->getBarsForEvent($event_id);
		$specialsArray = array();
		foreach ($bars as $bar) {
			$specials = $this->getBarSpecials($bar);
			$bar['specials'] = $specials;
			$specialsArray[] = $bar;
		}
		return $specialsArray;
	}
/***************************************************************************************************
 *                                          Event Creation
 **************************************************************************************************/
	function addNewEvent($event_id, $creator_id, $location_id, $start_time, $name, $description, $image, $privacy_type) {
		$values = "$event_id, $creator_id, $location_id, '$start_time', '$name', '$description', '$image', '$privacy_type'";
		$query = "INSERT INTO events (event_id, creator_id, location_id, start_time, name, description, image, privacy_type) VALUES ($values)";
		$result = mysql_query($query);
		if (!$result) {
			error_log("Invalid query: $query\nError:".mysql_error()."\n");
		}
		return $result;
	}

	function addGuestsToEvent($event_id, $guests) {
		$result = true;
		foreach ($guests as $guest_id) {
			$query = "INSERT INTO event_guests (event_id, guest_id) VALUES ($event_id, $guest_id)";
			$result = $result && mysql_query($query);
		}
		if (!$result) {
			error_log("Invalid query: $query\nError:".mysql_error()."\n");
		}
		return $result;
	}

	function addBarsToEvent($event_id, $bars) {
		$result = true;
		foreach ($bars as $bar) {
			$bar_id = $bar['bar_id'];
			$start_time = $bar['start_time'];
			$location_id = $bar['location_id'];
			$query = "INSERT INTO event_bars (event_id, bar_id, location_id, start_time) 
					  VALUES ($event_id, $bar_id, $location_id, '$start_time')
					  ON DUPLICATE KEY UPDATE start_time='$start_time'";
			$result = $result && mysql_query($query);

		}
		if (!$result) {
			error_log("Invalid query: $query\nError:".mysql_error()."\n");
		}
		return $result;
	}
	function editBarTimes($event_id, $bars) {
		$editedBar = $bars[0];

		error_log("EDITBARTIMES".var_dump($editedBar)."\n");
		$start_time = $editedBar['start_time'];
		error_log("start time:$start_time\n");
		$edited_time = $editedBar['edited_time'];
		$bar_id = $editedBar['bar_id'];
		$location_id = $editedBar['location_id'];

		$query = "UPDATE event_bars
			      SET start_time='$edited_time'
			      WHERE event_id='$event_id'
			      	AND bar_id=$bar_id
			      	AND location_id=$location_id
			      	AND start_time='$start_time'";
		error_log("\nQuery:\n$query\n");
		$result = mysql_query($query);

		if (!$result) {
			error_log("Invalid query: $query\nError:".mysql_error()."\n");
		}
		return $result;
	}
/***************************************************************************************************
 *                                          Deleting Event
 **************************************************************************************************/
	function deleteUserCreatedEvents($creator_id) {
		$query = "DELETE e1, e2, e3 FROM events e1
			INNER JOIN event_guests e2 ON e1.event_id=e2.event_id
			INNER JOIN event_bars e3 ON e1.event_id=e3.event_id
			WHERE e1.creator_id=$creator_id";
		$result = mysql_query($query);
		if (!$result) {
			error_log("Invalid query: $query\nError:".mysql_error()."\n");
		}
		return $result;
	}

	function deleteEvent($event_id) {
		$query = "DELETE e1, e2, e3 FROM events e1
			INNER JOIN event_guests e2 ON e1.event_id=e2.event_id
			INNER JOIN event_bars e3 ON e1.event_id=e3.event_id
			WHERE e1.event_id=$event_id";
		$result = mysql_query($query);
		if (!$result) {
			error_log("Invalid query: $query\nError:".mysql_error()."\n");
		}
		return $result;
	}

/***************************************************************************************************
 *                                        Helper Functions
 **************************************************************************************************/
	function fetchEventArray($mysql_result) {
		$eventArray = array();
		while ($row = mysql_fetch_array($mysql_result)) {
			$event = array();
			$event['event_id'] = $row['event_id'];
			$event['creator_id'] = $row['creator_id'];
			$event['start_time'] = $row['start_time'];
			$event['name'] = $row['name'];
			$event['description'] = $row['description'];
			$event['image'] = $row['image'];
			$event['privacy_type'] = $row['privacy_type'];
			$eventArray[] = $event;
		}
		return $eventArray;
	}
	function addNewUser($uid) {
		$query = "INSERT INTO users (user_id) VALUES ('$uid')";
		$result = mysql_query($query);

		if (!$result) {
			error_log("Invalid query: $query\nError:".mysql_error()."\n");
		}
		return $result;
	}
	function linkUserToFB($uid, $fb_id) {
		$query = "UPDATE users
			      SET fb_id = $fb_id
			      WHERE user_id = '$uid'";
		$result = mysql_query($query);

		if (!$result) {
			error_log("Invalid query: $query\nError:".mysql_error()."\n");
		}
		return $result;

	}
/***************************************************************************************************
 *                                       Not Used
 **************************************************************************************************/
	function editEventNameDB($eventID,$newTitle){
		mysql_query("UPDATE events
					SET title = '$newTitle'
					WHERE id = $eventID");
	}

	function editEventDescriptionDB($eventID,$newDescription){
		mysql_query("UPDATE events
					SET description = '$newDescription'
					WHERE id = $eventID");
	}
	
	function editEventDateDB($eventID,$newDate){
		mysql_query("UPDATE events
					SET date = '$newDate'
					WHERE id = $eventID");
	}

	function getEventDataForUser() { 
		error_log("Error:DB function deprecated\n");
	}
	function getEventsDataCreatedByUser(){
		error_log("Error:DB function deprecated\n");
	}
	function getEventDetail($id) {
		error_log("Error:DB function deprecated\n");
	}

	// Move out of DB Interaction
	function getCurrentUserID(){
		$authLibrary = new FacebookAuthentication();
		$userid = $authLibrary->getUserID();
		return $userid;
	}
	// Move out of DB Interaction
	function getFacebookComments($eventID){
			$facebookLibrary = new FacebookEvents();
			$comments = $facebookLibrary->getEventComments($eventID, 0);

			$allComments = array();

			for ($curComment = 0; $curComment < count($comments); $curComment++){

				$newComment = new EventComment();

				$commentDetails 			= $comments[$curComment];
				$newComment->ID 			= $curComment; // for now
				$newComment->UserID 		= $commentDetails->posterID;
				$newComment->postTime 		= $commentDetails->postTime;
				$newComment->commentText 	= $commentDetails->message;

				$allComments[] = $newComment;
					

			}
		return $allComments;
	}

}
?>