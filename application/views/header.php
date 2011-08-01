<body onload="initialize();">
<div id="oc">
	<div id="ic">
		<div id="header">
			<h1>TweetTrack <?php if (isset($report_query)){echo $report_query;}?></h1>
			<ul id="nav">
				<?php if (isset($home_link)){echo $home_link;}?>
				<?php if (isset($report_link)){echo $report_link;}?>
				<?php if (isset($run_report_link)){echo $run_report_link;}?>
			</ul>
			<?php if (isset($message)){ ?><div id="message"><?php echo $message;?></div><?php }?>
		