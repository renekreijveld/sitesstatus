<?php
/**
* @package Site status
* @author Rene Kreijveld, based on the original work of Watchful.li
* @authorUrl https://www.gakijken.nl
* @copyright (c) 2014, DSD business internet
*/

// Configure error reporting to maximum for CLI output.
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

//Config
define('API_KEY', 'enter your Watchfil.li API KEY here');
define('BASE_URL', 'https://watchful.li/api/v1');
define('LIMIT', '100');

// get base URL for refresh button
function getUrl() {
	$url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
	$url .= ( $_SERVER["SERVER_PORT"] !== 80 ) ? ":".$_SERVER["SERVER_PORT"] : "";
	$url .= $_SERVER["REQUEST_URI"];
	return $url;
}

// function for array sorting
function cmp($a, $b) {
	return strcmp($a->up.$a->name, $b->up.$b->name);
}

// setup curl call, request json format
$ch = curl_init(BASE_URL . '/sites' . '?limit=' . LIMIT);
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
$watchfuldata = json_decode(curl_exec($ch));
$watchfuldata = $watchfuldata->msg;
$sitesdata = "";

// if no error proceed
if (!$watchfuldata->error)
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

	// sort on upstatus and name
	usort($sites, "cmp");

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

		// site is down
		if ($site->up == 0)
		{
			$color = "#c9302c";
			$sitesdown++;
			$fa = "fa-times-circle";
		}

		// site seems down
		if ($site->up == 1)
		{
			$color = "#ec971f";
			$sitesdown++;
			$fa = "fa-times-circle";
		}

		// site is up
		if ($site->up == 2)
		{
			$color = "#449d44";
			$fa = "fa-check-circle";
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
		<meta name="author" content="DSD business internet">

		<title>Websites Status</title>

		<!-- Bootstrap core CSS -->
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">

		<!-- Font Awesome -->
		<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">

		<style type="text/css">
			.jumbotron {padding: 16px 0}
			.jumbotron h1 {margin-top: 10px}
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
				<p>Number of websites <?php echo $sitecount; ?>, currently we have <?php echo $pctup;?>% up.</p>
				<?php } ?>
				<div class="progress">
					<div class="progress-bar progress-bar-success" style="width: <?php echo $pctup;?>%">
						<span class="sr-only"><?php echo $pctup;?>% Up</span>
					</div>
					<div class="progress-bar progress-bar-danger" style="width: <?php echo $pctdown;?>%">
						<span class="sr-only"><?php echo $pctdown;?>% Down</span>
					</div>
				</div>
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
