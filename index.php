<?php
/**
* @package Site status
* @author Rene Kreijveld, based on the original work of Watchful.li
* @authorUrl https://about.me/renekreijveld
* @copyright (c) 2014, Rene Kreijveld
*/

// Configure error reporting to maximum for CLI output.
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

//Config
define('API_KEY', 'Enter your Watchful.li API KEY here');
define('BASE_URL', 'https://watchful.li/api/v1');
define('LIMIT', '100');

// get base URL for refresh button
function getUrl() {
	$url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
	$url .= ( $_SERVER["SERVER_PORT"] !== 80 ) ? ":".$_SERVER["SERVER_PORT"] : "";
	$url .= $_SERVER["REQUEST_URI"];
	return $url;
}

// setup curl call, request json format
$ch = curl_init(BASE_URL . '/sites' . '?order=up,name&fields=published,up,name,access_url,ip,j_version&limit=' . LIMIT);
$options = array(
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_SSL_VERIFYPEER => false,
	CURLOPT_CUSTOMREQUEST => 'GET',
	CURLOPT_HTTPHEADER => array(
		'api_key: ' . API_KEY,
		'Content-type: application/json',
		'Accept: application/json'
	),
);
curl_setopt_array($ch, ($options));

// retrieve data
$watchful = json_decode(curl_exec($ch));
$watchfuldata = $watchful->msg;
$sitesdata = "";

// if no error proceed
if (!$watchful->error)
{
	$sitecount = $watchfuldata->total;
	$sitesdown = 0;
	$sites = $watchfuldata->data;

	// correct up status
	// 9 = down, will become 0
	// 8 = seems down, will become 1
	// 2 = up, stays 2
	foreach ($sites as $site)
	{
		if ($site->up == 9) $site->up = 0;
		if ($site->up == 8) $site->up = 1;
	}


	$cols = 2;
	// process all sites, build html list of sites
	foreach ($sites as $site)
	{
		if ($cols == 2)
		{
			$sitesdata .= "<div class=\"row\">";
			$cols = 0;
		}
		$sitesdata .= "<div class=\"col-md-6\">";

		// check if site is up
		if ($site->up == 2)
		{
			// show in green
			$color = "#449d44";
			$fa = "fa-check-circle";
		} else {
			// it's not up, so show in red
			$color = "#c9302c";
			$sitesdown++;
			$fa = "fa-times-circle";			
		}

		$sitesdata .= "<h2 style=\"color:$color\">$site->name <i class=\"fa $fa\"></i></h2>";

		$sitesdata .= "<p><i class=\"fa fa-home fa-fw\"></i><a href=\"$site->access_url\" target=\"_blank\">$site->access_url</a><br/>";

		if ($site->published == 1)
		{
			$sitesdata .= "<i class=\"fa fa-bolt fa-fw\"></i>$site->ip<br/>";
			$sitesdata .= "<i class=\"fa fa-joomla fa-fw\"></i>$site->j_version<br/>";			
		}

		$sitesdata .= "</p></div>";

		$cols++;

		if ($cols == 2) $sitesdata .= "</div>";
	}
	if ($cols == 1)
	{
		$sitesdata .= "</div>";
	}
	$pctdown = round($sitesdown / $sitecount * 100);
	$pctup = 100 - $pctdown;
}
else
{
	$sitecount = -1;
}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="">
		<meta name="author" content="Rene Kreijveld">

		<title>Websites Status</title>

		<!-- Bootstrap core CSS -->
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">

		<!-- Font Awesome -->
		<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">

		<style type="text/css">
			.jumbotron {padding: 16px 0}
			.jumbotron h1 {margin-top: 10px}
			@media (max-device-width: 480px) {h2 {font-size: 24px;}}
		</style>

		<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
			<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>

	<body>

		<div class="jumbotron">
			<div class="container">
				<h1>Websites Status</h1>
				<?php if ($sitecount == -1) { ?>
				<p>There was a problem retrieving websites status. Please try again later.</p>
				<?php } else { ?>
				<p>Number of websites: <strong><?php echo $sitecount; ?></strong>, currently we have <strong><?php echo $pctup;?>%</strong> up.</p>
				<div class="progress">
					<div class="progress-bar progress-bar-success" style="width: <?php echo $pctup;?>%">
						<span class="sr-only"><?php echo $pctup;?>% Up</span>
					</div>
					<div class="progress-bar progress-bar-danger" style="width: <?php echo $pctdown;?>%">
						<span class="sr-only"><?php echo $pctdown;?>% Down</span>
					</div>
				</div>
				<?php } ?>
				<p><a class="btn btn-primary btn-lg" href="<?php echo getUrl();?>" role="button">Refresh page</a></p>
			</div>
		</div>

		<div class="container">

			<?php echo $sitesdata; ?>

			<hr>

			<footer>
				<p style="font-size: 12px;">Data collected <i class="fa fa-calendar"></i> <?php echo date("Y-m-d"); ?> <i class="fa fa-clock-o"></i> <?php echo date("H:i:s"); ?>, written by <a href="http://about.me/renekreijveld" target="_blank">René Kreijveld</a>. All data collected through <a href="https://watchful.li/support-services/kb/article/watchful-rest-api" target="_blank">Watchful REST API</a>.</p>
			</footer>
		</div> <!-- /container -->

	</body>
</html>