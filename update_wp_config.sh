#!/bin/bash

VALUES_FILE="$(dirname "$0")/wp_config_values.txt"
WP_CONFIG="/srv/www/wordpress/wp-config.php"

if [ ! -f "$VALUES_FILE" ]; then
    echo "Error: $VALUES_FILE not found"
    exit 1
fi

if [ ! -f "$WP_CONFIG" ]; then
    echo "Error: $WP_CONFIG not found"
    exit 1
fi

php -r '
$valuesFile = $argv[1];
$wpConfig   = $argv[2];

$replacements = [];
foreach (file($valuesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (preg_match("/define\(\s*'\''([^'\'']+)'\''/", $line, $m)) {
        $replacements[$m[1]] = $line;
    }
}

$lines = file($wpConfig);
$out = [];
foreach ($lines as $line) {
    if (preg_match("/define\(\s*'\''([^'\'']+)'\''/", $line, $m) && isset($replacements[$m[1]])) {
        $out[] = $replacements[$m[1]] . PHP_EOL;
    } else {
        $out[] = $line;
    }
}

file_put_contents($wpConfig, implode("", $out));
echo "Updated " . count($replacements) . " keys in " . $wpConfig . PHP_EOL;
' -- "$VALUES_FILE" "$WP_CONFIG"
