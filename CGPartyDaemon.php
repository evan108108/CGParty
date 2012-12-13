#!/opt/local/bin/php -q
<?php
ini_set('display_errors',0);
print "Parent : ". getmypid() . "\n";

global $pids;
$pids = Array();

// Daemonize
$pid = pcntl_fork();
if($pid){
 // Only the parent will know the PID. Kids aren't self-aware
 // Parent says goodbye!
 print "\tParent : " . getmypid() . " exiting\n";
 exit();
}

print "Child : " . getmypid() . "\n";

// Handle signals so we can exit nicely
declare(ticks = 1);
function sig_handler($signo){
 global $pids,$pidFileWritten;
 if ($signo == SIGTERM || $signo == SIGHUP || $signo == SIGINT){
 // If we are being restarted or killed, quit all children

 // Send the same signal to the children which we recieved
 foreach($pids as $p){ posix_kill($p,$signo); } 

 // Women and Children first (let them exit)
 foreach($pids as $p){ pcntl_waitpid($p,$status); }
 print "Parent : "
 .  getmypid()
 . " all my kids should be gone now. Exiting.\n";
 exit();
 }else if($signo == SIGUSR1){
 print "I currently have " . count($pids) . " children\n";
 }
}
// setup signal handlers to actually catch and direct the signals
pcntl_signal(SIGTERM, "sig_handler");
pcntl_signal(SIGHUP,  "sig_handler");
pcntl_signal(SIGINT, "sig_handler");
pcntl_signal(SIGUSR1, "sig_handler");

// All the daemon setup work is done now. Now do the actual tasks at hand

// The program to launch
$program = "/opt/local/bin/php";
$arguments = Array("server.php");

while(TRUE){
 // In a real world scenario we would do some sort of conditional launch.
 // Maybe a condition in a DB is met, or whatever, here we're going to
 // cap the number of concurrent grandchildren
 if(count($pids) < 1){
	$pid=pcntl_fork();
	if(!$pid){
		 pcntl_exec($program,$arguments); // takes an array of arguments
		 exit();
	} else {
		// We add pids to a global array, so that when we get a kill signal
		// we tell the kids to flush and exit.
		$pids[] = $pid;
	}
 }

 // Collect any children which have exited on their own. pcntl_waitpid will
 // return the PID that exited or 0 or ERROR
 // WNOHANG means we won't sit here waiting if there's not a child ready
 // for us to reap immediately
 // -1 means any child
 $dead_and_gone = pcntl_waitpid(-1,$status,WNOHANG);
 while($dead_and_gone > 0){
 // Remove the gone pid from the array
 unset($pids[array_search($dead_and_gone,$pids)]); 

 // Look for another one
 $dead_and_gone = pcntl_waitpid(-1,$status,WNOHANG);
 }

 // Sleep for 1 second
 sleep(5);
}
