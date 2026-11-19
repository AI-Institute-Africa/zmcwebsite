<?php
require '/var/www/portal.zmc.org.zw/vendor/autoload.php';
use Illuminate\View\Compilers\BladeCompiler; use Illuminate\Filesystem\Filesystem;
$c = new BladeCompiler(new Filesystem, '/tmp/zmc-uiux-check/cache');
$bad=0;
foreach (array_slice($argv,1) as $f) {
  $php = $c->compileString(file_get_contents($f));
  $tmp = tempnam('/tmp','blade'); file_put_contents($tmp,$php);
  exec('php -l '.escapeshellarg($tmp).' 2>&1',$out,$rc); unlink($tmp);
  if ($rc) { $bad++; echo "FAIL $f\n".implode("\n",$out)."\n"; } else echo "ok   $f\n";
  $out=[];
}
exit($bad?1:0);
