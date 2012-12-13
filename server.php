<?php
require 'vendor/autoload.php';

date_default_timezone_set('UTC'); //Set the default timezone to UTC

//use React\Async\Util as Async;

///Users/evan.frolich/Sites/react/CGParty/
$SOCKET_PORT = 4000;
$WATCHED_DIR = 'media';
$METADATA_DIR = 'metadata';
$PUSH_TIMER = 3; //Time to push new media over the socket in seconds
$WATCH_DIR_TIMER = 10; //How often to check the WATCHED_DIR for changes in seconds
$RANDOM_WEIGHT = 20; //The higher the number the more random. This number must be the same size or smaller then the total number of images

$file_list = array(); //Keeps a list of all loaded files for quick comparison with WATCHED_DIR


file_put_contents('run.pid', getmypid()); //Saves the current PID into the file run.pid

$loop = React\EventLoop\Factory::create(); //Init the event loop class
$socket = new React\Socket\Server($loop); //Init the socket server class

$db = new PDO('sqlite::memory:'); //Init PDO SQLite into memory
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING); 

createMediaTable(); //Create the media table
mapDirContentsToDB(); //Insert the initial list of file into the media table

/* Create the socket */
$socket->on('connection', function ($conn) use ($loop, $PUSH_TIMER){
	$loop->addPeriodicTimer($PUSH_TIMER, function() use ($conn) {
		$media = getMedia(); //Get Semi-Random Media file to push through the socket
		$conn->write($media); //Push Media through socket to the client
		updateMediaViews($media); //Update the media table to reflect that this media item has been sent.
	});
});

/* watch the dir for new images */
$loop->addPeriodicTimer($WATCH_DIR_TIMER, function() {
		mapDirContentsToDB();
});

echo "Socket server listening on port 4000.\n";
echo "You can connect to it by running: telnet localhost 4000\n";

$socket->listen($SOCKET_PORT); //Start the socket lisining on port 400
$loop->run(); //Start the event loop



//Helper methods ----------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------

/* Get Semi-Random Media to push over socket */
function getMedia()
{
	global $db;  //Use global db instance
	global $RANDOM_WEIGHT; //Use the global random weight

	//Get a random item and pass back the result as JSON
	$results = $db->query("SELECT * FROM media ORDER BY number_of_views ASC, crt_dtm DESC LIMIT $RANDOM_WEIGHT");
	$random = rand(0, $RANDOM_WEIGHT);
	$cnt = $RANDOM_WEIGHT + 1;
	foreach($results as $result)
	{
		$cnt--;
		if($cnt != $random) continue;
		$result['contents'] = json_decode($result['contents'], true);
		
		return json_encode($result);
	}
}

/* Incriments the number_of_views on a media item */
function updateMediaViews($result)
{
	global $db; //Use global db instance

	$result = json_decode($result, true);
	$update = "UPDATE `media` SET number_of_views = '" . ($result['number_of_views'] + 1) . "' WHERE id = '" . $result['id'] . "'";

	$db->beginTransaction();
	$db->exec($update);
	$db->commit();

	return true;
}


/* Create the in memory SQLite table to hold the media / metadata */
function createMediaTable()
{
	global $db; //Use global db instance

	$sql = "CREATE TABLE IF NOT EXISTS `media` (
			id INTEGER PRIMARY KEY,
			name TEXT,
			type TEXT,
			text TEXT,
			contents TEXT,
			number_of_views INTEGER,
			lud_dtm INTEGER,
			crt_dtm INTEGER
		)";

	$db->exec($sql);
}


/* Function maps the database to the media / metadata directories */
function mapDirContentsToDB()
{
	global $db;  //Use global db instance
	global $WATCHED_DIR;  //Use global Watched Dir
	global $METADATA_DIR; //Use global Metadata Dir
	global $file_list; //Use global file_list

	//Loop the Watched Dir and log the contents in the media table
	$fileHandle = opendir($WATCHED_DIR);
	if($fileHandle === false) { 
		echo "WATCHED DIR IS INVALID \n"; exit();
	}
	while(false !== ($file = readdir($fileHandle)))
	{
		//Make sure the file is valid and is not already inserted in the db
		if($file !== '.' && $file !== '..' && $file != '.DS_Store' && !isset($file_list[$file]))
		{
			if(is_dir("$WATCHED_DIR/$file")) { //If the file is a dir we loop its contents and store that with this file
				$type = 'dir';
				$fileList = array();
				$dirHandle = opendir("$WATCHED_DIR/$file");
				while(false !== ($subfile = readdir($dirHandle)))
				{
					if($subfile !== '.' && $subfile !== '..' && $subfile != '.DS_Store')
						$fileList[] = sqlite_escape_string($subfile);
				}
				$contents = json_encode($fileList);
			}
			else {
				$type = 'file';
				$contents = '';
			}
			//Look and see if there is metadata assosiated with the media item
			$metadata_filename = explode('.', $file);
			$metadata_filename = $metadata_filename[0] . '.txt';
			if(file_exists("$METADATA_DIR/$metadata_filename")) $text = file_get_contents("$METADATA_DIR/$metadata_filename");
			else $text = "";
			
			//Insert media item in media table
			$insert = "INSERT INTO media (name, number_of_views, type, text, contents, lud_dtm, crt_dtm) VALUES ('" . sqlite_escape_string($file) . "', 0, '$type', '" . sqlite_escape_string($text) . "', '$contents', " . time() . ", " . time() . ")";
			$stmt = $db->prepare($insert);
			$stmt->execute();
			$file_list[$file] = true;
			echo "$insert\n";
		}
	}
}
//-------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------
