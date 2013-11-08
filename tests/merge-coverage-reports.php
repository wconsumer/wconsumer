<?php
echo "\nGenerating merged coverage report for both unit and integration tests\n";

if (!defined('WC_BASE')) {
  define('WC_BASE', dirname(__DIR__));
}

$autoloadFile = WC_BASE.'/vendor/autoload.php';
if (!file_exists($autoloadFile)) {
  die('Composer not installed');
}
require_once($autoloadFile);

if (!class_exists('PHP_CodeCoverage')) {
  die('PHP_CodeCoverage class not found');
}

$coverage = new PHP_CodeCoverage();
foreach (listReports() as $report) {
  $coverage->merge(read($report));
}

$writer = new PHP_CodeCoverage_Report_Text(new PHPUnit_TextUI_ResultPrinter(), 35, 70, FALSE);
$writer->process($coverage);

$writer = new PHP_CodeCoverage_Report_HTML();
$writer->process($coverage, WC_BASE.'/build/coverage/html-merged');
?>
<?php
function listReports() {
  return glob(WC_BASE.'/build/coverage/*serialized*');
}

function read($filename) {
  if (!file_exists($filename)) {
    die("Coverage report '{$filename}' file not found '{$filename}'");
  }

  return unserialize(file_get_contents($filename));
}
?>