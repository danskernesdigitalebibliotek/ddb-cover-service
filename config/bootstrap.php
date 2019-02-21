<?php

// This file necessary because the legacy "behat/symfony2-extension"
// doesn't know about the new flex directory structure

// @TODO change to "friends-of-behat/symfony-extension" for Symfony 4 support

require dirname(__DIR__).'/src/.bootstrap.php';
