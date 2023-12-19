<?php

namespace Composer;

use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
  'root' =>
  array (
    'pretty_version' => 'dev-4.x-dev',
    'version' => 'dev-4.x-dev',
    'aliases' =>
    array (
    ),
    'reference' => '966739c860cee8c164e596688c3e6055c8de29a9',
    'name' => 'devdavido/performance-audit-plugin',
  ),
  'versions' =>
  array (
    'devdavido/performance-audit-plugin' =>
    array (
      'pretty_version' => 'dev-4.x-dev',
      'version' => 'dev-4.x-dev',
      'aliases' =>
      array (
      ),
      'reference' => '966739c860cee8c164e596688c3e6055c8de29a9',
    ),
    'ducks-project/spl-types' =>
    array (
      'pretty_version' => 'v1.1.0',
      'version' => '1.1.0.0',
      'aliases' =>
      array (
      ),
      'reference' => '814f5b719fb39975321db0eaf66c4e0d2d93f53e',
    ),
    'jeroen-g/lighthouse' =>
    array (
      'pretty_version' => 'v0.2',
      'version' => '0.2.0.0',
      'aliases' =>
      array (
      ),
      'reference' => '9cabb6a857b1a9ccc537328956b4e0af2c370f9a',
    ),
    'symfony/polyfill-mbstring' =>
    array (
      'pretty_version' => 'v1.20.0',
      'version' => '1.20.0.0',
      'aliases' =>
      array (
      ),
      'reference' => '39d483bdf39be819deabf04ec872eb0b2410b531',
    ),
    'symfony/process' =>
    array (
      'pretty_version' => 'v4.4.18',
      'version' => '4.4.18.0',
      'aliases' =>
      array (
      ),
      'reference' => '075316ff72233ce3d04a9743414292e834f2cb4a',
    ),
  ),
);







public static function getInstalledPackages()
{
return array_keys(self::$installed['versions']);
}









public static function isInstalled($packageName)
{
return isset(self::$installed['versions'][$packageName]);
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

$ranges = array();
if (isset(self::$installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = self::$installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', self::$installed['versions'][$packageName])) {
$ranges = array_merge($ranges, self::$installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', self::$installed['versions'][$packageName])) {
$ranges = array_merge($ranges, self::$installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', self::$installed['versions'][$packageName])) {
$ranges = array_merge($ranges, self::$installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}





public static function getVersion($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

if (!isset(self::$installed['versions'][$packageName]['version'])) {
return null;
}

return self::$installed['versions'][$packageName]['version'];
}





public static function getPrettyVersion($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

if (!isset(self::$installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return self::$installed['versions'][$packageName]['pretty_version'];
}





public static function getReference($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

if (!isset(self::$installed['versions'][$packageName]['reference'])) {
return null;
}

return self::$installed['versions'][$packageName]['reference'];
}





public static function getRootPackage()
{
return self::$installed['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
}
}
