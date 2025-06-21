<?php
/**
 * Universal WordPress Plugin Build Script
 * Author: Big Ears Webagentur
 */

$folders = glob(__DIR__ . '/*', GLOB_ONLYDIR);
$pluginFolder = isset($folders[0]) ? $folders[0] : null;
if (!$pluginFolder) {
    exit("❌ ERROR: No plugin folder found.\n");
}
$pluginSlug = basename($pluginFolder);
$pluginMainFile = $pluginFolder . '/' . $pluginSlug . '.php';
$buildDir = __DIR__ . '/build';

// Pre-flight checks
if (!file_exists($pluginMainFile)) {
    exit("❌ ERROR: Main plugin file '{$pluginSlug}.php' not found.\n");
}

// Get version
function getPluginVersion($filePath) {
    $contents = file_get_contents($filePath);
    if (preg_match('/^Version:\s*(.*)$/mi', $contents, $matches)) {
        return trim($matches[1]);
    }
    return '0.1.0';
}

$version = getPluginVersion($pluginMainFile);
$zipFileName = "{$pluginSlug}-v{$version}.zip";

// Ensure build directory
if (!is_dir($buildDir)) {
    mkdir($buildDir);
}

// Start ZIP
$zip = new ZipArchive();
if ($zip->open($buildDir . '/' . $zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    exit("❌ ERROR: Cannot create <$zipFileName>\n");
}

// Add plugin files
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($pluginFolder, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $file) {
    $filePath = $file->getRealPath();
    $relativePath = $pluginSlug . '/' . substr($filePath, strlen($pluginFolder) + 1);
    $zip->addFile($filePath, $relativePath);
}

$zip->close();

echo "✅ Plugin built: $buildDir/$zipFileName\n";

// Git auto-commit and tag
$gitEnabled = true;

if ($gitEnabled) {
    shell_exec('git add .');
    shell_exec('git commit -m "Build v' . $version . '"');
    shell_exec('git tag v' . $version);
    shell_exec('git push --tags');
    echo "✅ Git commit and tag v$version created.\n";
}

