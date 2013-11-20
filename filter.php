<?php

require_once('workflows.php');


function result($r) {
	global $results;
	if ( ! isset($r['icon']) ):
		$r['icon'] = 'icon.png';
	endif;
	array_push($results, $r);
}

function color_picker($id) {
	$rgba = `osascript -e 'tell application "Alfred 2"' -e 'activate' -e 'choose color default color {65535, 65535, 65535}' -e 'end tell'`;
	$hex = '';
	if ( $rgba ):
		$rgba = explode(',', $rgba);
		$rgb = array_slice($rgba, 0, 3);
		// Convert to hex
		foreach ( $rgb as $c ):
			$hex .= substr('0' . dechex(($c / 65535) * 255), -2);
		endforeach;
	endif;
	return `osascript -e 'tell application "Alfred 2" to search "hue $id:color:$hex"'`;
}

function generate_results($query, $set_partial_query = true) {
	global $lights;
	global $partial_query;
	$control = explode(':', $query);

	if ( $query == 'lights' ):
		foreach ( $lights as $id => $light ):
			result(array(
				'uid' => "light_$id",
				'title' => $light['name'],
				'valid' => 'no',
				'autocomplete' => "$id:"
			));
		endforeach;

	elseif ( isset($lights[$query]) ):
		$id = $query;
		$light = $lights[$id];
		result(array(
			'uid' => "light_$id",
			'title' => $light['name'],
			'valid' => 'no',
			'autocomplete' => "$id:"
		));

	elseif ( count($control) == 2 ):
		$id = $control[0];
		$remainder = $control[1];
		result(array(
			'title' => 'Turn off',
			'icon' => 'icons/switch.png',
			'autocomplete' => "$id:off",
			'arg' => json_encode(array(
				'url' => "/lights/$id/state",
				'data' => '{"on": false}'
			))
		));
		result(array(
			'title' => 'Turn on',
			'icon' => 'icons/switch.png',
			'autocomplete' => "$id:on",
			'arg' => json_encode(array(
				'url' => "/lights/$id/state",
				'data' => '{"on": true}'
			))
		));
		result(array(
			'title' => 'Set color...',
			'icon' => 'icons/colors.png',
			'valid' => 'no',
			'autocomplete' => "$id:color:"
		));
		result(array(
			'title' => 'Set effect...',
			'icon' => 'icons/effect.png',
			'valid' => 'no',
			'autocomplete' => "$id:effect:"
		));
		result(array(
			'title' => 'Set brightness...',
			'icon' => 'icons/sun.png',
			'valid' => 'no',
			'autocomplete' => "$id:bri:"
		));
		result(array(
			'title' => 'Set alert...',
			'icon' => 'icons/siren.png',
			'valid' => 'no',
			'autocomplete' => "$id:alert:"
		));
		result(array(
			'title' => 'Rename...',
			'icon' => 'icons/cog.png',
			'valid' => 'no',
			'autocomplete' => "$id:rename:"
		));

	elseif ( count($control) == 3 ):
		$id = $control[0];
		$value = $control[2];
		if ( $control[1] == 'bri' ):
			result(array(
				'title' => "Set brightness to $value",
				'subtitle' => 'Set on a scale from 0 to 255, where 0 is off.',
				'icon' => 'icons/sun.png',
				'arg' => json_encode(array(
					'url' => "/lights/$id/state",
					'data' => sprintf('{"bri": %d}', $value)
				))
			));
		elseif ( $control[1] == 'color' ):
			if ( $value == 'colorpicker' ):
				color_picker($id);
			endif;

			result(array(
				'title' => "Set color to $value",
				'subtitle' => 'Accepts 6-digit hex colors or CSS literal color names (e.g. "blue")',
				'icon' => 'icons/colors.png',
				'arg' => json_encode(array(
					'url' => "/lights/$id/state",
					'data' => '',
					'_color' => $value
				))
			));
			result(array(
				'title' => "Use color picker...",
				'valid' => 'no',
				'icon' => 'icons/eyedropper.png',
				'autocomplete' => "$id:color:colorpicker"
			));

		elseif ( $control[1] == 'effect' ):
			result(array(
				'title' => 'None',
				'icon' => 'icons/effect.png',
				'arg' => json_encode(array(
					'url' => "/lights/$id/state",
					'data' => '{"effect": "none"}'
				))
			));
			result(array(
				'title' => 'Color loop',
				'icon' => 'icons/effect.png',
				'arg' => json_encode(array(
					'url' => "/lights/$id/state",
					'data' => '{"effect": "colorloop"}'
				))
			));
		elseif ( $control[1] == 'alert' ):
			result(array(
				'title' => 'None',
				'subtitle' => 'Turn off any ongoing alerts',
				'icon' => 'icons/siren.png',
				'arg' => json_encode(array(
					'url' => "/lights/$id/state",
					'data' => '{"alert": "none"}'
				))
			));
			result(array(
				'title' => 'Blink once',
				'icon' => 'icons/siren.png',
				'arg' => json_encode(array(
					'url' => "/lights/$id/state",
					'data' => '{"alert": "select"}'
				))
			));
			result(array(
				'title' => 'Blink for 30 seconds',
				'icon' => 'icons/siren.png',
				'arg' => json_encode(array(
					'url' => "/lights/$id/state",
					'data' => '{"alert": "lselect"}'
				))
			));

		elseif ( $control[1] == 'rename' ):
			result(array(
				'title' => "Set light name to $value",
				'arg' => json_encode(array(
					'url' => "/lights/$id",
					'data' => sprintf('{"name": "%s"}', $value)
				))
			));
		endif;

	else:
		$remainder = $query;
		result(array(
			'title' => 'Lights',
			'valid' => 'no',
			'autocomplete' => 'lights'
		));
		result(array(
			'title' => 'Turn all lights off',
			'icon' => 'icons/switch.png',
			'autocomplete' => 'off',
			'arg' => json_encode(array(
				'_group' => 'true',
				'data' => '{"on": false}'
			))
		));
		result(array(
			'title' =>'Turn all lights on',
			'icon' => 'icons/switch.png',
			'autocomplete' => 'on',
			'arg' => json_encode(array(
				'_group' => 'true',
				'data' => '{"on": true}'
			))
		));
		result(array(
			'title' => 'Party',
			'subtitle' => 'Set all lights to color loop.',
			'icon' => 'icons/colors.png',
			'autocomplete' => 'party',
			'arg' => json_encode(array(
				'_group' => 'true',
				'data' => '{"effect": "colorloop"}'
			))
		));
		result(array(
			'title' => 'Movie',
			'icon' => 'icons/popcorn.png',
			'subtitle' => 'Set the lights to the minimum brightness.',
			'autocomplete' => 'movie',
			'arg' => json_encode(array(
				'_group' => 'true',
				'data' => '{"ct": 500, "sat": 0, "bri": 1}'
			))
		));
	endif;

	if ( $set_partial_query ):
		$partial_query = $remainder;
	endif;
}


