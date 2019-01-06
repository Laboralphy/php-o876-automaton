<?php

require('EAutomate.php');
require('Trans.php');
require('State.php');
require('System.php');


class MesTransistions {
	public $profil;
	
	public function createDraft() {
		print "createDraft\n";
	}
	
	public function isBrouillonCreatable() {
		print "isBrouillonCreatable ?\n";
		return $this->profil === 'n1';
	}

	public function isBrouillonRemovable() {
		print "isBrouillonRemovable ?\n";
		return $this->profil === 'n2';
	}
}

$mesTrans = new MesTransistions();
$mesTrans->profil = 'n1';


$sys = new O876\Automate\System();

$sys->loadFromXMLFile('test1.xml');
$sys->setCaller($mesTrans);
$sys->setStateIndex('start');

// liste des action possibles
print_r(array_map(function($s) { return $s ? $s->getName() : '***'; }, $sys->searchActivableStates(array('toto', 'creer', 'suppr'))));


$newState = $sys->doAction('creer');
if ($newState) {
	print "nouvel état : " . $newState->getName() . "\n";
} else {
	print "pas possible\n";
}
