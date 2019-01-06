<?php
namespace O876\Automate;

/** @brief Automate : Transition
 * 
 * Une transition permet de controler le passage d'un état à un autre.
 * La Transition gére une directive de test et un état de destination.
 * Si le test de cette transition retourne une valeur TRUE, l'automate 
 * traverse la transition et passe à l'état de destination. 
 * Si le test échoue, une exception est déclenchée.
 * 
 * @author raphael.marandet
 *
 */
class Trans {
  protected $oDestState;  // Etat de destination
  protected $sEvent;      // Evènement
  protected $sPostEvent;      // Evènement déclenché après le passage de transition
  
  
  /** Constructeur de la classe
   * @param State $d Etat de destination
   * @param string $e Test (nom de fonction de l'automate effectuant le test)
   */
  public function __construct(State $d, $e, $p) {
    $this->oDestState = $d;
    $this->sEvent = $e;
    $this->sPostEvent = $p;
  }

  public function getEvent() {
    return $this->sEvent;
  }

  public function getPostEvent() {
    return $this->sPostEvent;
  }

  public function getDestState() {
    return $this->oDestState;
  }
}