// -----------------------------------------------------------------------------
// Where the sausage is made.
// -----------------------------------------------------------------------------

$w = new Workflows();
$input = $argv[1];
$results = array();

// Cache a reference to lights.
// This should be the only case when the settings file is read.
if ( trim($input) == '' ):
	$username  = $w->get('api.username',  'settings.plist');
	$bridge_ip = $w->get('api.bridge_ip', 'settings.plist');
	$base_path = "/api/$username";

	// clear cache
	$lights = $w->write('', 'lights');
	$lights = $w->request(
		"http://$bridge_ip" . $base_path . '/lights',
		array(CURLOPT_CONNECTTIMEOUT => 3)
	);
	$lights = json_decode($lights, true);
	if ( $lights ):
		$w->write($lights, 'lights');
	endif;
else:
	$lights = $w->read('lights', true);
endif;

if ( ! $lights ):
	result(array(
		'title' => 'Bridge connection failed.',
		'subtitle' => 'Try running "setup-hue".',
		'valid' => 'no'
	));
	echo $w->toxml($results);
	exit;
endif;

// Check for combined query.
if ( count(explode(' ', $input)) > 1 ):
	function filter($result) {
		return ! ( isset($result['valid']) && $result['valid'] == 'no' );
	}

	foreach (explode(' ', $input) as $query):
		generate_results($query, false);
	endforeach;

	$results = array_filter($results, 'filter');

	$r_args = array();

	foreach ($results as $r):
		$r_args[] = $r['arg'];
	endforeach;

	$results = array();

	result(array(
		'title' => 'Combined Query',
		'subtitle' => 'Look at you go, power user!',
		'arg' => json_encode(array(
			'_multi' => true,
			'data' => implode(',', $r_args)
		))
	));
else:
	generate_results($input);
endif;

// Filter by partial query.
if ( $partial_query ):
	function filter($result) {
		global $partial_query;
		if ( isset($result['autocomplete']) ):
			return stripos($result['autocomplete'], $partial_query) !== false;
		endif;
		return false;
	}
	$results = array_filter($results, 'filter');
endif;

echo $w->toxml($results);
exit;
