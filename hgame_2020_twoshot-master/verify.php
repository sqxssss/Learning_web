<?php
error_reporting(0);
session_start();
if(isset($_POST['ans'])) {
	if(intval($_POST['ans']) === $_SESSION['seed'] && time() - $_SESSION['time'] < 1) {
		echo 'flag{xxxxxxx}';
	} else {
		echo 'wrong answer or too slow (solve it in 1 seconds)';
		session_destroy();
	}
}