<?php 
class Adrlist_Human{
	//Check to see if the user is human or not.
	
	public function __construct(){
	}
	
	public static function humanSlider($goSwitch){
		global $debug;
		return '<div class="full-width-slider noSliderInput center">
    <label for="humanSlider">Slide to Send</label>
    <input name="humanSlider" id="humanSlider" goswitch="' . $goSwitch . '" min="0" max="100" step="1" value="1" data-type="range">
</div>';
	}
}