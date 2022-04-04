<?php
/**
 * DmnFieldFactory Class
 */
defined('ABSPATH') || exit();

class DmnFieldFactory {

   const INPUTS = array('text', 'url', 'password', 'tel', 'email');

   protected function getFieldHTML($field, $value = '', $screen = null){
        switch ($field['type']){
            case (in_array($field['type'], DmnFieldFactory::INPUTS)):
                return $this->loadFieldTemplate('input', $field, $value, $screen);
                break;
            case "select":
                return $this->loadFieldTemplate($field['type'], $field, $value, $screen);
                break;
        }
   }

   private function loadFieldTemplate($tag, $field, $value = '', $screen){
        ob_start();
            get_template_part( 'includes/components/fields/'.$tag, 'field', array('field' => $field, 'value' => $value, 'screen' => $screen) );
        return ob_get_clean();
   }
 
}