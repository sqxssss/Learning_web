<?php
session_start();
$_SESSION['gen'] = 0;
?>
<html>
<head>
	<!-- php5 -->
	<script src="https://cdn.jsdelivr.net/npm/jquery@1.12.4/dist/jquery.min.js"></script>
</head>
<body>
	<div>
		<p>抽卡次数: <input type="number" name="times" placeholder=0></p>
		<p><input type="submit" value="抽卡!" onclick=random()></p>
	</div>
	<div name="show">
	</div>
	<div>
		<p>cdkey: <input type="text" name="verify" placeholder=0></p>
		<p><input type="submit" value="兑换" onclick=verify()></p>
	</div>
</body>
<script>
	function random() {
		var times = $("input[name='times']").val();
		$.get("random.php?times="+times, 
		function(data) {
			if(data) {
				var show = $("div[name='show']")
				show.empty()
				show.append(data)
			}
		})
	}
	
	function verify() {
		var verify = $("input[name='verify']").val();
		$.post("verify.php", {
			"ans": verify
		},
		function(data) {
			if(data) {
				alert(data)
			}
		})
	}
</script>
</html>