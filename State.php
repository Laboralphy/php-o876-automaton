<?php
namespace O876\Automate;

/** Etat d'automate.
 * 
 * @author raphael.marandet
 *
 */
class State {
  protected $sName;
  protected $aTrans;
  protected $oSystem;

  /** Constructeur de la classe
   * @param Automate $oMaster Automate contenant cet état
   * @param string $s identifiant de l'état
   */
  public function __construct(System $oMaster, $s) {
    $this->sName = $s;
    $this->aTrans = array();
    $this->oSystem = $oMaster;
  }
  
  public function getTransitions() {
    return $this->aTrans;
  }

  public function getName() {
    return $this->sName;
  }
  
  public function hasAction($sAction) {
    return isset($this->aTrans[$sAction]);
  }

  /** Création d'une nouvelle transition.
   * @param string $sAction Nom de l'action associée à cet état
   * @param Trans $oTrans Transition
   * @return Trans
   */
  public function linkTrans($sAction, Trans $oTrans) {
    $this->aTrans[$sAction] = $oTrans;
    return $oTrans;
  }

  /** Effectue l'action spécifiée
   * - Recherche du test
   * - Test et récupération du résultat
   * - Retour d'une transition en cas de test réussi
   * @param string $sAction ACtion a effectuer
   * @return Trans|NULL
   */
  public function doAction($sAction) {
    if (isset($this->aTrans[$sAction])) {
      $oTrans = $this->aTrans[$sAction];
      $sMeth = $oTrans->getEvent();
      if ($sMeth) {
        if ($this->oSystem->doTest($this, $sMeth)) {
          return $oTrans;
        } else {
          return null;
        }
      } else {
        return $oTrans;
      }
    } else {
      throw new EAutomate('action impossible depuis l\'état ' . $this->sName);
    }
  }
  
  public function isValidAction($sAction) {
    return is_null($this->doAction($sAction)) ? false : true;
  }
  
  public function getValidActions() {
    $aResults = array();
    $aTrans = $this->getTransitions();
    foreach ($aTrans as $sAction => $oTrans) {
      $aResults[$sAction] = $this->isValidAction($sAction);
    }
    return $aResults;
  }
}
