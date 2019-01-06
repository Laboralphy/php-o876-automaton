<?php
namespace O876\Automate;

/** Classe de manipulation d'automate
 * @author raphael.marandet
 *
 */
class System {
  protected $aStates;
  protected $sStateIndex;
  protected $oCaller = null;

  public function __construct() {
    $this->aStates = array();
  }
  
  /** Défini un nouvel état.
   * @param string $sState Identifiant du nouvel état.
   * @return State
   */
  public function newState($sState) {
    return $this->linkState(new State($this, $sState));
  }

  /** Ajoute un Etat à la collection de l'automate
   * @param State $oState Etat à linker
   * @return State
   */
  public function linkState(State $oState) {
    $this->aStates[$oState->getName()] = $oState;
    return $oState;
  }

  public function setStateIndex($sState) {
    $this->sStateIndex = $sState;
  }

  public function getState($sState) {
    return $this->aStates[$sState];
  }
  
  public function setCaller($oCaller) {
  	$this->oCaller = $oCaller;
  }
  
  /** Recherche les états à partir desquels on peut utiliser une action
   * donnée.
   * @param string $sAction nom de l'action
   * @return array of States
   */
  public function searchStates($sAction) {
    $aResult = array();
    foreach ($this->aStates as $sState => $oState) {
      if ($oState->hasAction($sAction)) {
        $aResult[$sState] = $oState;
      }
    }
    return $aResult;    
  }
  
  public function searchActivableStates($aActions) {
    $aStates = array();
    foreach ($aActions as $sAction) {
      $a = $this->searchStates($sAction);
      foreach ($a as $sState => $oState) {
        try {
          if ($oState->doAction($sAction)) {
            $aStates[$sState] = $oState;
          } 
        } catch (EAutomate $e) {
          // Action invalide
        }
      }
    }
    return $aStates;
  }
  
  
  /** Lance le test associé à une transaction.
   * 
   * @param State $oState Etat  
   * @param $sTest Nom du test.
   * @return boolean
   */
  public function doTest(State $oState, $sTest) {
    if (is_null($this->oCaller)) {
      $this->oCaller = $this;
    }
    if (method_exists($this->oCaller, $sTest)) {
      return $this->oCaller->$sTest();
    } else {
      throw new EAutomate('test ' . $sTest . ' introuvable dans l\'objet spécifié.');
    }
  }
  
  /** Effectue une action
   * Vérifie que l'action spécifiée existe
   * Extrait la transition associer
   * Effectue le test de cette transition
   * Si le test réussi, l'automate tranvers la transition
   * @param string $sAction Nom de l'action 
   * @return State Nouvel état
   */
  public function doAction($sAction) {
    $oState = $this->getCurrentState();

    $oTrans = $oState->doAction($sAction);
    if ($oTrans) {
      $this->setStateIndex($oTrans->getDestState()->getName());
      $oNewState = $this->getCurrentState();
      if (is_null($this->oCaller)) {
        $this->oCaller = $this;
      }
      $sPEvent = $oTrans->getPostEvent();
      if ($sPEvent && method_exists($this->oCaller, $sPEvent)) {
        $this->oCaller->$sPEvent($this);
      }
      return $oNewState;
    } else {
      return null; //throw new EAutomate('condition non-remplie pour effectuer l\'action "'. $sAction . '"');
    }
  }

  /** Créé une nouvelle transition
   * 
   * @param string $sFrom identifiant Etat de départ
   * @param string $sTo identifiant Etat d'arriver
   * @param string $sAction Nom de l'action identifiant la transition
   * @param string $sEvent Test à effectuer pour valider la transition
   * @return Trans
   */
  public function newTrans($sFrom, $sTo, $sAction, $sEvent, $sCons) {
    $oTo = $this->getState($sTo);
    if (is_null($oTo)) {
		throw new EAutomate('Unknown state "' . $sTo . '" while creating new transition. Known states are : [' . implode(', ', array_keys($this->aStates)) . ']');
	}
    return $this->linkAction($sAction, $sFrom, new Trans($oTo, $sEvent, $sCons));
  }

  /** Link une action à la collection interne de l'automate
   * 
   * @param $sAction action identifiant la transition 
   * @param $sFrom etat a partir duquel on branche la transition
   * @param $oTrans transition identifié par l'action
   * @return Trans
   */
  public function linkAction($sAction, $sFrom, Trans $oTrans) {
    $oFrom = $this->getState($sFrom);
    return $oFrom->linkTrans($sAction, $oTrans);
  }

