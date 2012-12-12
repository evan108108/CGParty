<?php
require 'vendor/autoload.php';

use React\Async\Util as Async;

///Users/evan.frolich/Sites/react/CGParty/
$WATCHED_DIR = 'media';
$METADATA_DIR = 'metadata';

$file_list = array();

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

createMediaTable();
mapDirContentsToDB();

$socket->on('connection', function ($conn) use ($loop){
	//$conn->pipe($conn);
	$loop->addPeriodicTimer(3, function() use ($conn) {
		$conn->write(getMedia());
	});
		
});


/* Get Semi Random Media to push over socket */
function getMedia()
{
	global $db;
	$results = $db->query('SELECT * FROM media ORDER BY number_of_views ASC, crt_dtm DESC LIMIT 20');
	$random = rand(0, 20);
	$cnt = 21;
	foreach($results as $result)
	{
		$cnt--;
		if($cnt != $random) continue;
		//$result = $results[rand(0, (count($results)-1))];
		$result['contents'] = json_decode($result['contents'], true);
		return json_encode($result);
	}
}


/* Create the in memory SQLite table to hold the media / metadata */
function createMediaTable()
{
	global $db;

	$sql = "CREATE TABLE IF NOT EXISTS `media` (
			id INTEGER PRIMARY KEY,
			name TEXT,
			type TEXT,
			text TEXT,
			contents TEXT,
			number_of_views INTEGER,
			lud_dtm INTEGER,
			crt_dtm INTEGER)";

	$db->exec($sql);
}

/* watch the dir for new images */
$loop->addPeriodicTimer(10, function() {
		mapDirContentsToDB();
});

/* Function maps the database to the media / metadata directories */
function mapDirContentsToDB()
{
	global $db;
	global $WATCHED_DIR;
	global $METADATA_DIR;
	global $file_list;

	$fileHandle = opendir($WATCHED_DIR);
	if($fileHandle === false) { 
		echo "WATCHED DIR IS INVALID \n"; exit();
	}
	while(false !== ($file = readdir($fileHandle)))
	{
		if($file !== '.' && $file !== '..' && $file != '.DS_Store' && !isset($file_list[$file]))
		{
			if(is_dir("$WATCHED_DIR/$file")) {
				$type = 'dir';
				$fileList = array();
				$dirHandle = opendir("$WATCHED_DIR/$file");
				while(false !== ($subfile = readdir($dirHandle)))
				{
					if($subfile !== '.' && $subfile !== '..' && $subfile != '.DS_Store')
						$fileList[] = $subfile;
				}
				$contents = json_encode($fileList);
			}
			else {
				$type = 'file';
				$contents = '';
			}
			
			$metadata_filename = explode('.', $file);
			$metadata_filename = $metadata_filename[0] . '.txt';
			if(file_exists("$METADATA_DIR/$metadata_filename")) $text = file_get_contents("$METADATA_DIR/$metadata_filename");
			else $text = "";

			$insert = "INSERT INTO media (name, number_of_views, type, text, contents, lud_dtm, crt_dtm) VALUES ('$file', 0, '$type', '$text', '$contents', " . time() . ", " . time() . ")";
			$stmt = $db->prepare($insert);
			$stmt->execute();
			$file_list[$file] = true;
			echo "$insert\n";
		}
	}
}


echo "Socket server listening on port 4000.\n";
echo "You can connect to it by running: telnet localhost 4000\n";

$socket->listen(4000);
$loop->run();
