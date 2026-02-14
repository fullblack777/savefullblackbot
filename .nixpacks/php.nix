# .nixpacks/php.nix
{ pkgs }:

pkgs.buildEnv {
  name = "php-environment";
  paths = [
    pkgs.php82
    pkgs.php82Packages.composer
  ];
}
