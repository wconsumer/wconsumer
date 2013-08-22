<?php
echo "\nGenerating merged coverage report for both unit and integration tests\n";

$autoloadFile = __DIR__.'/vendor/autoload.php';
if (!file_exists($autoloadFile)) {
  die('Composer not installed');
}
require_once($autoloadFile);

if (!class_exists('PHP_CodeCoverage')) {
  die('PHP_CodeCoverage class not found');
}

$coverage = new PHP_CodeCoverage();
$coverage->merge(read('unit-tests-coverage.serialized'));
$coverage->merge(read('integration-tests-coverage.serialized'));

$writer = new PHP_CodeCoverage_Report_Text(new PHPUnit_TextUI_ResultPrinter(), 35, 70, FALSE);
$writer->process($coverage, !defined('PHP_WINDOWS_VERSION_BUILD') || !PHP_WINDOWS_VERSION_BUILD);
?>
<?php
function read($name) {
  $filename = __DIR__ . '/build/coverage/' . $name;
  if (!file_exists($filename)) {
    die("Coverage report '{$name}' file not found '{$filename}'");
  }

  return unserialize(file_get_contents($filename));
}
?>