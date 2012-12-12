<?php
require 'vendor/autoload.php';

use React\Async\Util as Async;

$WATCHED_DIR = 'media';
$file_list = array();

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

createMediaTable();

$socket->on('connection', function ($conn) use ($loop){
	//$conn->pipe($conn);
	$loop->addPeriodicTimer(3, function() use ($conn) {
		$conn->write(rand(100, 100000));
	});
		
});

function createMediaTable()
{
	global $db;

	$sql = "CREATE TABLE IF NOT EXISTS `media` (
			id INTEGER PRIMARY KEY,
			name TEXT,
			number_of_views INTEGER,
			lud_dtm INTEGER,
			crt_dtm INTEGER)";

	$db->exec($sql);
}

function mapDirContentsToDB()
{
	global $WATCH_DIR;
	global $file_list;

	$fileHandle = opendir($WATCH_DIR);
	while($file = readdir($fileHandle))
	{
		if($file !== '.' && $file !== '..' && $file != '.DS_Store' && !isset($file_list[$file]))
		{
			$insert = "INSERT INTO media (name, number_of_views, lud_dtm, crt_dtm) VALUES ('$file', 0, " . time() . ", " . time() . ")";
		}
	}
}


echo "Socket server listening on port 4000.\n";
echo "You can connect to it by running: telnet localhost 4000\n";

$socket->listen(4000);
$loop->run();
