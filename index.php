<?php
/*
Title:        Blackhole for Bad Bots
Description:  Automatically trap and block bots that don't obey robots.txt rules
Project URL:  http://perishablepress.com/blackhole-bad-bots/
Author:       Jeff Starr (aka Perishable)
Version:      4.0
License:      GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.txt



USAGE:

1. Add the /blackhole/ folder to the root directory of your site.

2. Open /blackhole/index.php and edit the three variables in the "EDIT HERE" section.

3. Change file permissions for blackhole.dat to make it writable by the server.

4. Add this line to the beginning of all pages:

<?php include(realpath(getenv('DOCUMENT_ROOT')) . '/blackhole/index.php'); ?>

5. Add this line to the footer of all pages (change "example.com" to match your domain):

<a rel="nofollow" style="display:none;" href="https://example.com/blackhole/">Do NOT follow this link or you will be banned from the site!</a>

6. Add these lines to your site's robots.txt file:

User-agent: *
Disallow: /blackhole/

7. Done! Remember to test thoroughly before going live.



TESTING:

1. Test your robots.txt rules for proper syntax (for example, you can use the robots checker in Google Webmaster Tools).

2. Visit the link from step 5, and then try visiting other pages on your site.

Tip: To reset the Blackhole list, clear the contents of the blackhole.dat file.

For complete infos, visit: https://perishablepress.com/blackhole-bad-bots-php-version/

*/



// EDIT HERE

define('BLACKHOLE_TO',      'blackhole@badbot.cf'); // email to
define('BLACKHOLE_FROM',    'report@badbot.cf'); // email from
define('BLACKHOLE_SUBJECT', 'Bad Bot Alert!');    // email subject



// DO NOT EDIT BELOW THIS LINE

define('BLACKHOLE_VERSION', '4.0');
define('BLACKHOLE_PATH', realpath(getenv('DOCUMENT_ROOT')) . '/');



function blackhole_get_vars() {
	
	$ip = blackhole_get_ip();
	
	$whois = blackhole_whois();
	
	$ua       = isset($_SERVER['HTTP_USER_AGENT']) ? blackhole_sanitize($_SERVER['HTTP_USER_AGENT']) : null;
	
	$request  = isset($_SERVER['REQUEST_URI'])     ? blackhole_sanitize($_SERVER['REQUEST_URI'])     : null;
	
	$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? blackhole_sanitize($_SERVER['SERVER_PROTOCOL']) : null;
	
	$method   = isset($_SERVER['REQUEST_METHOD'])  ? blackhole_sanitize($_SERVER['REQUEST_METHOD'])  : null;
	
	date_default_timezone_set('UTC');
	
	$date = date('l, F jS Y @ H:i:s');
	
	$time = time();
	
	return array($ip, $whois, $ua, $request, $protocol, $method, $date, $time);
	
}



function blackhole_checkbot($ip, $ua, $request) {
	
	$badbot = 0;
	
	if (blackhole_whitelist($ua)) return -1;
	
	$filename = BLACKHOLE_PATH . 'blackhole.dat';
	
	$fp = fopen($filename, 'r') or die('<p>Error: Data File</p>');
	
	while ($line = fgets($fp)) {
		
		$ua_logged = explode(' ', $line);
		
		if ($ua_logged[0] === $ip) {
			
			$badbot++;
			
			break;
			
		}
		
	}
	
	fclose($fp);
	
	if (($badbot === 0) && (strpos($request, '/') === false)) return -1;
	
	return $badbot;
	
}



