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
 *                                        Bar Functions
 **************************************************************************************************/
	function getBarData($school_id) {
		$result = mysql_query("SELECT * FROM bars WHERE school_id=$school_id");
		while ($row = mysql_fetch_array($result)) {
			$bar['id'] = $row['id'];
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
	
	function getEventBars($id) {
		$result = mysql_query("SELECT * FROM event_bars where event_id=$id ORDER BY datetime ASC");
		$barsCount = 0;
		while ($row = mysql_fetch_array($result)) {
			$barsArray[$barsCount][0] = $row['bar_id'];
			$barsArray[$barsCount][1] = $row['datetime'];
			$barsCount++;
		}
		return $barsArray;
	}


/***************************************************************************************************
 *                                        Event Functions
 **************************************************************************************************/
	function getEventDetail($id) {
		$result = mysql_query("SELECT * FROM events WHERE id=$id");
		$row = mysql_fetch_array($result);
		$event = new Event();
		$event->id = $row['id'];
		$event->creatorid = $row['creatorid'];
		$event->date = $row['date'];
		$event->title = $row['title'];
		$event->description = $row['description'];
		$event->picture = $row['picture'];
		$event->privacy = $row['privacy'];
		return $event;
	}

	function getEventData() {
		$result = mysql_query("SELECT * FROM events");
		$eventCount = 0;
		while ($row = mysql_fetch_array($result)) {
			$eventArray[$eventCount][0] = $row['id'];
			$eventArray[$eventCount][1] = $row['creatorid'];
			$eventArray[$eventCount][2] = $row['date'];
			$eventArray[$eventCount][3] = $row['title'];
			$eventArray[$eventCount][4] = $row['description'];
			$eventArray[$eventCount][5] = $row['picture'];
			$eventArray[$eventCount][6] = $row['privacy'];
			$eventCount++;
		}
		return $eventArray;
	}
	
	function getEventDataForUser() {
		$authLibrary = new FacebookAuthentication();
		$userid = $authLibrary->getUserID();
		 
		$result = mysql_query("SELECT * FROM events INNER JOIN event_friends ON events.id=event_friends.event_id WHERE event_friends.friend_id=".$userid);
		$eventCount = 0;
		while ($row = mysql_fetch_array($result)) {
			$eventArray[$eventCount][0] = $row['id'];
			$eventArray[$eventCount][1] = $row['creatorid'];
			$eventArray[$eventCount][2] = $row['date'];
			$eventArray[$eventCount][3] = $row['title'];
			$eventArray[$eventCount][4] = $row['description'];
			$eventArray[$eventCount][5] = $row['picture'];
			$eventArray[$eventCount][6] = $row['privacy'];
			$eventCount++;
		}
		return $eventArray;
	}

	function getEventsDataCreatedByUser(){
		$authLibrary = new FacebookAuthentication();
		$userid = $authLibrary->getUserID();
		
		$result = mysql_query("SELECT * FROM events WHERE creatorid =".$userid);
		$eventCount = 0;
		while ($row = mysql_fetch_array($result)) {
			$eventArray[$eventCount][0] = $row['id'];
			$eventArray[$eventCount][1] = $row['creatorid'];
			$eventArray[$eventCount][2] = $row['date'];
			$eventArray[$eventCount][3] = $row['title'];
			$eventArray[$eventCount][4] = $row['description'];
			$eventArray[$eventCount][5] = $row['picture'];
			$eventArray[$eventCount][6] = $row['privacy'];
			$eventCount++;
		}
		return $eventArray;
	}

	function getEventWithID($eventID) {
		$result = mysql_query("SELECT * FROM events WHERE id=$eventID");
		$eventCount = 0;
		while ($row = mysql_fetch_array($result)) {
			$eventArray[$eventCount][0] = $row['id'];
			$eventArray[$eventCount][1] = $row['creatorid'];
			$eventArray[$eventCount][2] = $row['date'];
			$eventArray[$eventCount][3] = $row['title'];
			$eventArray[$eventCount][4] = $row['description'];
			$eventArray[$eventCount][5] = $row['picture'];
			$eventArray[$eventCount][6] = $row['privacy'];
			$eventCount++;
		}
		return $eventArray;
	}

	function getEventDataForID($id) {
		$result = mysql_query("SELECT * FROM events INNER JOIN event_friends ON events.id=event_friends.event_id WHERE event_friends.friend_id=".$id." ORDER BY date ASC");
		$eventCount = 0;
		while ($row = mysql_fetch_array($result)) {
			$eventArray[$eventCount][0] = $row['id'];
			$eventArray[$eventCount][1] = $row['creatorid'];
			$eventArray[$eventCount][2] = $row['date'];
			$eventArray[$eventCount][3] = $row['title'];
			$eventArray[$eventCount][4] = $row['description'];
			$eventArray[$eventCount][5] = $row['picture'];
			$eventArray[$eventCount][6] = $row['privacy'];
			$eventCount++;
		}
		return $eventArray;
	}


/***************************************************************************************************
 *                                          Event Creation
 **************************************************************************************************/
	function createNewEvent($id, $creatorid, $date, $title, $description, $picture, $privacy) {
		print $id;
		$values = $id.", ".$creatorid.", '".$date."', '".$title."', '".$description."', '".$picture."', '".$privacy."'";
		$result = mysql_query("INSERT INTO events (id, creatorid, date, title, description, picture, privacy) VALUES (".$values.")");
		print $result;
		return $result;
	}

	function addGuestsToEvent($id,$friends) {
		foreach ($friends as $friendID) {
			$result = mysql_query("INSERT INTO event_friends (event_id, friend_id) VALUES (".$id.",".$friendID.")");
		}
	}
	

	function addBarsToEvent($id,$bars,$dateTimes) {
		for ($i = 0; $i < count($bars); $i++) {
			$time = date_parse($dateTimes[$i]);
			$time = $time["hour"] . ":" . $time["minute"] . ":" . $time["second"];
			$result = mysql_query("INSERT INTO event_bars (event_id, bar_id, time, datetime) VALUES (".$id.",".$bars[$i].",'".$time."','".$dateTimes[$i]."')");
		}
	}

	
/***************************************************************************************************
 *                                        Helper Functions
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