  /** Renvoie l'état courant (instance de State) de l'automate
   * 
   * @return State
   */
  public function getCurrentState() {
    return $this->getState($this->sStateIndex);
  }

  /** Transforme le contenu de l'automate (définition de Etat/Transition) 
   * en données XML
   * 
   * @return string
   */
  public function renderXML() {
    $oDom = new \DomDocument();
    $oDom->formatOutput = true;
    $oRootElement = $oDom->createElement('states');
    $oStatesNode = $oDom->appendChild($oRootElement);

    foreach ($this->aStates as $oState) {
      $oStateElement = $oDom->createElement('state');
      $oStateElement->setAttribute('id', $oState->getName());
      $oStateNode = $oStatesNode->appendChild($oStateElement);
      $oTransitionsElement = $oDom->createElement('transitions');
      $oTransitionsNode = $oStateNode->appendChild($oTransitionsElement);
      foreach ($oState->getTransitions() as $sTransName => $oTrans) {
        $oTransElement = $oDom->createElement('transition');
        $oTransElement->setAttribute('id', $sTransName);
        $oTransNode = $oTransitionsNode->appendChild($oTransElement);

        $oDestElement = $oDom->createElement('destination');
        $oDestText = $oDom->createTextNode($oTrans->getDestState()->getName());
        $oDestNode = $oTransNode->appendChild($oDestElement);
        $oDestNode->appendChild($oDestText);

        $oCondElement = $oDom->createElement('condition');
        $oCondText = $oDom->createTextNode($oTrans->getEvent());
        $oCondNode = $oTransNode->appendChild($oCondElement);
        $oCondNode->appendChild($oCondText);
      }
    }

    return $oDom->saveXML();
  }

  /** Exporte les définition de l'automate dans un fichier XML
   * 
   * @param string $sFile Nom du fichier XML à produire
   */ 
  public function saveToXMLFile($sFile) {
    file_put_contents($sFile, $this->renderXML());
  }

  
  public function loadFromXMLFile($sFile) {
    $a = $this->parseXML(file_get_contents($sFile));
    $this->build($a);
  }

  /** 
   * 
   * @param $a
   * @return unknown_type
   */
  public function build($a) {
    foreach ($a['states'] as $sState) {
      $this->newState($sState);
    }
    foreach ($a['trans'] as $aTrans) {
      $this->newTrans($aTrans['state'], $aTrans['dest'], $aTrans['action'], $aTrans['cond'], $aTrans['cons']);
    }
  }

  private function parseXMLGetData($oElement) {
    if ($oElement->hasChildNodes()) {
      return $oElement->textContent;
    }
    return '';
  }
  
  private function getChild($oChild) {
    if ($oChild->length) {
      $oChild0 = $oChild->item(0);
      $sResult = $this->parseXMLGetData($oChild0);
    } else {
      $sResult = '';
    }
    return $sResult;
  }

  /** Analyse d'un flux de données XML afin de définir les états et les transitions
   * Le tableau parsé peut etre transmis à build().
   * 
   * @param string $sXML
   * @return Array tableau de configuration
   */
  public function parseXML($sXML) {
    $oDom = new \DomDocument();
    $oDom->loadXML($sXML);

    $oStates = $oDom->getElementsByTagName('states')->item(0);
    $aStates = $oStates->getElementsByTagName('state');
    $s = array('states' => array(), 'trans' => array());
    for ($i = 0; $i < $aStates->length; $i++) {
      $oState = $aStates->item($i);
	  $s['states'][] = $oState->getAttribute('id');
	}
    for ($i = 0; $i < $aStates->length; $i++) {
      $oState = $aStates->item($i);
      $sStateId = $oState->getAttribute('id');
      if ($oState->getElementsByTagName('transitions')) {
		$aTranss = $oState->getElementsByTagName('transitions')->item(0);
		if ($aTranss) {
			$aTrans = $aTranss->getElementsByTagName('transition');
			for ($iTrans = 0; $iTrans < $aTrans->length; $iTrans++) {
				$oTrans = $aTrans->item($iTrans);
				$sTransId = $oTrans->getAttribute('id');
				$oDest = $oTrans->getElementsByTagName('destination')->item(0);
				$sCond = $this->getChild($oTrans->getElementsByTagName('condition'));
				$sCons = $this->getChild($oTrans->getElementsByTagName('consequence'));
				$sDest = $this->parseXMLGetData($oDest);
				$s['trans'][] = array(
				  'state' => $sStateId,
				  'action' => $sTransId,
				  'dest' => $sDest,
				  'cond' => $sCond,
				  'cons' => $sCons
				);
		    }
		}
      }
    }
    return $s;
  }
}
