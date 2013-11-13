# Contributors Guide

First of all, thank you for your interest in contributing to AlphaRPC!

Here you will find a couple of guidelines we would like you to follow.

  * Find or create an issue
  * Fork and fix
  * Style your code
  * Send a PR
    * For a feature, use the master branch.
    * For a bugfix, use the earliest release branch in which the bug exists.


## Coding style

AlphaRPC uses the [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) coding style.
To format your code, please use the following command (mind the dot at the end):

`vendor/bin/php-cs-fixer -c .php_cs fix .`