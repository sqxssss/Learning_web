<?php
session_start();
if(!isset($_SESSION['seed']) || $_SESSION['gen'] == 1) {
	mt_srand(time()+20271124);
	$_SESSION['seed'] = mt_rand();
	$_SESSION['time'] = time();
	$_SESSION['gen'] = 0;
}

if(isset($_SESSION['seed']) && $_SESSION['gen'] == 0) {
	if(isset($_GET['times'])) {
		$times = intval($_GET['times']);
		$res = [];
		if($times > 777) {
			die("too much");
		} else if($times < 0) {
			die("too small");
		}
		mt_srand($_SESSION['seed']);
		for($i=0;$i<$times;$i++) {
			$res[$i] = mt_rand();
		}
		echo json_encode($res);
		$_SESSION['gen'] = 1;
	}
}
?>