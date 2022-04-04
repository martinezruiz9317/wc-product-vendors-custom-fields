<?php
/**
 * DmnVendorFields Class
 * Adds Custom Fields for Vendor
 */
defined('ABSPATH') || exit();

include_once(DMN_THEME_DIR . '/includes/classes/DmnFieldFactory.php');

class DmnVendorFields extends DmnFieldFactory{

    private $fields = [];

    public function __construct($fields = [])
    {
       if($fields) $this->setFields($fields);
       $this->installHooks(); 
    }

    private function installHooks(){
        // add fields when wp-admin is Initialised
        add_action( 'admin_init',array($this, 'registerVendorFields'));
        // Add Fields to Vendor Registration Form
        add_action( 'wcpv_registration_form', array($this, 'vendors_reg_custom_fields' ));
        // Validate Fields from Vendor Registration Form
        add_filter( 'wcpv_shortcode_registration_form_validation_errors', array($this,'vendors_reg_custom_fields_validation'), 10, 2 );
        // Save Fields of Vendor Registration Form
        add_action( 'wcpv_shortcode_registration_form_process', array($this, 'vendors_reg_custom_fields_save'), 10, 2 );
    }

    public function registerVendorFields(){
        // Add Fields to Admins when adding a new Vendor Taxonomy (Optionally can be added with PODS or ACF)
        add_action( DMN_VENDOR_TAX . '_add_form_fields', array($this, 'add_vendor_custom_fields' ));
        // Add Fields to Admins when editing a new Vendor Taxonomy (Optionally can be added with PODS or ACF)
	    add_action( DMN_VENDOR_TAX . '_edit_form_fields',  array($this,'edit_vendor_custom_fields'), 10 );
        // Save the Fields for Taxonomy
	    // add_action( 'edited_' . DMN_VENDOR_TAX,  array($this, 'save_vendor_custom_fields') );
	    // add_action( 'created_' . DMN_VENDOR_TAX,  array($this,'save_vendor_custom_fields') );
        // Render Fields for Vendor Dashboard Store Settings (Can't be added with PODS or ACF, and we can decide which fields are editable by the Vendor)
        add_action( 'wcpv_vendor_settings_render_additional_fields', array($this, 'add_fields_vendor_store_settings' ));
        // Research in Progress Hook to save fields on Dashboard Store Settings
    }

    /**
     * @param Array $fields
     * @key String label
     * @key String type
     * @key String name
     * @key Boolean vendor_access (Default true)
     * @key Boolean reg_form (Default false)
     */
    public function setFields($fields){
        if(!$fields) return;

        foreach($fields as $field){
            $vendorAccess = isset($field['vendor_access']) ? $field['vendor_access'] : true;
            $showRegFrom = isset($field['reg_form']) ? $field['reg_form'] : false;
            $options = isset($field['options']) ? $field['options'] : [];
            $css = isset($field['css']) ? $field['css'] : '';
            $filter = isset($field['filter']) ? $field['filter'] : '';
            $this->setField($field['name'], $field['label'], $field['type'], $field['description'], $vendorAccess,  $showRegFrom, $options,  $css, $filter);
        }
    }

    private function setField($name, $label, $type, $desc, $vendorAccess = true, $showRegFrom = false, $options = [], $css = '', $filter = ''){
        $this->fields[] = array(
            'name' => $name,
            'data_array' => 'vendor_data',
            'label' => $label,
            'type' => $type,
            'desc' => $desc,
            'options' => $options,
            'vendorAccess' => $vendorAccess,
            'sowInRegform' => $showRegFrom,
            'css' => $css,
            'filter' => $filter,
        );
    }

 
    
    public function add_vendor_custom_fields(){
            wp_nonce_field( basename( __FILE__ ), 'vendor_custom_fields_nonce' );
            foreach($this->fields as $field){
                echo $this->getFieldHTML($field, '', 'add_term');
            }
    }

    public function edit_vendor_custom_fields($term){
        $value = get_term_meta( $term->term_id, 'vendor_data', true );
        foreach($this->fields as $field){
            echo $this->getFieldHTML($field,$value[$field['name']], 'edit_term');
        }
    }

    public function add_fields_vendor_store_settings(){
        $vendor_custom_data_array = WC_Product_Vendors_Utils::get_vendor_data_from_user();
        foreach($this->fields as $field){     
            if ($field['vendorAccess']){
                echo $this->getFieldHTML($field, $vendor_custom_data_array[$field['name']], 'edit_term');
            }
        }
        ?>
        <?php
    }

    public function vendors_reg_custom_fields() {
        
        foreach($this->fields as $field){
            if($field['sowInRegform']){
                echo $this->getFieldHTML($field, '', 'reg_form');
            }   
        }
    }

    public function vendors_reg_custom_fields_validation( $errors, $form_items ) {

        foreach($this->fields as $field){
            if($field['sowInRegform']){
               if(empty($form_items[$field['name']])){
                $errors[$field['name']] = __( $field['label'].' field cannot be empty.', 'oceanwp' );
               }
               if(!empty($field['filter']) && filter_var( $form_items[$field['name']], FILTER_VALIDATE_URL ) === false){
                    $errors[$field['name']] = __( $field['label'].' field format is not correct.', 'oceanwp' );
               }
            }   
        }
       
        return $errors;
    
    }
    
    public function vendors_reg_custom_fields_save( $args, $items ) {
        $term = get_term_by( 'name', $items['vendor_name'], DMN_VENDOR_TAX );
        $data = get_term_meta( $term->term_id, 'vendor_data', true );
        
        
        foreach($this->fields as $field){
            if($field['sowInRegform']){
                if ( isset( $items[$field['name']] ) && ! empty( $items[$field['name']] ) ) {
                    $data[$field['name']] = $items[$field['name']];
                    
                }
            }   
        }
        update_term_meta( $term->term_id, 'vendor_data', $data );
    }

}

$countries_obj   = new WC_Countries();
$fields = array(
    array(
        'name' => 'vendor_contact',
        'type' => 'tel',
        'label' => 'Contact Number',
        'description' => '',
        'reg_form' => true,
        'css' => 'form-row-wide'
    ),
    array(
        'name' => 'vendor_province',
        'type' => 'select',
        'label' => 'Province',
        'description' => '',
        'reg_form' => true,
        'css' => 'form-row-first',
        'options' => $countries_obj->get_states( "ZA" )
    ),
    array(
        'name' => 'vendor_suburb',
        'type' => 'text',
        'label' => 'Suburb',
        'reg_form' => true,
        'css' => 'form-row-last',
        'description' => ''
    ),
    array(
        'name' => 'vendor_address',
        'type' => 'text',
        'label' => 'Address',
        'description' => '',
        'reg_form' => true,
        'css' => 'form-row-wide'
    ),
    array(
        'name' => 'vendor_directions',
        'type' => 'url',
        'label' => 'Google Map Link',
        'description' => 'Insert Shareable link, to get it got to: Google Maps, seach for the name of the place, click on share and get the Send a link url.',
        'filter' => FILTER_VALIDATE_URL,
    ),
);

$fieldobj = new DmnVendorFields($fields);


