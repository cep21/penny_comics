<?php
/**
 * Shows penny arcade rss feed w/ comics inline
 *
 * @author Jack Lindamood
 * @license Apache License, Version 2.0
 */
header('Content-Type: text/xml');
$subreddit = $_GET['s'];
if (!$subreddit) {
	$subreddit = '';
}

$url_rss  = 'http://penny-arcade.com/feed';
$ch = curl_init($url_rss);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_TIMEOUT, 6);
$res_rss = curl_exec($ch);
curl_close($ch);

$x_obj = simplexml_load_string($res_rss);
$x_obj->channel->title = "Jackmod: " . $x_obj->channel->title;
$toremove = array();
$count = 0;
foreach ($x_obj->channel->item as $obj) {
	$count++;
	if (!preg_match('#Comic:(.*)#', $obj->description, $matches)) {
		// These are Iterable not arrays, so I can't just unset.  There's some
		// __unset magic that uses the index the count happens in
		$toremove[$count - 1] = $count - 1;
		continue;
	}
	$comic_title = trim($matches[1]);
	$link = trim($obj->link);
	$obj->link = $link;
	$ch = curl_init($link);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_TIMEOUT, 6);
	$image_post_content = curl_exec($ch);
	curl_close($ch);
	$matches = array();
	$lines = explode("\n", $image_post_content);
	$found_image = null;
	foreach ($lines as $line) {
		if (preg_match('#<img src="([A-Za-z0-9_\.:/]*art[-A-Za-z0-9\.\/]*)"#', $line, $matches)) {
			$found_image = $matches[1];
		}
	}
	if ($found_image) {
		$obj->description = '<img src="' . $found_image . '" />';
	} else {
		$obj->description = "------- INVALID MATCH -------\n" . $obj->description;
	}

}

// Need to unset in reverse order so I don't mess up indexes
sort($toremove);
foreach (array_reverse($toremove) as $k) {
	unset($x_obj->channel->item[$k]);
}
print $x_obj->asXML();