function blackhole() {
	
	list ($ip, $whois, $ua, $request, $protocol, $method, $date, $time) = blackhole_get_vars();
	
	$badbot = blackhole_checkbot($ip, $ua, $request);
	
	if ($badbot > 0) {
		
		echo '<h1>You have been banned from this domain</h1>';
		echo '<p>If you think there has been a mistake, <a href="/">contact the administrator</a> via proxy server.</p>';
		
		exit;
		
	} elseif ($badbot === 0) {
		
		$filename = BLACKHOLE_PATH . 'blackhole.dat';
		
		$fp = fopen($filename, 'a+');
		
		fwrite($fp, $ip .' - '. $method .' - '. $protocol .' - '. $date .' - '. $ua . "\n");
		
		fclose($fp);
		
		$message  = $date . "\n\n";
		$message .= 'URL Request: '  . $request . "\n";
		$message .= 'IP Address: '   . $ip . "\n";
		$message .= 'User Agent: '   . $ua . "\n\n";
		$message .= 'Whois Lookup: ' . "\n\n" . $whois . "\n";
		
		mail (BLACKHOLE_TO, BLACKHOLE_SUBJECT, $message, 'From: '. BLACKHOLE_FROM);
		
		?><!DOCTYPE html>
<html lang="en-US">
	<head>
		<title>Welcome to Blackhole!</title>
		<style>
			body { color: #fff; background-color: #851507; font: 14px/1.5 Helvetica, Arial, sans-serif; }
			#blackhole { margin: 20px auto; width: 700px; }
			pre { padding: 20px; white-space: pre-line; border-radius: 10px; background-color: #b34334; }
			a { color: #fff; }
		</style>
	</head>
	<body>
		<div id="blackhole">
			<h1>You have fallen into a trap!</h1>
			<p>
				This site&rsquo;s <a href="/robots.txt">robots.txt</a> file explicitly forbids your presence at this location. 
				The following Whois data will be reviewed carefully. If it is determined that you suck, you will be banned from this site. 
				If you think this is a mistake, <em>now</em> is the time to <a href="/contact/">contact the administrator</a>.
			</p>
			<h3>Your IP Address is <?php echo $ip; ?></h3>
			<pre>WHOIS Lookup for <?php echo $ip ."\n". $date ."\n\n". $whois; ?></pre>
			<p><a href="http://perishablepress.com/blackhole-bad-bots/" title="Blackhole for Bad Bots">Blackhole v<?php echo BLACKHOLE_VERSION; ?></a></p>
		</div>
	</body>
</html><?php
		
		exit;
		
	}
	
	return false;
	
}



function blackhole_whitelist($ua) {
	
	if (preg_match("/(aolbuild|baidu|bingbot|bingpreview|msnbot|duckduckgo|adsbot-google|googlebot|mediapartners-google|teoma|slurp|yandex)/i", $ua)) {
		
		return true;
		
	}
	
	return false;
	
}



function blackhole_sanitize($string) {
	
	$string = trim($string); 
	$string = strip_tags($string);
	$string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
	$string = str_replace("\n", "", $string);
	$string = trim($string); 
	
	return $string;
	
}



function blackhole_get_ip() {
	
	$ip = blackhole_evaluate_ip();
	
	if (preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $ip, $ip_match)) {
		
		$ip = $ip_match[1];
		
	}
	
	return blackhole_sanitize($ip);
	
}



function blackhole_evaluate_ip() {
	 
	$ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_X_REAL_IP', 'HTTP_X_COMING_FROM', 'HTTP_PROXY_CONNECTION', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_COMING_FROM', 'HTTP_VIA', 'REMOTE_ADDR');
	
	foreach ($ip_keys as $key) {
		
		if (array_key_exists($key, $_SERVER) === true) {
			
			foreach (explode(',', $_SERVER[$key]) as $ip) {
				
				$ip = trim($ip);
				
				$ip = blackhole_normalize_ip($ip);
				
				if (blackhole_validate_ip($ip)) {
					
					return $ip;
					
				}
				
			}
			
		}
		
	}
	
	return 'Error: Invalid Address';
	
}



function blackhole_normalize_ip($ip) {
	
	if (strpos($ip, ':') !== false && substr_count($ip, '.') == 3 && strpos($ip, '[') === false) {
		
		// IPv4 with port (e.g., 123.123.123:80)
		$ip = explode(':', $ip);
		$ip = $ip[0];
		
	} else {
		
		// IPv6 with port (e.g., [::1]:80)
		$ip = explode(']', $ip);
		$ip = ltrim($ip[0], '[');
		
	}
	
	return $ip;
	
}



function blackhole_validate_ip($ip) {
	
	$options  = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
	
	$filtered = filter_var($ip, FILTER_VALIDATE_IP, $options);
	
	 if (!$filtered || empty($filtered)) {
		
		if (preg_match("/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/", $ip)) {
			
			return $ip; // IPv4
			
		} elseif (preg_match("/^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/", $ip)) { 
			
			return $ip; // IPv6
			
		}
		
		error_log('Invalid IP Address: '. $ip);
		
		return false;
		
	}
	
	return $filtered;
	
}



function blackhole_whois() {
	
	$msg = '';
	
	$extra = '';
	
	$server = 'whois.arin.net';
	
	$ip = blackhole_get_ip();
	
	if (!$ip = gethostbyname($ip)) {
		
		$msg .= 'Can&rsquo;t perform lookup without an IP address.'. "\n\n";
		
	} else {
		
		if (!$sock = fsockopen($server, 43, $num, $error, 20)) {
			
			unset($sock);
			$msg .= 'Timed-out connecting to $server (port 43).'. "\n\n";
			
		} else {
			
			// fputs($sock, "$ip\n");
			fputs($sock, "n $ip\n");
			$buffer = '';
			while (!feof($sock)) $buffer .= fgets($sock, 10240); 
			fclose($sock);
			
		}
		
		if (stripos($buffer, 'ripe.net')) {
			
			$nextServer = 'whois.ripe.net';
			
		} elseif (stripos($buffer, 'nic.ad.jp')) {
			
			$nextServer = 'whois.nic.ad.jp';
			$extra = '/e'; // suppress JaPaNIC characters
			
		} elseif (stripos($buffer, 'registro.br')) {
			
			$nextServer = 'whois.registro.br';
			
		}
		
		if (isset($nextServer)) {
			
			$buffer = '';
			$msg .= 'Deferred to specific whois server: '. $nextServer .'...'. "\n\n";
			
			if (!$sock = fsockopen($nextServer, 43, $num, $error, 10)) {
				
				unset($sock);
				$msg .= 'Timed-out connecting to '. $nextServer .' (port 43)'. "\n\n";
				
			} else {
				
				fputs($sock, $ip . $extra . "\n");
				while (!feof($sock)) $buffer .= fgets($sock, 10240);
				fclose($sock);
				
			}
		}
		
		$replacements = array("\n", "\n\n", "");
		$patterns = array("/\\n\\n\\n\\n/i", "/\\n\\n\\n/i", "/#(\s)?/i");
		$buffer = preg_replace($patterns, $replacements, $buffer);
		$buffer = htmlentities(trim($buffer), ENT_QUOTES, 'UTF-8');
		
		// $msg .= nl2br($buffer);
		$msg .= $buffer;
		
	}
	
	return $msg;
	
}



blackhole();


