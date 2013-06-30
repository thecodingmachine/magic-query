<?php
use Mouf\MoufManager;
use Mouf\MoufUtils;

MoufUtils::registerMainMenu('dbMainMenu', 'DB', null, 'mainMenu', 70);
MoufUtils::registerMenuItem('dbQueryWriterAdminSubMenu', 'SQL queries', null, 'dbMainMenu', 80);
MoufUtils::registerMenuItem('dbQueryWriterCreateQueryAdminSubMenu', 'Create SQL query', 'parseselect/createQuery', 'dbQueryWriterAdminSubMenu', 0);


// Controller declaration
$moufManager = MoufManager::getMoufManager();
$moufManager->declareComponent('parseselect', 'Mouf\\Database\\QueryWriter\\Controllers\\SelectController', true);
$moufManager->bindComponents('parseselect', 'template', 'moufTemplate');
$moufManager->bindComponents('parseselect', 'content', 'block.content');


?>