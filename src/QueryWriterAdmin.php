<?php
use Mouf\MoufManager;
use Mouf\MoufUtils;


// Controller declaration
$moufManager = MoufManager::getMoufManager();
$moufManager->declareComponent('parseselect', 'Mouf\\Database\\QueryWriter\\Controllers\\SelectController', true);
$moufManager->bindComponents('parseselect', 'template', 'moufTemplate');
$moufManager->bindComponents('parseselect', 'content', 'block.content');


?>